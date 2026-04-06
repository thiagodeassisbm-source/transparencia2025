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
    
    // Forçamos limpeza de cache se estivermos em modo debug agressivo ou se houver suspeita de mudança de schema
    if (isset($_SESSION['clone_debug_verbose']) && $_SESSION['clone_debug_verbose'] == 1) {
        unset($cache[$cacheKey]);
    }

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
        clone_debug_log('categorias: ' . count($categorias_origem) . ' colunas detectadas: ' . implode(', ', $cols_cat));

        foreach ($categorias_origem as $cat) {
            $insert_cols = [];
            $insert_vals = [];
            $push_cat = function (string $logical, $val) use (&$insert_cols, &$insert_vals, $cols_cat) {
                // EXCEÇÃO CRÍTICA: Se a coluna for "icone" e ela for legacy, ignorar se estiver causando erro ou não existir no schema físico.
                // Na dúvida, não clonamos "icone" em categorias para evitar erros 42S22.
                if ($logical === 'icone') return;
                
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

            if ($insert_cols === []) continue;

            $ph = implode(', ', array_fill(0, count($insert_cols), '?'));
            $quoted = array_map(function ($c) { return '`' . str_replace('`', '``', $c) . '`'; }, $insert_cols);
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
            $nova_cat_id = isset($map_categorias[$p['id_categoria']]) ? $map_categorias[$p['id_categoria']] : null;

            $insert_cols = [];
            $insert_vals = [];
            $push_p = function (string $logical, $val) use (&$insert_cols, &$insert_vals, $cols_portais) {
                if ($logical === 'icone') return; // Ignora icone legado
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

            if ($insert_cols === []) continue;

            $ph = implode(', ', array_fill(0, count($insert_cols), '?'));
            $quoted = array_map(function ($c) { return '`' . str_replace('`', '``', $c) . '`'; }, $insert_cols);
            $sql_p = 'INSERT INTO portais (' . implode(', ', $quoted) . ') VALUES (' . $ph . ')';
            $pdo->prepare($sql_p)->execute($insert_vals);
            $new_p_id = $pdo->lastInsertId();
            $map_portais[$p['id']] = $new_p_id;
            
            // --- Clona Campos da Seção ---
            $stmt_c = $pdo->prepare("SELECT * FROM campos_portal WHERE id_portal = ?");
            $stmt_c->execute([$p['id']]);
            $campos_origem = $stmt_c->fetchAll();
            
            $ins_c = $pdo->prepare("INSERT INTO campos_portal (id_portal, nome_campo, tipo_campo, opcoes_campo, obrigatorio, pesquisavel, detalhes_apenas, ordem) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($campos_origem as $c) {
                $ins_c->execute([$new_p_id, $c['nome_campo'], $c['tipo_campo'], $c['opcoes_campo'], $c['obrigatorio'], $c['pesquisavel'], $c['detalhes_apenas'], $c['ordem']]);
            }
        }

        // --- 4. Clona TODOS os Cards da Home ---
        $stmt_cards_all = $pdo->prepare("SELECT * FROM cards_informativos WHERE id_prefeitura = ?");
        $stmt_cards_all->execute([$id_origem]);
        $all_cards = $stmt_cards_all->fetchAll();

        $cols_cards = demo_colunas_tabela($pdo, 'cards_informativos');
        $nom_caminho = demo_col_nome($cols_cards, 'caminho_icone');
        $nom_tipo = demo_col_nome($cols_cards, 'tipo_icone');
        $nom_icone_leg = demo_col_nome($cols_cards, 'icone');
        
        clone_debug_log('cards_informativos cols: ' . implode(', ', $cols_cards));

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
                // Se a lógica detectou icone_legado mas ele falha no SQL, temos um problema de detecção.
                // Para garantir, se "caminho_icone" existe, ignoramos "icone" completamente.
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
                // VERIFICAÇÃO FINAL: Só adiciona icone se caminho_icone NÃO estiver na lista de colunas físicas
                $real_caminho = demo_col_nome($cols_cards, 'caminho_icone');
                if ($real_caminho === null) {
                    $insert_cols[] = $nom_icone_leg;
                    $insert_vals[] = $ico['caminho'];
                }
            }

            if ($insert_cols === []) continue;

            $ph = implode(', ', array_fill(0, count($insert_cols), '?'));
            $quoted = array_map(function ($c) { return '`' . str_replace('`', '``', $c) . '`'; }, $insert_cols);
            $sql_ins = 'INSERT INTO cards_informativos (' . implode(', ', $quoted) . ') VALUES (' . $ph . ')';
            $pdo->prepare($sql_ins)->execute($insert_vals);
        }

        if ($should_manage_transaction) {
            $pdo->commit();
        }
        clone_debug_log('clonar_dados_demonstrativos OK');
        return true;
    } catch (Exception $e) {
        if ($should_manage_transaction && $pdo->inTransaction()) { 
            $pdo->rollBack(); 
        }
        $msg = 'Falha na clonagem (Banco de Dados): ' . $e->getMessage();
        clone_debug_log('EXCEPTION: ' . $msg);
        throw new Exception($msg);
    }
}
 ?>
