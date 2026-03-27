<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Bloqueia se não for superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

// Busca logs de todas as prefeituras, incluindo o nome da prefeitura
$stmt = $pdo->prepare("
    SELECT l.*, p.nome as prefeitura_nome 
    FROM logs_sistema l
    LEFT JOIN prefeituras p ON l.id_prefeitura = p.id
    ORDER BY l.horario DESC 
    LIMIT 200
");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title_for_header = 'Auditoria Global de Operações';
include 'admin_header.php';
?>

<div class="super-logs-container px-3">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-4 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold"><i class="bi bi-activity text-primary me-2"></i> Monitoramento do SaaS</h5>
                <p class="text-muted small mb-0">Últimas 200 atividades registradas em todas as instâncias</p>
            </div>
            <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Atualizar
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Data/Hora</th>
                            <th>Prefeitura</th>
                            <th>Módulo</th>
                            <th>Ação</th>
                            <th>Descrição / Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr class="small">
                            <td class="ps-4 text-nowrap"><?php echo date('d/m/Y H:i', strtotime($log['horario'])); ?></td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary py-2 px-3 rounded-pill border border-primary-subtle">
                                    <?php echo htmlspecialchars($log['prefeitura_nome'] ?? 'PLATAFORMA'); ?>
                                </span>
                            </td>
                            <td><span class="text-muted small fw-bold text-uppercase"><?php echo htmlspecialchars($log['tabela'] ?? '---'); ?></span></td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary"><?php echo htmlspecialchars($log['acao']); ?></span>
                            </td>
                            <td class="text-wrap" style="max-width: 300px;">
                                <span class="text-dark"><?php echo htmlspecialchars($log['detalhes'] ?? '---'); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">Nenhum log registrado ainda.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.super-logs-container { max-width: 1400px; margin: 0 auto; }
.bg-primary-subtle { background-color: rgba(99, 102, 241, 0.1) !important; color: #4f46e5 !important; }
</style>

<?php include 'admin_footer.php'; ?>
