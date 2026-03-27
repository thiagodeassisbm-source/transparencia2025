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

// 2. Busca lista de prefeituras com o título configurado se existir
$stmt = $pdo->query("
    SELECT p.*, 
    (SELECT valor FROM configuracoes WHERE chave = 'prefeitura_titulo' AND id_prefeitura = p.id LIMIT 1) as titulo_config 
    FROM prefeituras p 
    ORDER BY p.criado_em DESC
");
$prefeituras = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title_for_header = 'Gestão de Prefeituras SaaS';
include 'admin_header.php';
?>

<div class="super-dashboard px-4">
    <!-- Header com Stats -->
    <div class="row mb-5 g-4 mt-2">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded-4 me-4">
                        <i class="bi bi-buildings-fill fs-3 text-primary"></i>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase small mb-1 ls-1">Total de Prefeituras</h6>
                        <h2 class="mb-0 fw-bold"><?php echo $total_clientes; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="flex-shrink-0 bg-success bg-opacity-10 p-3 rounded-4 me-4">
                        <i class="bi bi-currency-dollar fs-3 text-success"></i>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase small mb-1 ls-1">Receita Mensal (MRR)</h6>
                        <h2 class="mb-0 fw-bold">R$ <?php echo number_format($receita_mensal, 2, ',', '.'); ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-center">
            <a href="cadastrar_prefeitura.php" class="btn btn-primary btn-lg rounded-pill h-100 d-flex flex-column justify-content-center align-items-center w-100 border-0 shadow" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);">
                <i class="bi bi-plus-circle-fill fs-2 mb-2"></i>
                <span class="fw-bold">Novo Cliente / Prefeitura</span>
            </a>
        </div>
    </div>

    <!-- Lista de Clientes -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-dark">Gestão de Prefeituras</h5>
                <p class="text-muted small mb-0">Controle de contratos e dados financeiros por município</p>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light bg-opacity-50">
                        <tr>
                            <th class="ps-4 py-3 text-muted small text-uppercase">Prefeitura / Responsável</th>
                            <th class="py-3 text-muted small text-uppercase">Contrato / Venc.</th>
                            <th class="py-3 text-muted small text-uppercase">Valor Mensal</th>
                            <th class="py-3 text-muted small text-uppercase text-center">Status</th>
                            <th class="py-3 text-muted small text-uppercase text-center pe-4" style="width: 220px;">Ações de Gestão</th>
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
                                        <?php 
                                            $exibir_nome = $pref['titulo_config'] ?: $pref['nome'];
                                            if (stripos($exibir_nome, 'Prefeitura') === false) {
                                                $exibir_nome = 'Prefeitura de ' . $exibir_nome;
                                            }
                                        ?>
                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($exibir_nome); ?></h6>
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
                                <?php elseif ($pref['status'] == 'pendente_pagamento'): ?>
                                    <span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-2 border border-warning-subtle">PENDENTE</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2 border border-danger-subtle">SUSPENSO</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group shadow-sm rounded-pill overflow-hidden bg-white border">
                                    <a href="switch_pref.php?id=<?php echo $pref['id']; ?>" class="btn btn-white btn-sm px-3" title="Auditória Direta">
                                        <i class="bi bi-search text-primary"></i>
                                    </a>
                                    <a href="editar_prefeitura.php?id=<?php echo $pref['id']; ?>" class="btn btn-white btn-sm px-3 border-start">
                                        <i class="bi bi-pencil-square text-dark"></i>
                                    </a>
                                    <?php if ($pref['status'] == 'ativo'): ?>
                                        <a href="alterar_status_pref.php?id=<?php echo $pref['id']; ?>&status=suspenso" class="btn btn-white btn-sm px-3 border-start" onclick="return confirm('Confirmar suspensão de acesso?')" title="Bloquear">
                                            <i class="bi bi-lock-fill text-danger"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="alterar_status_pref.php?id=<?php echo $pref['id']; ?>&status=ativo" class="btn btn-white btn-sm px-3 border-start" title="Desbloquear">
                                            <i class="bi bi-unlock-fill text-success"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($prefeituras)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Nenhum cliente cadastrado. Comece agora!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.super-dashboard { max-width: 1400px; margin: 0 auto; }
.avatar-sm { width: 42px; height: 42px; }
.ls-1 { letter-spacing: 0.05em; }
.btn-white:hover { background: #f8f9fa; }
.bg-success-subtle { background-color: rgba(25, 135, 84, 0.1) !important; color: #198754 !important; }
.bg-warning-subtle { background-color: rgba(255, 193, 7, 0.1) !important; color: #997404 !important; }
.bg-danger-subtle { background-color: rgba(220, 53, 69, 0.1) !important; color: #dc3545 !important; }
</style>

<?php include 'admin_footer.php'; ?>
