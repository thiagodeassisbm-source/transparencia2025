<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Bloqueia se não for superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

// 1. Busca a lista de recursos
$stmt = $pdo->query("SELECT * FROM landing_recursos ORDER BY ordem ASC, id DESC");
$recursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title_for_header = 'Gerenciar Recursos da Landing Page';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-window-stack me-2 text-primary"></i> Recursos do Sistema</h4>
            <p class="text-muted small mb-0">Gerencie os recursos que aparecem na página principal institucional.</p>
        </div>
        <a href="editar_landing_recurso.php" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
            <i class="bi bi-plus-circle me-2"></i> Adicionar Recurso
        </a>
    </div>

    <!-- Tabela de Recursos -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light bg-opacity-50 border-bottom">
                        <tr>
                            <th class="ps-4 py-3 text-muted small text-uppercase" style="width: 80px;">Ícone</th>
                            <th class="py-3 text-muted small text-uppercase">Título</th>
                            <th class="py-3 text-muted small text-uppercase">Descrição</th>
                            <th class="py-3 text-muted small text-uppercase text-center">Ordem</th>
                            <th class="py-3 text-muted small text-uppercase text-center" style="width: 150px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recursos as $r): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                    <i class="bi <?php echo htmlspecialchars($r['icone']); ?> fs-5"></i>
                                </div>
                            </td>
                            <td><h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($r['titulo']); ?></h6></td>
                            <td>
                                <div class="text-muted small text-truncate" style="max-width: 400px;">
                                    <?php echo htmlspecialchars($r['descricao']); ?>
                                </div>
                            </td>
                            <td class="text-center"><span class="badge bg-light text-dark border"><?php echo $r['ordem']; ?></span></td>
                            <td class="text-center pe-4">
                                <div class="btn-group shadow-sm rounded-pill overflow-hidden bg-white border">
                                    <a href="editar_landing_recurso.php?id=<?php echo $r['id']; ?>" class="btn btn-white btn-sm px-3" title="Editar">
                                        <i class="bi bi-pencil-square text-dark"></i>
                                    </a>
                                    <a href="excluir_landing_recurso.php?id=<?php echo $r['id']; ?>" class="btn btn-white btn-sm px-3 border-start" onclick="return confirm('Excluir permanentemente este recurso?')" title="Excluir">
                                        <i class="bi bi-trash text-danger"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recursos)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Nenhum recurso cadastrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.btn-white:hover { background: #f8f9fa; }
</style>

<?php include 'admin_footer.php'; ?>
