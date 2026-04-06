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
    LIMIT 300
");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title_for_header = 'Auditoria Global de Operações';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h4 class="mb-0 fw-bold"><i class="bi bi-activity text-primary me-2"></i> Monitoramento Global do SaaS</h4>
            <p class="text-muted small mb-0 px-1">Acompanhamento em tempo real de todas as ações realizadas em todas as prefeituras e na plataforma central.</p>
        </div>
        <div class="col-auto">
             <button onclick="window.location.reload()" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm"><i class="bi bi-arrow-clockwise me-2"></i> Atualizar Agora</button>
        </div>
    </div>

    <div class="card shadow-sm border-0 overflow-hidden rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-dark text-white border-0">
                    <tr>
                        <th class="ps-4" style="width: 100px;">Horário</th>
                        <th style="width: 200px;">Prefeitura</th>
                        <th style="width: 200px;">Usuário</th>
                        <th style="width: 130px;">Ação</th>
                        <th style="width: 200px;">Módulo/Tabela</th>
                        <th>O que foi feito / Detalhes</th>
                        <th style="width: 130px;">IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="bi bi-inbox-fill display-4 text-light"></i>
                            <p class="mt-2 text-muted">Nenhum rastro encontrado nas últimas atividades.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($logs as $log): ?>
                    <tr class="transition-all hover-light">
                        <td class="ps-4 py-3">
                            <div class="text-dark fw-bold" style="font-size: 13px;"><?php echo date('H:i:s', strtotime($log['horario'])); ?></div>
                            <div class="text-muted" style="font-size: 11px;"><?php echo date('d/m/Y', strtotime($log['horario'])); ?></div>
                        </td>
                        <td>
                            <?php if (!$log['id_prefeitura'] || $log['id_prefeitura'] == 0): ?>
                                <span class="badge bg-dark rounded-pill border border-dark px-3 py-2 small" style="letter-spacing: 0.5px;">
                                    <i class="bi bi-grid-fill me-1 small"></i> PLATAFORMA
                                </span>
                            <?php else: ?>
                                <div class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 small">
                                    <i class="bi bi-building me-1 small"></i> <?php echo htmlspecialchars($log['prefeitura_nome'] ?: 'Desconhecida'); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle-sm me-2 bg-light text-secondary small border shadow-sm">
                                    <?php echo strtoupper(substr($log['usuario_nome'], 0, 1)); ?>
                                </div>
                                <span class="fw-bold text-secondary" style="font-size: 13px;"><?php echo htmlspecialchars($log['usuario_nome']); ?></span>
                            </div>
                        </td>
                        <td>
                            <?php 
                            $badge_class = 'bg-secondary';
                            if ($log['acao'] == 'ADIÇÃO') $badge_class = 'bg-success';
                            if ($log['acao'] == 'EDIÇÃO') $badge_class = 'bg-warning text-dark';
                            if ($log['acao'] == 'EXCLUSÃO') $badge_class = 'bg-danger';
                            if ($log['acao'] == 'LOGIN') $badge_class = 'bg-info text-dark';
                            if ($log['acao'] == 'LOGOUT') $badge_class = 'bg-dark';
                            if ($log['acao'] == 'CONFIG') $badge_class = 'bg-primary';
                            ?>
                            <span class="badge <?php echo $badge_class; ?> rounded-pill shadow-xs" style="font-size: 0.65rem; padding: 0.4em 0.8em;"><?php echo $log['acao']; ?></span>
                        </td>
                        <td>
                            <span class="text-uppercase fw-600 small px-2 py-1 bg-light rounded-3 text-dark border" style="font-size: 11px; letter-spacing: 0.3px;">
                                <?php echo htmlspecialchars($log['tabela'] ?: 'SISTEMA'); ?>
                            </span>
                        </td>
                        <td class="text-wrap" style="min-width: 300px;">
                            <p class="mb-0 text-dark small" style="line-height: 1.5;"><?php echo nl2br(htmlspecialchars($log['detalhes'])); ?></p>
                        </td>
                        <td class="small font-monospace text-muted py-2" style="font-size: 11px;">
                            <i class="bi bi-pc-display me-1 opacity-75"></i> <?php echo $log['ip_endereco']; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.avatar-circle-sm {
    width: 25px;
    height: 25px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.7rem;
}
.table thead th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding: 1.2rem 0.75rem;
    font-weight: 700;
}
.table tbody td {
    padding: 1.2rem 0.75rem;
}
.bg-primary-subtle { background-color: rgba(13, 110, 253, 0.08) !important; color: #0d6efd !important; }
.bg-light-subtle { background-color: #f8f9fa !important; }
.fw-600 { font-weight: 600; }
.transition-all { transition: all 0.2s ease; }
.hover-light:hover { background-color: #fcfcfc !important; }
.shadow-xs { box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
</style>

<?php include 'admin_footer.php'; ?>
