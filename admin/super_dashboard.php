<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Bloqueia se não for superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

// 1. Busca estatísticas globais
$total_clientes = $pdo->query("SELECT COUNT(*) FROM prefeituras")->fetchColumn();
$receita_mensal = $pdo->query("SELECT SUM(valor_mensalidade) FROM prefeituras WHERE status = 'ativo'")->fetchColumn() ?: 0;

// 2. Busca lista de prefeituras
$stmt = $pdo->query("SELECT * FROM prefeituras ORDER BY criado_em DESC");
$prefeituras = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title_for_header = 'Central de Clientes';
include 'admin_header.php';
?>

<div class="super-dashboard">
    <!-- Header com Stats -->
    <div class="row mb-5 g-4">
        <div class="col-md-4">
            <div class="card bg-dark text-white border-0 shadow-sm overflow-hidden h-100 p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 text-uppercase small ls-1">Total de Clientes</h6>
                        <h2 class="mb-0 fw-bold display-6"><?php echo $total_clientes; ?></h2>
                    </div>
                    <div class="stats-icon-bg opacity-25">
                        <i class="bi bi-buildings-fill fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white border-0 shadow-sm overflow-hidden h-100 p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 text-uppercase small ls-1">Receita Mensal (MRR)</h6>
                        <h2 class="mb-0 fw-bold display-6">R$ <?php echo number_format($receita_mensal, 2, ',', '.'); ?></h2>
                    </div>
                    <div class="stats-icon-bg opacity-25">
                        <i class="bi bi-currency-dollar fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white border-0 shadow-sm overflow-hidden h-100 p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 text-uppercase small ls-1">Status da Plataforma</h6>
                        <h2 class="mb-0 fw-bold h4">SISTEMA ONLINE</h2>
                    </div>
                    <div class="stats-icon-bg opacity-25 text-white">
                        <i class="bi bi-shield-check fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Clientes -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-dark">Gestão de Prefeituras</h5>
                <p class="text-muted small mb-0">Controle de contratos, pagamentos e acessos</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalNovoCliente">
                <i class="bi bi-plus-lg me-2"></i> Novo Cliente
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light bg-opacity-50">
                        <tr>
                            <th class="ps-4 py-3 border-0 text-muted small text-uppercase">Prefeitura</th>
                            <th class="py-3 border-0 text-muted small text-uppercase">Contrato</th>
                            <th class="py-3 border-0 text-muted small text-uppercase">Valor</th>
                            <th class="py-3 border-0 text-muted small text-uppercase">Últ. Pagamento</th>
                            <th class="py-3 border-0 text-muted small text-uppercase text-center">Status</th>
                            <th class="py-3 border-0 text-muted small text-uppercase text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prefeituras as $pref): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm rounded-circle bg-light d-flex align-items-center justify-content-center me-3 border">
                                        <i class="bi bi-building text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($pref['nome']); ?></h6>
                                        <span class="text-muted small">/<?php echo $pref['slug']; ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="small"><?php echo $pref['data_contratacao'] ? date('d/m/Y', strtotime($pref['data_contratacao'])) : '---'; ?></span>
                            </td>
                            <td>
                                <span class="fw-bold">R$ <?php echo number_format($pref['valor_mensalidade'], 2, ',', '.'); ?></span>
                            </td>
                            <td>
                                <span class="small text-muted"><?php echo $pref['data_ultimo_pagamento'] ? date('d/m/Y', strtotime($pref['data_ultimo_pagamento'])) : 'Pendente'; ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($pref['status'] == 'ativo'): ?>
                                    <span class="badge bg-success-subtle text-success rounded-pill px-3">Ativo</span>
                                <?php elseif ($pref['status'] == 'pendente_pagamento'): ?>
                                    <span class="badge bg-warning-subtle text-warning rounded-pill px-3">Inadimplente</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3">Suspenso</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group shadow-sm rounded-pill overflow-hidden bg-white border">
                                    <a href="switch_pref.php?id=<?php echo $pref['id']; ?>" class="btn btn-white btn-sm px-3" title="Acessar Painel">
                                        <i class="bi bi-box-arrow-in-right text-primary"></i>
                                    </a>
                                    <button class="btn btn-white btn-sm px-3" data-bs-toggle="modal" data-bs-target="#editPref_<?php echo $pref['id']; ?>" title="Editar">
                                        <i class="bi bi-pencil-square text-muted"></i>
                                    </button>
                                    <?php if ($pref['status'] == 'ativo'): ?>
                                        <a href="alterar_status_pref.php?id=<?php echo $pref['id']; ?>&status=suspenso" class="btn btn-white btn-sm px-3" onclick="return confirm('Confirmar suspensão de acesso?')">
                                            <i class="bi bi-lock-fill text-danger"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="alterar_status_pref.php?id=<?php echo $pref['id']; ?>&status=ativo" class="btn btn-white btn-sm px-3">
                                            <i class="bi bi-unlock-fill text-success"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.super-dashboard { max-width: 1400px; margin: 0 auto; }
.avatar-sm { width: 40px; height: 40px; }
.ls-1 { letter-spacing: 0.1em; }
.btn-white:hover { background: #f8f9fa; }
.bg-primary { background: linear-gradient(45deg, #4f46e5, #6366f1) !important; }
.bg-dark { background: #1e293b !important; }
</style>

<?php include 'admin_footer.php'; ?>
