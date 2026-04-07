<?php
/**
 * Script de Injeção de Correção v6 - FINAL
 */

$target_file = __DIR__ . '/admin/functions_demo.php';
$content_demo = <<<'PHP'
<?php
function demo_colunas_tabela(PDO $pdo, string $tabela) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$tabela`");
    $cols = []; while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $cols[] = $row['Field']; }
    return $cols;
}
function demo_col_nome(array $cols, string $logical) {
    foreach ($cols as $c) { if (strcasecmp((string)$c, $logical) === 0) return (string)$c; }
    return null;
}
function demo_card_valores_icone(array $card) {
    $caminho = $card['caminho_icone'] ?? $card['icone'] ?? '';
    $tipo = $card['tipo_icone'] ?? ((is_string($caminho) && strpos($caminho, 'bi-') !== false) ? 'bootstrap' : 'imagem');
    return ['caminho' => $caminho, 'tipo' => $tipo];
}
function clonar_dados_demonstrativos($pdo, $id_origem, $id_destino) {
    // Registra se a transação foi iniciada aqui ou pelo chamador
    $iniciou_transacao = false;
    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $iniciou_transacao = true;
        }
        $pdo->prepare("DELETE FROM configuracoes WHERE id_prefeitura = ?")->execute([$id_destino]);
        foreach ($pdo->query("SELECT * FROM configuracoes WHERE id_prefeitura = $id_origem")->fetchAll() as $conf) {
             $pdo->prepare("INSERT INTO configuracoes (chave, valor, id_prefeitura) VALUES (?, ?, ?)")->execute([$conf['chave'], $conf['valor'], $id_destino]);
        }
        $map_cat = []; $cols_cat = demo_colunas_tabela($pdo, 'categorias');
        foreach ($pdo->query("SELECT * FROM categorias WHERE id_prefeitura = $id_origem")->fetchAll() as $cat) {
            $insert = ['id_prefeitura'=>$id_destino, 'nome'=>$cat['nome'], 'ordem'=>$cat['ordem']];
            if (in_array('slug', $cols_cat)) $insert['slug'] = $cat['slug'] ?? null;
            $ph = implode(',', array_fill(0, count($insert), '?'));
            $pdo->prepare("INSERT INTO categorias (".implode(',', array_keys($insert)).") VALUES ($ph)")->execute(array_values($insert));
            $map_cat[$cat['id']] = $pdo->lastInsertId();
        }
        $map_p = [];
        foreach ($pdo->query("SELECT * FROM portais WHERE id_prefeitura = $id_origem")->fetchAll() as $p) {
            $pdo->prepare("INSERT INTO portais (id_prefeitura, id_categoria, nome, descricao, slug, ordem) VALUES (?,?,?,?,?,?)")
                ->execute([$id_destino, $map_cat[$p['id_categoria']] ?? null, $p['nome'], $p['descricao'], $p['slug'], $p['ordem']]);
            $new_p_id = $pdo->lastInsertId(); $map_p[$p['id']] = $new_p_id;
            foreach ($pdo->query("SELECT * FROM campos_portal WHERE id_portal = ".$p['id'])->fetchAll() as $c) {
                $pdo->prepare("INSERT INTO campos_portal (id_portal, nome_campo, tipo_campo, opcoes_campo, obrigatorio, pesquisavel, detalhes_apenas, ordem) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$new_p_id, $c['nome_campo'], $c['tipo_campo'], $c['opcoes_campo'], $c['obrigatorio'], $c['pesquisavel'], $c['detalhes_apenas'], $c['ordem']]);
            }
        }
        $cols_cards = demo_colunas_tabela($pdo, 'cards_informativos');
        foreach ($pdo->query("SELECT * FROM cards_informativos WHERE id_prefeitura = $id_origem")->fetchAll() as $card) {
            $ico = demo_card_valores_icone($card);
            $ins = ['id_prefeitura'=>$id_destino, 'id_secao'=>$map_p[$card['id_secao']] ?? null, 'id_categoria'=>$map_cat[$card['id_categoria']] ?? null, 'titulo'=>$card['titulo'], 'subtitulo'=>$card['subtitulo'], 'link_url'=>$card['link_url'], 'ordem'=>$card['ordem']];
            if (in_array('caminho_icone',$cols_cards)) { $ins['caminho_icone']=$ico['caminho']; if (in_array('tipo_icone',$cols_cards)) $ins['tipo_icone']=$ico['tipo']; }
            elseif (in_array('icone',$cols_cards)) $ins['icone']=$ico['caminho'];
            $ph = implode(',', array_fill(0,count($ins),'?'));
            $pdo->prepare("INSERT INTO cards_informativos (".implode(',',array_keys($ins)).") VALUES ($ph)")->execute(array_values($ins));
        }
        
        // Só faz commit se iniciou a transação AQUI
        if ($iniciou_transacao) {
            $pdo->commit(); 
        }
        return true;
    } catch (Exception $e) {
        // Só faz rollback se iniciou a transação AQUI
        if ($iniciou_transacao && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw new Exception("Falha na clonagem: " . $e->getMessage());
    }
}
PHP;
@file_put_contents($target_file, $content_demo);
if (function_exists('opcache_reset')) opcache_reset();
echo "✅ functions_demo.php atualizado: Controle de transação (commit) corrigido!";
