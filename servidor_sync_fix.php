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

$target_file_debug_ui = __DIR__ . '/admin/debug_clone_ambiente.php';
$target_file_debug_lib = __DIR__ . '/admin/clone_debug.php';

$content_debug_lib = <<<'PHP'
<?php
/**
 * Usado por cadastrar_prefeitura.php e functions_demo.php (log/trace).
 */

if (function_exists('clone_debug_base_dir')) {
    return;
}

function clone_debug_base_dir(): string
{
    return __DIR__;
}

function clone_debug_tmp_dir(): string
{
    $d = clone_debug_base_dir() . '/tmp';
    if (!is_dir($d)) {
        @mkdir($d, 0755, true);
    }
    return $d;
}

function clone_debug_functions_demo_path(): string
{
    return clone_debug_base_dir() . '/functions_demo.php';
}

function clone_debug_file_fingerprint(string $absPath): array
{
    $out = [
        'path' => $absPath,
        'exists' => false,
        'readable' => false,
        'mtime' => null,
        'size' => null,
        'md5' => null,
    ];
    if (!file_exists($absPath)) return $out;
    $out['exists'] = true;
    $out['readable'] = is_readable($absPath);
    if ($out['readable']) {
        $mt = @filemtime($absPath);
        $out['mtime'] = $mt ? date('c', $mt) : null;
        $out['size'] = @filesize($absPath);
        $out['md5'] = @md5_file($absPath);
    }
    return $out;
}

function clone_debug_verbose(): bool
{
    if (session_status() === PHP_SESSION_ACTIVE && (int) ($_SESSION['clone_debug_verbose'] ?? 0) === 1) return true;
    return is_file(clone_debug_base_dir() . '/.clone_debug');
}

function clone_debug_log(string $line): void
{
    if (!clone_debug_verbose()) return;
    $file = clone_debug_tmp_dir() . '/clone_debug.log';
    $ts = date('Y-m-d H:i:s');
    @file_put_contents($file, '[' . $ts . '] ' . $line . "\n", FILE_APPEND | LOCK_EX);
}

function clone_debug_opcache_hint(): array
{
    if (!function_exists('opcache_get_status')) return ['available' => false];
    $st = @opcache_get_status(false);
    return ['available' => true, 'enabled' => !empty($st['opcache_enabled']), 'full' => $st];
}

function clone_debug_snapshot_runtime(?PDO $pdo = null, bool $includeOpcache = false): array
{
    $db = null;
    if ($pdo) { try { $db = $pdo->query('SELECT DATABASE()')->fetchColumn(); } catch(Throwable $e){}}
    $oc = clone_debug_opcache_hint();
    return [
        'written_at' => date('c'),
        'database' => $db,
        'functions_demo' => clone_debug_file_fingerprint(clone_debug_functions_demo_path()),
        'cadastrar_prefeitura' => clone_debug_file_fingerprint(clone_debug_base_dir() . '/cadastrar_prefeitura.php'),
        'opcache' => ['available' => $oc['available'], 'enabled' => $oc['enabled'] ?? null],
    ];
}

function clone_debug_write_last_result(?PDO $pdo, array $payload): void
{
    $path = clone_debug_tmp_dir() . '/clone_last_result.json';
    $payload['snapshot'] = clone_debug_snapshot_runtime($pdo);
    @file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function clone_debug_html_banner(): string
{
    $f = clone_debug_file_fingerprint(clone_debug_functions_demo_path());
    if (!$f['exists']) return '<div class="alert alert-danger small">clone_debug: functions_demo.php não encontrado</div>';
    return '<div class="alert alert-warning small font-monospace mb-3"><strong>Debug clonagem ativo</strong><br>'
        . 'MD5: ' . htmlspecialchars((string)$f['md5']) . ' · <a href="debug_clone_ambiente.php" class="alert-link">Diagnóstico completo</a></div>';
}
PHP;

$content_debug_ui = <<<'PHP'
<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'clone_debug.php';

if (!isset($_SESSION['is_superadmin']) || (int) $_SESSION['is_superadmin'] !== 1) {
    header('Location: dashboard.php'); exit;
}

$tmp = clone_debug_tmp_dir();
$logFile = $tmp . '/clone_debug.log';
$lastJson = $tmp . '/clone_last_result.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_verbose'])) $_SESSION['clone_debug_verbose'] = (int)$_POST['set_verbose'];
    if (isset($_POST['clear_log'])) @unlink($logFile);
    header('Location: debug_clone_ambiente.php'); exit;
}

