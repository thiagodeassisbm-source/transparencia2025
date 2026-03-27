<?php
// /admin/functions_demo.php

/**
 * Clona toda a estrutura e dados de uma prefeitura para outra como "Demonstração".
 */
function clonar_dados_demonstrativos($pdo, $id_origem, $id_destino) {
    try {
        $pdo->beginTransaction();

        // 1. Clona os Portais (Seções) e mapeia IDs
        $stmt_p = $pdo->prepare("SELECT * FROM portais WHERE id_prefeitura = ?");
        $stmt_p->execute([$id_origem]);
        $portais_origem = $stmt_p->fetchAll();
        
        $map_portais = []; // old_id => new_id
        
        $ins_p = $pdo->prepare("INSERT INTO portais (id_prefeitura, id_categoria, nome, slug, ordem, is_demo) VALUES (?, ?, ?, ?, ?, 1)");
        
        foreach ($portais_origem as $p) {
            $ins_p->execute([
                $id_destino, 
                $p['id_categoria'], 
                $p['nome'], 
                $p['slug'], 
                $p['ordem']
            ]);
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
            
            $ins_r = $pdo->prepare("INSERT INTO registros (id_portal, is_demo) VALUES (?, 1)");
            
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

        // 2. Clona os Cards da Home (que agora pertencem a prefeitura via id_secao)
        foreach ($map_portais as $old_pid => $new_pid) {
            $stmt_card = $pdo->prepare("SELECT * FROM cards_informativos WHERE id_secao = ?");
            $stmt_card->execute([$old_pid]);
            $card = $stmt_card->fetch();
            
            if ($card) {
                $ins_card = $pdo->prepare("INSERT INTO cards_informativos (id_secao, id_categoria, titulo, subtitulo, caminho_icone, tipo_icone, link_url, ordem, is_demo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
                $ins_card->execute([
                    $new_pid,
                    $card['id_categoria'],
                    $card['titulo'],
                    $card['subtitulo'],
                    $card['caminho_icone'],
                    $card['tipo_icone'],
                    $card['link_url'],
                    $card['ordem']
                ]);
            }
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw new Exception("Falha na clonagem (Banco de Dados): " . $e->getMessage());
    }
}
?>
