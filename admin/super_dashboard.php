<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Bloqueia se não for superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

// 1. Métricas Gerais
$total_clientes = $pdo->query("SELECT COUNT(*) FROM prefeituras")->fetchColumn();
$receita_mensal = $pdo->query("SELECT SUM(valor_mensalidade) FROM prefeituras WHERE status = 'ativo'")->fetchColumn() ?: 0;

// 2. Recebíveis do mês (todos os vencimentos das prefeituras ativas)
$stmt_venc = $pdo->query("SELECT nome, dia_vencimento, valor_mensalidade FROM prefeituras WHERE status = 'ativo' ORDER BY dia_vencimento ASC, nome ASC");
$vencimentos = $stmt_venc->fetchAll();

// 3. Volume de Dados por Prefeitura (Contagem de lançamentos/registros)
$stmt_dados = $pdo->query("
    SELECT p.nome, COUNT(r.id) as total_arquivos 
    FROM prefeituras p 
    LEFT JOIN portais pt ON p.id = pt.id_prefeitura 
    LEFT JOIN registros r ON pt.id = r.id_portal 
    GROUP BY p.id 
    ORDER BY total_arquivos DESC 
    LIMIT 5
");
$volume_dados = $stmt_dados->fetchAll();

$page_title_for_header = 'Dashboard SaaS';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <h4 class="fw-bold text-dark mt-2">Dashboard de Gestão de Prefeituras</h4>
    </div>

    <!-- Row de Stats Principais -->
    <div class="row g-4 mb-5">
        <div class="col-md-6 col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-4 me-3">
                        <i class="bi bi-cash-stack fs-3 text-primary"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small text-uppercase mb-1 fw-bold">Receita (MRR)</h6>
                        <h3 class="mb-0 fw-bold">R$ <?php echo number_format($receita_mensal, 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-info bg-opacity-10 p-3 rounded-4 me-3">
                        <i class="bi bi-buildings-fill fs-3 text-info"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small text-uppercase mb-1 fw-bold">Prefeituras</h6>
                        <h3 class="mb-0 fw-bold"><?php echo $total_clientes; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recebíveis do Mês -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-calendar-check me-2 text-warning"></i> Recebíveis do Mês (todas as prefeituras)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small">
                                <tr>
                                    <th class="ps-4">Prefeitura</th>
                                    <th>Dia</th>
                                    <th class="pe-4 text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vencimentos as $venc): ?>
                                <tr>
                                    <td class="ps-4 py-3 fw-bold"><?php echo htmlspecialchars($venc['nome']); ?></td>
                                    <td><span class="badge bg-warning-subtle text-warning">Dia <?php echo $venc['dia_vencimento']; ?></span></td>
                                    <td class="pe-4 text-end fw-bold">R$ <?php echo number_format($venc['valor_mensalidade'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($vencimentos)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted small">Nenhuma prefeitura ativa com vencimento cadastrado.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Volume de Dados -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-graph-up-arrow me-2 text-success"></i> Volume de Dados Cadastrados</h6>
                </div>
                <div class="card-body">
                    <?php foreach ($volume_dados as $dado): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="fw-bold"><?php echo htmlspecialchars($dado['nome']); ?></span>
                            <span class="text-muted"><?php echo $dado['total_arquivos']; ?> itens</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <?php 
                                $max = $volume_dados[0]['total_arquivos'] ?: 1;
                                $percent = ($dado['total_arquivos'] / $max) * 100;
                            ?>
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