$page_title_for_header = 'Debug — Clonagem';
include 'admin_header.php';
$snap = clone_debug_snapshot_runtime($pdo, true);

$tables = ['categorias', 'portais', 'cards_informativos', 'campos_portal'];
$describe = [];
foreach ($tables as $t) {
    try {
        $st = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $t) . '`');
        $describe[$t] = ['ok' => true, 'rows' => $st ? $st->fetchAll(PDO::FETCH_ASSOC) : []];
    } catch (Throwable $e) { $describe[$t] = ['ok' => false, 'error' => $e->getMessage()]; }
}

$all_ok = true; $health_errors = [];
if (!$snap['functions_demo']['exists']) { $all_ok = false; $health_errors[] = "functions_demo.php ausente."; }
foreach ($describe as $tbl => $info) { if (!$info['ok']) { $all_ok = false; $health_errors[] = "Erro em $tbl: ".$info['error']; } }
?>
<div class="container-fluid py-4">
    <div class="row justify-content-center"><div class="col-lg-11">
    <h4 class="fw-bold mb-3"><i class="bi bi-bug me-2"></i>Diagnóstico de Clonagem</h4>

    <div class="card border-0 shadow-sm mb-4 <?php echo $all_ok ? 'border-start border-success border-4' : 'border-start border-danger border-4'; ?>">
        <div class="card-body p-4"><div class="d-flex align-items-center">
            <div class="flex-shrink-0"><?php echo $all_ok ? '<i class="bi bi-check-circle-fill text-success fs-1"></i>' : '<i class="bi bi-exclamation-triangle-fill text-danger fs-1"></i>'; ?></div>
            <div class="ms-4">
                <h5 class="mb-1 fw-bold">Ambiente de Clonagem</h5>
                <p class="<?php echo $all_ok ? 'text-success' : 'text-danger'; ?> mb-0 fw-bold">
                    <?php echo $all_ok ? 'Tudo certo! O sistema está pronto.' : 'Problemas detectados: ' . implode(', ', $health_errors); ?>
                </p>
            </div>
        </div></div>
    </div>

    <div class="card border-0 shadow-sm mb-4"><div class="card-header bg-dark text-white text-uppercase small fw-bold">Fatos Técnicos</div><div class="card-body font-monospace small bg-light">
        <pre class="mb-0"><?php echo htmlspecialchars(json_encode($snap, JSON_PRETTY_PRINT)); ?></pre>
    </div></div>

    <div class="card border-0 shadow-sm mb-4"><div class="card-header">Controles</div><div class="card-body">
        <form method="post" class="d-inline">
            <input type="hidden" name="set_verbose" value="<?php echo @$_SESSION['clone_debug_verbose'] ? '0' : '1'; ?>">
            <button type="submit" class="btn btn-sm <?php echo @$_SESSION['clone_debug_verbose'] ? 'btn-warning' : 'btn-outline-primary'; ?>">Alternar Log Detalhado</button>
        </form>
        <form method="post" class="d-inline ms-2" onsubmit="return confirm('Limpar?');">
            <input type="hidden" name="clear_log" value="1"><button type="submit" class="btn btn-sm btn-outline-danger">Limpar Logs</button>
        </form>
    </div></div>
    </div></div>
</div>
<?php include 'admin_footer.php'; ?>
PHP;

if (@file_put_contents($target_file, $content_demo)) {
    echo "✅ admin/functions_demo.php atualizado!\n";
}
if (@file_put_contents($target_file_debug_lib, $content_debug_lib)) {
    echo "✅ admin/clone_debug.php atualizado!\n";
}
if (@file_put_contents($target_file_debug_ui, $content_debug_ui)) {
    echo "✅ admin/debug_clone_ambiente.php atualizado!\n";
}

if (function_exists('opcache_reset')) opcache_reset();
echo "\nSincronização concluída!";
