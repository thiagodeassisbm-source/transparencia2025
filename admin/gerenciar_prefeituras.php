<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Bloqueia se não for superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

// 1. Busca lista de prefeituras
$stmt = $pdo->query("SELECT * FROM prefeituras ORDER BY criado_em DESC");
$prefeituras = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title_for_header = 'Gerenciar Prefeituras';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <!-- Cabeçalho de Ação -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-buildings-fill me-2 text-primary"></i> Gestão de Prefeituras</h4>
            <p class="text-muted small mb-0">Controle total de clientes, contratos e acessos SaaS</p>
        </div>
        <a href="cadastrar_prefeitura.php" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
            <i class="bi bi-plus-circle me-2"></i> Adicionar Prefeitura
        </a>
    </div>

    <!-- Tabela de Clientes -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light bg-opacity-50 border-bottom">
                        <tr>
                            <th class="ps-4 py-3 text-muted small text-uppercase">Prefeitura / Responsável</th>
                            <th class="py-3 text-muted small text-uppercase">Contrato / Venc.</th>
                            <th class="py-3 text-muted small text-uppercase">Valor Mensal</th>
                            <th class="py-3 text-muted small text-uppercase text-center">Status</th>
                            <th class="py-3 text-muted small text-uppercase text-center" style="width: 200px;">Gestão</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prefeituras as $pref): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm rounded-circle bg-light d-flex align-items-center justify-content-center me-3 border">
                                        <i class="bi bi-building text-primary fs-5"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($pref['nome']); ?></h6>
                                        <span class="text-muted small">Resp: <?php echo htmlspecialchars($pref['responsavel_nome'] ?? '---'); ?> (<?php echo htmlspecialchars($pref['responsavel_contato'] ?? '---'); ?>)</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-bold">Dia <?php echo $pref['dia_vencimento'] ?: '10'; ?></div>
                                <span class="text-muted small">Desde: <?php echo $pref['data_contratacao'] ? date('d/m/Y', strtotime($pref['data_contratacao'])) : '---'; ?></span>
                            </td>
                            <td>
                                <span class="fw-bold text-dark">R$ <?php echo number_format($pref['valor_mensalidade'], 2, ',', '.'); ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($pref['status'] == 'ativo'): ?>
                                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 border border-success-subtle">ATIVO</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2 border border-danger-subtle">SUSPENSO</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group shadow-sm rounded-pill overflow-hidden bg-white border">
                                    <a href="switch_pref.php?id=<?php echo $pref['id']; ?>" class="btn btn-white btn-sm px-3" title="Auditória">
                                        <i class="bi bi-search text-primary"></i>
                                    </a>
                                    <a href="editar_prefeitura.php?id=<?php echo $pref['id']; ?>" class="btn btn-white btn-sm px-3 border-start" title="Editar">
                                        <i class="bi bi-pencil-square text-dark"></i>
                                    </a>
                                    <?php if ($pref['status'] == 'ativo'): ?>
                                        <a href="alterar_status_pref.php?id=<?php echo $pref['id']; ?>&status=suspenso" class="btn btn-white btn-sm px-3 border-start" onclick="return confirm('Confirma bloqueio?')" title="Bloquear">
                                            <i class="bi bi-lock-fill text-warning"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="alterar_status_pref.php?id=<?php echo $pref['id']; ?>&status=ativo" class="btn btn-white btn-sm px-3 border-start" title="Desbloquear">
                                            <i class="bi bi-unlock-fill text-success"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="excluir_prefeitura.php?id=<?php echo $pref['id']; ?>" class="btn btn-white btn-sm px-3 border-start" onclick="return confirm('ATENÇÃO: Isso apagará TODOS os dados desta prefeitura (seções, usuários, registros, etc). Deseja continuar?')" title="Excluir Permanentemente">
                                        <i class="bi bi-trash3-fill text-danger"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($prefeituras)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Nenhuma prefeitura encontrada.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-sm { width: 40px; height: 40px; }
.btn-white:hover { background: #f8f9fa; }
.bg-success-subtle { background-color: rgba(25, 135, 84, 0.1) !important; color: #198754 !important; }
.bg-danger-subtle { background-color: rgba(220, 53, 69, 0.1) !important; color: #dc3545 !important; }
</style>

<?php include 'admin_footer.php'; ?>
