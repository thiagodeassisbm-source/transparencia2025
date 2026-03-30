<?php
// /admin/functions_demo.php

/**
 * Clona toda a estrutura e dados de uma prefeitura para outra como "Demonstração".
 */
function clonar_dados_demonstrativos($pdo, $id_origem, $id_destino) {
    // Verifica se já existe uma transação ativa para evitar o erro de "Nested Transactions"
    $should_manage_transaction = !$pdo->inTransaction();
    
    try {
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

        // --- 2. Clona os Portais (Seções) e mapeia IDs ---
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

        // --- 3. Clona TODOS os Cards da Home (Incluindo SIC, Ouvidoria, etc) ---
        $stmt_cards_all = $pdo->prepare("SELECT * FROM cards_informativos WHERE id_prefeitura = ?");
        $stmt_cards_all->execute([$id_origem]);
        $all_cards = $stmt_cards_all->fetchAll();
        
        $ins_card = $pdo->prepare("INSERT INTO cards_informativos (id_prefeitura, id_secao, id_categoria, titulo, subtitulo, caminho_icone, tipo_icone, link_url, ordem, is_demo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        
        foreach ($all_cards as $card) {
            // Mapeia o ID da seção se o card estiver vinculado a uma seção clonada
            $nova_secao = null;
            if (!empty($card['id_secao']) && isset($map_portais[$card['id_secao']])) {
                $nova_secao = $map_portais[$card['id_secao']];
            }
            
            $ins_card->execute([
                $id_destino,
                $nova_secao,
                $card['id_categoria'],
                $card['titulo'],
                $card['subtitulo'],
                $card['caminho_icone'],
                $card['tipo_icone'],
                $card['link_url'],
                $card['ordem']
            ]);
        }

        if ($should_manage_transaction) {
            $pdo->commit();
        }
        return true;
    } catch (Exception $e) {
        if ($should_manage_transaction && $pdo->inTransaction()) { 
            $pdo->rollBack(); 
        }
        throw new Exception("Falha na clonagem (Banco de Dados): " . $e->getMessage());
    }
}
?>
