<?php
// /admin/functions_demo.php

function demo_colunas_tabela(PDO $pdo, string $tabela) {
    $safe = str_replace('`', '``', $tabela);
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$safe}`");
    $cols = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['Field'])) $cols[] = $row['Field'];
    }
    return $cols;
}

function demo_card_valores_icone(array $card) {
    $caminho = $card['caminho_icone'] ?? $card['icone'] ?? '';
    // Tenta deduzir o tipo, caso não venha no schema e precise
    $tipo = $card['tipo_icone'] ?? ((is_string($caminho) && strpos($caminho, 'bi-') !== false) ? 'bootstrap' : 'imagem');
    return ['caminho' => $caminho, 'tipo' => $tipo];
}

// Helper genérico para clone dinâmico
function demo_clone_row(PDO $pdo, $tabela, $row_origem, $overrides, $cols) {
    $ins_cols = [];
    $ins_vals = [];
    foreach ($row_origem as $col => $val) {
        // Ignorar IDs antigos
        if ($col === 'id' || !in_array($col, $cols)) continue;
        // Pular 'icone' legado se existir como coluna mas quisermos forçar o ignorar
        if ($col === 'icone' && in_array('caminho_icone', $cols)) continue;
        
        $ins_cols[] = $col;
        $ins_vals[] = array_key_exists($col, $overrides) ? $overrides[$col] : $val;
    }
    
    // Processar campos adicionais definidos no override que talvez não estavam na origem
    foreach ($overrides as $col => $val) {
        if (!in_array($col, $ins_cols) && in_array($col, $cols)) {
            $ins_cols[] = $col;
            $ins_vals[] = $val;
        }
    }
    
    if (empty($ins_cols)) return null;
    
    $ph = implode(', ', array_fill(0, count($ins_cols), '?'));
    $quoted = array_map(function($c) { return "`$c`"; }, $ins_cols);
    $pdo->prepare("INSERT INTO `$tabela` (" . implode(', ', $quoted) . ") VALUES ($ph)")->execute($ins_vals);
    return $pdo->lastInsertId();
}

function clonar_dados_demonstrativos($pdo, $id_origem, $id_destino) {
    $iniciou = false;
    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $iniciou = true;
        }

        // 1. Configurações
        $pdo->prepare("DELETE FROM configuracoes WHERE id_prefeitura = ?")->execute([$id_destino]);
        foreach ($pdo->query("SELECT * FROM configuracoes WHERE id_prefeitura = $id_origem")->fetchAll() as $conf) {
             $pdo->prepare("INSERT INTO configuracoes (chave, valor, id_prefeitura) VALUES (?, ?, ?)")->execute([$conf['chave'], $conf['valor'], $id_destino]);
        }

        // 2. Categorias
        $map_cat = []; 
        $cols_cat = demo_colunas_tabela($pdo, 'categorias');
        foreach ($pdo->query("SELECT * FROM categorias WHERE id_prefeitura = $id_origem")->fetchAll() as $cat) {
            $map_cat[$cat['id']] = demo_clone_row($pdo, 'categorias', $cat, ['id_prefeitura' => $id_destino], $cols_cat);
        }

        // 3. Portais e Campos
        $map_p = [];
        $map_c = [];
        $cols_p = demo_colunas_tabela($pdo, 'portais');
        $cols_campos = demo_colunas_tabela($pdo, 'campos_portal');
        
        foreach ($pdo->query("SELECT * FROM portais WHERE id_prefeitura = $id_origem")->fetchAll() as $p) {
            $nova_cat = isset($map_cat[$p['id_categoria']]) ? $map_cat[$p['id_categoria']] : null;
            $novo_p = demo_clone_row($pdo, 'portais', $p, ['id_prefeitura' => $id_destino, 'id_categoria' => $nova_cat], $cols_p);
            $map_p[$p['id']] = $novo_p;
            
            // Campos do Portal
            $stmt_c = $pdo->prepare("SELECT * FROM campos_portal WHERE id_portal = ?");
            $stmt_c->execute([$p['id']]);
            foreach ($stmt_c->fetchAll() as $camp) {
                $map_c[$camp['id']] = demo_clone_row($pdo, 'campos_portal', $camp, ['id_portal' => $novo_p], $cols_campos);
            }
        }

        // 4. Cards Informativos
        $cols_cards = demo_colunas_tabela($pdo, 'cards_informativos');
        foreach ($pdo->query("SELECT * FROM cards_informativos WHERE id_prefeitura = $id_origem")->fetchAll() as $card) {
            $nova_secao = isset($map_p[$card['id_secao']]) ? $map_p[$card['id_secao']] : null;
            $nova_cat = isset($map_cat[$card['id_categoria']]) ? $map_cat[$card['id_categoria']] : null;
            
            $ovr = [
                'id_prefeitura' => $id_destino,
                'id_secao' => $nova_secao,
                'id_categoria' => $nova_cat
            ];
            
            $ico = demo_card_valores_icone($card);
            if (in_array('caminho_icone', $cols_cards)) {
                $ovr['caminho_icone'] = $ico['caminho'];
                if (in_array('tipo_icone', $cols_cards)) $ovr['tipo_icone'] = $ico['tipo'];
            } elseif (in_array('icone', $cols_cards)) {
                $ovr['icone'] = $ico['caminho'];
            }
            
            demo_clone_row($pdo, 'cards_informativos', $card, $ovr, $cols_cards);
        }

        // 5. Registros e Valores Internos (Os PDFs e Dados)
        $cols_reg = demo_colunas_tabela($pdo, 'registros');
        $cols_val = demo_colunas_tabela($pdo, 'valores_registros');
        
        foreach ($map_p as $old_p => $new_p) {
            $stmt_r = $pdo->prepare("SELECT * FROM registros WHERE id_portal = ?");
            $stmt_r->execute([$old_p]);
            
            foreach ($stmt_r->fetchAll() as $reg) {
                $ovr_reg = ['id_portal' => $new_p];
                // Se a tabela registros tiver vinculação por id_prefeitura, clonamos ela também para acelerar binds do frontend.
                if (in_array('id_prefeitura', $cols_reg)) $ovr_reg['id_prefeitura'] = $id_destino;
                
                $novo_r = demo_clone_row($pdo, 'registros', $reg, $ovr_reg, $cols_reg);
                
                if ($novo_r) {
                    $stmt_v = $pdo->prepare("SELECT * FROM valores_registros WHERE id_registro = ?");
                    $stmt_v->execute([$reg['id']]);
                    foreach ($stmt_v->fetchAll() as $val) {
                        $novo_cid = isset($map_c[$val['id_campo']]) ? $map_c[$val['id_campo']] : null;
                        if ($novo_cid) {
                            demo_clone_row($pdo, 'valores_registros', $val, ['id_registro' => $novo_r, 'id_campo' => $novo_cid], $cols_val);
                        }
                    }
                }
            }
        }

        if ($iniciou) $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($iniciou && $pdo->inTransaction()) $pdo->rollBack();
        throw new Exception($e->getMessage());
    }
}
?>
