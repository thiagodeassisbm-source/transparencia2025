<?php
/**
 * Script de Sincronização Forçada - Transparência 2026 (v2 - Fixed)
 */

header('Content-Type: text/plain; charset=utf-8');
echo "Iniciando Sincronização Forçada (v2)...\n\n";

$target_file = __DIR__ . '/admin/functions_demo.php';
$target_file_debug = __DIR__ . '/admin/clone_debug.php';

// Conteúdo de functions_demo.php CORRIGIDO (Lógica simplificada e robusta)
$content_demo = <<<'PHP'
<?php
// /admin/functions_demo.php (Sobrescrito via Sync Script)
if (file_exists(__DIR__ . '/clone_debug.php')) {
    require_once __DIR__ . '/clone_debug.php';
} else {
    if (!function_exists('clone_debug_log')) {
        function clone_debug_log($m) {}
        function clone_debug_verbose() { return false; }
    }
}

function demo_colunas_tabela(PDO $pdo, string $tabela) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$tabela` ");
    $cols = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $cols[] = $row['Field']; }
    return $cols;
}

function demo_col_nome(array $cols, string $logical) {
    foreach ($cols as $c) { if (strcasecmp((string) $c, $logical) === 0) return (string) $c; }
    return null;
}

function demo_card_valores_icone(array $card) {
    $caminho = $card['caminho_icone'] ?? $card['icone'] ?? '';
    $tipo = $card['tipo_icone'] ?? ((is_string($caminho) && strpos($caminho, 'bi-') !== false) ? 'bootstrap' : 'imagem');
    return ['caminho' => $caminho, 'tipo' => $tipo];
}

function clonar_dados_demonstrativos($pdo, $id_origem, $id_destino) {
    try {
        if (!$pdo->inTransaction()) $pdo->beginTransaction();
        
        $pdo->prepare("DELETE FROM configuracoes WHERE id_prefeitura = ?")->execute([$id_destino]);
        $stmt_conf = $pdo->prepare("SELECT * FROM configuracoes WHERE id_prefeitura = ?");
        $stmt_conf->execute([$id_origem]);
        foreach ($stmt_conf->fetchAll() as $conf) {
             $pdo->prepare("INSERT INTO configuracoes (chave, valor, id_prefeitura) VALUES (?, ?, ?)")
                 ->execute([$conf['chave'], $conf['valor'], $id_destino]);
        }

        $stmt_cat = $pdo->prepare("SELECT * FROM categorias WHERE id_prefeitura = ?");
        $stmt_cat->execute([$id_origem]);
        $map_categorias = [];
        $cols_cat = demo_colunas_tabela($pdo, 'categorias');

        foreach ($stmt_cat->fetchAll() as $cat) {
            $insert_data = ['id_prefeitura' => $id_destino, 'nome' => $cat['nome'], 'ordem' => $cat['ordem']];
            if (in_array('slug', $cols_cat)) $insert_data['slug'] = $cat['slug'] ?? null;
            
            $cols = array_keys($insert_data);
            $vals = array_values($insert_data);
            $ph = implode(',', array_fill(0, count($cols), '?'));
            $pdo->prepare("INSERT INTO categorias (".implode(',', $cols).") VALUES ($ph)")->execute($vals);
            $map_categorias[$cat['id']] = $pdo->lastInsertId();
        }

        $stmt_p = $pdo->prepare("SELECT * FROM portais WHERE id_prefeitura = ?");
        $stmt_p->execute([$id_origem]);
        $map_portais = [];
        $cols_p = demo_colunas_tabela($pdo, 'portais');

        foreach ($stmt_p->fetchAll() as $p) {
            $insert_data = [
                'id_prefeitura' => $id_destino, 
                'id_categoria' => $map_categorias[$p['id_categoria']] ?? null, 
                'nome' => $p['nome'], 
                'descricao' => $p['descricao'], 
                'slug' => $p['slug'], 
                'ordem' => $p['ordem']
            ];
            $cols = array_keys($insert_data);
            $vals = array_values($insert_data);
            $ph = implode(',', array_fill(0, count($cols), '?'));
            $pdo->prepare("INSERT INTO portais (".implode(',', $cols).") VALUES ($ph)")->execute($vals);
            $new_p_id = $pdo->lastInsertId();
            $map_portais[$p['id']] = $new_p_id;
            
            $stmt_c = $pdo->prepare("SELECT * FROM campos_portal WHERE id_portal = ?");
            $stmt_c->execute([$p['id']]);
            foreach ($stmt_c->fetchAll() as $c) {
                $pdo->prepare("INSERT INTO campos_portal (id_portal, nome_campo, tipo_campo, opcoes_campo, obrigatorio, pesquisavel, detalhes_apenas, ordem) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$new_p_id, $c['nome_campo'], $c['tipo_campo'], $c['opcoes_campo'], $c['obrigatorio'], $c['pesquisavel'], $c['detalhes_apenas'], $c['ordem']]);
            }
        }

        $stmt_cards = $pdo->prepare("SELECT * FROM cards_informativos WHERE id_prefeitura = ?");
        $stmt_cards->execute([$id_origem]);
        $cols_cards = demo_colunas_tabela($pdo, 'cards_informativos');

        foreach ($stmt_cards->fetchAll() as $card) {
            $ico = demo_card_valores_icone($card);
            $insert_data = [
                'id_prefeitura' => $id_destino, 
                'id_secao' => $map_portais[$card['id_secao']] ?? null, 
                'id_categoria' => $map_categorias[$card['id_categoria']] ?? null, 
                'titulo' => $card['titulo'], 
                'subtitulo' => $card['subtitulo'], 
                'link_url' => $card['link_url'], 
                'ordem' => $card['ordem']
            ];
            
            if (in_array('caminho_icone', $cols_cards)) {
                $insert_data['caminho_icone'] = $ico['caminho'];
                if (in_array('tipo_icone', $cols_cards)) $insert_data['tipo_icone'] = $ico['tipo'];
            } elseif (in_array('icone', $cols_cards)) {
                $insert_data['icone'] = $ico['caminho'];
            }

            $cols = array_keys($insert_data);
            $vals = array_values($insert_data);
            $ph = implode(',', array_fill(0, count($cols), '?'));
            $pdo->prepare("INSERT INTO cards_informativos (".implode(',', $cols).") VALUES ($ph)")->execute($vals);
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw new Exception("Falha na clonagem (Banco de Dados): " . $e->getMessage());
    }
}
PHP;

if (@file_put_contents($target_file, $content_demo)) {
    echo "✅ admin/functions_demo.php atualizado forçadamente!\n";
} else {
    echo "❌ Erro: Não foi possível gravar no arquivo. Permissão negada pelo servidor.\n";
}

if (function_exists('opcache_reset')) opcache_reset();
echo "\nOPcache limpo. Sincronização concluída!";
