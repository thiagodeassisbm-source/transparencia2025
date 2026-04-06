<?php
// /admin/functions_demo.php

require_once __DIR__ . '/clone_debug.php';

/**
 * Lista nomes de colunas da tabela (cache por BD + tabela; evita colisão entre conexões).
 */
function demo_colunas_tabela(PDO $pdo, string $tabela): array {
    static $cache = [];
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $cacheKey = ($db !== false ? (string) $db : '_') . '|' . $tabela;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }
    $safe = str_replace('`', '``', $tabela);
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$safe}`");
    $cols = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['Field'])) {
            $cols[] = $row['Field'];
        }
    }
    $cache[$cacheKey] = $cols;
    return $cols;
}

/**
 * Retorna o nome real da coluna no banco (case-insensitive) ou null.
 */
function demo_col_nome(array $cols, string $logical): ?string {
    foreach ($cols as $c) {
        if (strcasecmp((string) $c, $logical) === 0) {
            return (string) $c;
        }
    }
    return null;
}

/**
 * Monta valores de ícone a partir de uma linha de cards (compatível com schema antigo ou novo).
 */
function demo_card_valores_icone(array $card): array {
    $caminho = $card['caminho_icone'] ?? $card['icone'] ?? '';
    $tipo = $card['tipo_icone'] ?? null;
    if ($tipo === null || $tipo === '') {
        $tipo = (is_string($caminho) && preg_match('/^\s*bi-/i', $caminho)) ? 'bootstrap' : 'imagem';
    }
    return ['caminho' => $caminho, 'tipo' => $tipo];
}

/**
 * Clona toda a estrutura e dados de uma prefeitura para outra como "Demonstração".
 */
function clonar_dados_demonstrativos($pdo, $id_origem, $id_destino) {
    // Verifica se já existe uma transação ativa para evitar o erro de "Nested Transactions"
    $should_manage_transaction = !$pdo->inTransaction();
    
    try {
        clone_debug_log('clonar_dados_demonstrativos START origem=' . $id_origem . ' destino=' . $id_destino);
        clone_debug_log('functions_demo: ' . json_encode(clone_debug_file_fingerprint(clone_debug_functions_demo_path()), JSON_UNESCAPED_UNICODE));

        if ($should_manage_transaction) {
            $pdo->beginTransaction();
        }

        // --- 1. Clona as Configurações (Branding: Cores, Logos, Títulos) ---
        // Primeiro limpamos o que o cadastrar_prefeitura.php inseriu de básico
        $pdo->prepare("DELETE FROM configuracoes WHERE id_prefeitura = ?")->execute([$id_destino]);
        
        $stmt_conf = $pdo->prepare("SELECT * FROM configuracoes WHERE id_prefeitura = ?");
        $stmt_conf->execute([$id_origem]);
        $configs_origem = $stmt_conf->fetchAll();
        
        $ins_conf = $pdo->prepare("INSERT INTO configuracoes (chave, valor, id_prefeitura) VALUES (?, ?, ?)");
        foreach ($configs_origem as $conf) {
             $ins_conf->execute([$conf['chave'], $conf['valor'], $id_destino]);
        }

        // --- 2. Clona as Categorias (Menu Lateral) ---
        $stmt_cat = $pdo->prepare("SELECT * FROM categorias WHERE id_prefeitura = ?");
        $stmt_cat->execute([$id_origem]);
        $categorias_origem = $stmt_cat->fetchAll();

        $map_categorias = []; // old_id => new_id
        $cols_cat = demo_colunas_tabela($pdo, 'categorias');
        clone_debug_log('categorias: ' . count($categorias_origem) . ' linhas | SHOW COLUMNS: ' . json_encode($cols_cat, JSON_UNESCAPED_UNICODE));

        foreach ($categorias_origem as $cat) {
            $insert_cols = [];
            $insert_vals = [];
            $push_cat = function (string $logical, $val) use (&$insert_cols, &$insert_vals, $cols_cat) {
                $real = demo_col_nome($cols_cat, $logical);
                if ($real !== null) {
                    $insert_cols[] = $real;
                    $insert_vals[] = $val;
                }
            };

            $push_cat('id_prefeitura', $id_destino);
            $push_cat('nome', $cat['nome']);
            $push_cat('ordem', $cat['ordem']);
            $push_cat('slug', $cat['slug'] ?? null);
            // Não clonar coluna "icone" em categorias: muitos bancos não têm; o menu público não depende disso.

            if ($insert_cols === []) {
                throw new Exception('Tabela categorias sem colunas reconhecidas para INSERT.');
            }

            $ph = implode(', ', array_fill(0, count($insert_cols), '?'));
            $quoted = array_map(static function ($c) {
                return '`' . str_replace('`', '``', $c) . '`';
            }, $insert_cols);
            $sql_cat = 'INSERT INTO categorias (' . implode(', ', $quoted) . ') VALUES (' . $ph . ')';
            $pdo->prepare($sql_cat)->execute($insert_vals);
            $map_categorias[$cat['id']] = $pdo->lastInsertId();
        }

        // --- 3. Clona os Portais (Seções) e mapeia IDs ---
        $stmt_p = $pdo->prepare("SELECT * FROM portais WHERE id_prefeitura = ?");
        $stmt_p->execute([$id_origem]);
        $portais_origem = $stmt_p->fetchAll();
        
        $map_portais = []; // old_id => new_id
        $cols_portais = demo_colunas_tabela($pdo, 'portais');
        
        foreach ($portais_origem as $p) {
            // Mapeia a nova categoria
            $nova_cat_id = isset($map_categorias[$p['id_categoria']]) ? $map_categorias[$p['id_categoria']] : null;

            $insert_cols = [];
            $insert_vals = [];
            $push_p = function (string $logical, $val) use (&$insert_cols, &$insert_vals, $cols_portais) {
                $real = demo_col_nome($cols_portais, $logical);
                if ($real !== null) {
                    $insert_cols[] = $real;
                    $insert_vals[] = $val;
                }
            };

            $push_p('id_prefeitura', $id_destino);
            $push_p('id_categoria', $nova_cat_id);
            $push_p('nome', $p['nome']);
            $push_p('descricao', $p['descricao'] ?? '');
            $push_p('slug', $p['slug']);
            $push_p('ordem', $p['ordem'] ?? 0);

            if ($insert_cols === []) {
                throw new Exception('Tabela portais sem colunas reconhecidas para INSERT.');
            }

            $ph = implode(', ', array_fill(0, count($insert_cols), '?'));
            $quoted = array_map(static function ($c) {
                return '`' . str_replace('`', '``', $c) . '`';
            }, $insert_cols);
            $sql_p = 'INSERT INTO portais (' . implode(', ', $quoted) . ') VALUES (' . $ph . ')';
            $pdo->prepare($sql_p)->execute($insert_vals);
            $new_p_id = $pdo->lastInsertId();
            $map_portais[$p['id']] = $new_p_id;
            
            // --- Clona Campos da Seção ---
            $stmt_c = $pdo->prepare("SELECT * FROM campos_portal WHERE id_portal = ?");
            $stmt_c->execute([$p['id']]);
            $campos_origem = $stmt_c->fetchAll();
            
            $map_campos = []; // old_id => new_id
            $ins_c = $pdo->prepare("INSERT INTO campos_portal (id_portal, nome_campo, tipo_campo, opcoes_campo, obrigatorio, pesquisavel, detalhes_apenas, ordem) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($campos_origem as $c) {
                $ins_c->execute([
                    $new_p_id, 
                    $c['nome_campo'], 
                    $c['tipo_campo'], 
                    $c['opcoes_campo'], 
                    $c['obrigatorio'], 
                    $c['pesquisavel'], 
                    $c['detalhes_apenas'], 
                    $c['ordem']
                ]);
                $map_campos[$c['id']] = $pdo->lastInsertId();
            }
            
            // --- Clona Registros (Lançamentos) ---
            $stmt_r = $pdo->prepare("SELECT * FROM registros WHERE id_portal = ?");
            $stmt_r->execute([$p['id']]);
            $registros_origem = $stmt_r->fetchAll();
            
            $ins_r = $pdo->prepare("INSERT INTO registros (id_portal) VALUES (?)");
            
            foreach ($registros_origem as $r) {
                $ins_r->execute([$new_p_id]);
                $new_r_id = $pdo->lastInsertId();
                
                // --- Clona Valores do Registro ---
                $stmt_v = $pdo->prepare("SELECT * FROM valores_registros WHERE id_registro = ?");
                $stmt_v->execute([$r['id']]);
                $valores_origem = $stmt_v->fetchALL();
                
                $ins_v = $pdo->prepare("INSERT INTO valores_registros (id_registro, id_campo, valor) VALUES (?, ?, ?)");
                foreach ($valores_origem as $v) {
                    if (isset($map_campos[$v['id_campo']])) {
                        $ins_v->execute([$new_r_id, $map_campos[$v['id_campo']], $v['valor']]);
                    }
                }
            }
        }

        // --- 4. Clona TODOS os Cards da Home (Incluindo SIC, Ouvidoria, etc) ---
        $stmt_cards_all = $pdo->prepare("SELECT * FROM cards_informativos WHERE id_prefeitura = ?");
        $stmt_cards_all->execute([$id_origem]);
        $all_cards = $stmt_cards_all->fetchAll();

        $cols_cards = demo_colunas_tabela($pdo, 'cards_informativos');
        $nom_caminho = demo_col_nome($cols_cards, 'caminho_icone');
        $nom_tipo = demo_col_nome($cols_cards, 'tipo_icone');
        $nom_icone_leg = demo_col_nome($cols_cards, 'icone');
        clone_debug_log('cards_informativos: ' . count($all_cards) . ' linhas | cols=' . json_encode($cols_cards, JSON_UNESCAPED_UNICODE));
        clone_debug_log('resolve icone: caminho_icone=' . ($nom_caminho ?? 'null') . ' tipo_icone=' . ($nom_tipo ?? 'null') . ' icone(leg)=' . ($nom_icone_leg ?? 'null'));

        $card_i = 0;
        foreach ($all_cards as $card) {
            $nova_secao = null;
            if (!empty($card['id_secao']) && isset($map_portais[$card['id_secao']])) {
                $nova_secao = $map_portais[$card['id_secao']];
            }

            $nova_cat_id_card = isset($map_categorias[$card['id_categoria']]) ? $map_categorias[$card['id_categoria']] : null;

            $ico = demo_card_valores_icone($card);

            $insert_cols = [];
            $insert_vals = [];
            $push_card = function (string $logical, $val) use (&$insert_cols, &$insert_vals, $cols_cards) {
                $real = demo_col_nome($cols_cards, $logical);
                if ($real !== null) {
                    $insert_cols[] = $real;
                    $insert_vals[] = $val;
                }
            };

            $push_card('id_prefeitura', $id_destino);
            $push_card('id_secao', $nova_secao);
            $push_card('id_categoria', $nova_cat_id_card);
            $push_card('titulo', $card['titulo']);
            $push_card('subtitulo', $card['subtitulo']);
            $push_card('link_url', $card['link_url']);
            $push_card('ordem', $card['ordem']);

            if ($nom_caminho !== null) {
                $insert_cols[] = $nom_caminho;
                $insert_vals[] = $ico['caminho'];
                if ($nom_tipo !== null) {
                    $insert_cols[] = $nom_tipo;
                    $insert_vals[] = $ico['tipo'];
                }
            } elseif ($nom_icone_leg !== null) {
                $insert_cols[] = $nom_icone_leg;
                $insert_vals[] = $ico['caminho'];
            }

            $nom_demo = demo_col_nome($cols_cards, 'is_demo');
            if ($nom_demo !== null) {
                $insert_cols[] = $nom_demo;
                $insert_vals[] = $card['is_demo'] ?? 0;
            }

            if ($insert_cols === []) {
                throw new Exception('Tabela cards_informativos sem colunas reconhecidas para INSERT.');
            }

            $ph = implode(', ', array_fill(0, count($insert_cols), '?'));
            $quoted = array_map(static function ($c) {
                return '`' . str_replace('`', '``', $c) . '`';
            }, $insert_cols);
            $sql_ins = 'INSERT INTO cards_informativos (' . implode(', ', $quoted) . ') VALUES (' . $ph . ')';
            if ($card_i === 0) {
                clone_debug_log('cards INSERT exemplo (1ª linha): ' . $sql_ins);
            }
            $card_i++;
            $pdo->prepare($sql_ins)->execute($insert_vals);
        }

        if ($should_manage_transaction) {
            $pdo->commit();
        }
        clone_debug_log('clonar_dados_demonstrativos OK');
        return true;
    } catch (Exception $e) {
        clone_debug_log('EXCEPTION: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        if (clone_debug_verbose()) {
            clone_debug_log($e->getTraceAsString());
        }
        if ($should_manage_transaction && $pdo->inTransaction()) { 
            $pdo->rollBack(); 
        }
        $msg = 'Falha na clonagem (Banco de Dados): ' . $e->getMessage();
        if (clone_debug_verbose()) {
            $msg .= "\n\n--- Trace (debug) ---\n" . $e->getTraceAsString();
        }
        throw new Exception($msg);
    }
}
?>
