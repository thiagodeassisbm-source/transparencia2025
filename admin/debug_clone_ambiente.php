<?php
/**
 * Diagnóstico objetivo: qual arquivo PHP está no disco, qual BD, quais colunas, OPcache, último erro de clone.
 * Acesso: somente superadmin.
 */
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'includes/clone_debug.php';

if (!isset($_SESSION['is_superadmin']) || (int) $_SESSION['is_superadmin'] !== 1) {
    header('Location: dashboard.php');
    exit;
}

$tmp = clone_debug_tmp_dir();
$logFile = $tmp . '/clone_debug.log';
$lastJson = $tmp . '/clone_last_result.json';
$flagFile = clone_debug_base_dir() . '/.clone_debug';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_verbose_session'])) {
        $_SESSION['clone_debug_verbose'] = (int) $_POST['set_verbose_session'] === 1 ? 1 : 0;
    }
    if (isset($_POST['touch_flag'])) {
        if ((int) $_POST['touch_flag'] === 1) {
            @file_put_contents($flagFile, date('c') . "\n");
        } else {
            @unlink($flagFile);
        }
    }
    if (isset($_POST['clear_log']) && (int) $_POST['clear_log'] === 1) {
        @unlink($logFile);
    }
    header('Location: debug_clone_ambiente.php');
    exit;
}

$page_title_for_header = 'Debug — Clonagem / Ambiente';
include 'admin_header.php';

$snap = clone_debug_snapshot_runtime($pdo, true);

$tables = ['categorias', 'portais', 'cards_informativos', 'campos_portal'];
$describe = [];
foreach ($tables as $t) {
    try {
        $st = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $t) . '`');
        $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
        $describe[$t] = ['ok' => true, 'rows' => $rows];
    } catch (Throwable $e) {
        $describe[$t] = ['ok' => false, 'error' => $e->getMessage()];
    }
}

$lastContent = is_readable($lastJson) ? file_get_contents($lastJson) : '(arquivo inexistente)';
$logTail = is_readable($logFile) ? file_get_contents($logFile) : '(log vazio ou inexistente)';
if (strlen($logTail) > 12000) {
    $logTail = '… (últimos 12000 caracteres) …' . "\n" . substr($logTail, -12000);
}

$verboseSession = (int) ($_SESSION['clone_debug_verbose'] ?? 0) === 1;
$flagExists = is_file($flagFile);
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <h4 class="fw-bold mb-3"><i class="bi bi-bug me-2"></i>Debug — clonagem de prefeitura (fatos, não suposições)</h4>
            <p class="text-muted small">Use isto para provar qual código está em disco, qual banco está conectado e qual SQL/colunas o PHP enxerga.</p>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-dark text-white">Arquivos PHP (fingerprint)</div>
                <div class="card-body small font-monospace">
                    <p class="mb-2"><strong>functions_demo.php</strong></p>
                    <pre class="bg-light p-2 rounded small mb-3"><?php echo htmlspecialchars(json_encode($snap['functions_demo'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                    <p class="mb-2"><strong>cadastrar_prefeitura.php</strong></p>
                    <pre class="bg-light p-2 rounded small"><?php echo htmlspecialchars(json_encode($snap['cadastrar_prefeitura'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">Conexão e OPcache</div>
                <div class="card-body small">
                    <p><strong>DATABASE():</strong> <?php echo htmlspecialchars((string) ($snap['database'] ?? '')); ?></p>
                    <p><strong>PHP:</strong> <?php echo htmlspecialchars(PHP_VERSION); ?></p>
                    <p><strong>SCRIPT_FILENAME (esta página):</strong> <code><?php echo htmlspecialchars((string) ($snap['script_filename'] ?? '')); ?></code></p>
                    <p><strong>OPcache:</strong> <?php echo $snap['opcache']['available'] ? ('habilitado=' . ($snap['opcache']['enabled'] ? 'sim' : 'não')) : 'indisponível'; ?></p>
                    <?php if (!empty($snap['opcache_scripts_functions_demo'])): ?>
                        <p class="mb-0"><strong>Scripts OPcache contendo functions_demo:</strong></p>
                        <pre class="bg-light p-2 rounded small mt-1"><?php echo htmlspecialchars(json_encode($snap['opcache_scripts_functions_demo'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                    <?php endif; ?>
                    <p class="text-muted small mb-0 mt-2">Se você alterar o PHP e o MD5 acima não mudar após salvar no servidor, o OPcache ou outro cache de código pode estar servindo arquivo antigo — reinicie PHP-FPM/Apache ou invalide OPcache.</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">SHOW COLUMNS (o que o PDO vê no BD atual)</div>
                <div class="card-body">
                    <?php foreach ($describe as $tbl => $info): ?>
                        <h6 class="fw-bold"><?php echo htmlspecialchars($tbl); ?></h6>
                        <?php if ($info['ok']): ?>
                            <table class="table table-sm table-bordered small mb-4">
                                <thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr></thead>
                                <tbody>
<?php foreach ($info['rows'] as $r): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($r['Field'] ?? ''); ?></code></td>
                                        <td><?php echo htmlspecialchars($r['Type'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['Null'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['Key'] ?? ''); ?></td>
                                    </tr>
<?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-danger small"><?php echo htmlspecialchars($info['error']); ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">Controles de log</div>
                <div class="card-body">
                    <p class="small">Log detalhado: <code>tmp/clone_debug.log</code> · Flag persistente: <code>admin/.clone_debug</code> (log sem precisar de sessão)</p>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="set_verbose_session" value="<?php echo $verboseSession ? '0' : '1'; ?>">
                        <button type="submit" class="btn btn-sm <?php echo $verboseSession ? 'btn-warning' : 'btn-outline-primary'; ?>">
                            <?php echo $verboseSession ? 'Desativar' : 'Ativar'; ?> verbose na sessão
                        </button>
                    </form>
                    <form method="post" class="d-inline ms-2">
                        <input type="hidden" name="touch_flag" value="<?php echo $flagExists ? '0' : '1'; ?>">
                        <button type="submit" class="btn btn-sm <?php echo $flagExists ? 'btn-warning' : 'btn-outline-secondary'; ?>">
                            <?php echo $flagExists ? 'Remover' : 'Criar'; ?> arquivo <code>.clone_debug</code>
                        </button>
                    </form>
                    <form method="post" class="d-inline ms-2" onsubmit="return confirm('Limpar log?');">
                        <input type="hidden" name="clear_log" value="1">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Limpar clone_debug.log</button>
                    </form>
                    <p class="small mt-2 mb-0">Com verbose ativo, tente cadastrar de novo e volte aqui; o log mostrará colunas e o SQL da primeira linha de card.</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">Último resultado (clone_last_result.json)</div>
                <div class="card-body">
                    <pre class="small bg-light p-3 rounded" style="max-height: 420px; overflow-y: auto;"><?php echo htmlspecialchars($lastContent); ?></pre>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">clone_debug.log (tail)</div>
                <div class="card-body">
                    <pre class="small bg-light p-3 rounded" style="max-height: 420px; overflow-y: auto;"><?php echo htmlspecialchars($logTail); ?></pre>
                </div>
            </div>

            <p class="small text-muted"><a href="cadastrar_prefeitura.php">← Voltar ao cadastro</a> · <a href="cadastrar_prefeitura.php?debug_clone=1">Cadastro com debug na URL</a></p>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
