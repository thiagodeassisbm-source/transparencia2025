<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// --- BUSCA DE DADOS PARA OS CARDS NUMÉRICOS ---
$total_secoes = $pdo->query("SELECT COUNT(id) FROM portais")->fetchColumn();
$total_lancamentos = $pdo->query("SELECT COUNT(id) FROM registros")->fetchColumn();
$total_paginas = $pdo->query("SELECT COUNT(id) FROM paginas")->fetchColumn();
$total_ouvidoria = $pdo->query("SELECT COUNT(id) FROM ouvidoria_manifestacoes")->fetchColumn();

// --- DADOS PARA O GRÁfico DE STATUS DA OUVIDORIA ---
$stats_status_raw = $pdo->query(
    "SELECT status, COUNT(id) as total 
     FROM ouvidoria_manifestacoes 
     GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$stats_status = [];
$status_possiveis = ['Recebida', 'Em Análise', 'Respondida', 'Finalizada'];
foreach ($status_possiveis as $status) {
    $stats_status[$status] = $stats_status_raw[$status] ?? 0;
}


// --- DADOS PARA O GRÁFICO DE LANÇAMENTOS POR SEÇÃO ---
$stmt_lanc_secao = $pdo->query(
    "SELECT p.nome, COUNT(r.id) as total
     FROM registros r
     JOIN portais p ON r.id_portal = p.id
     GROUP BY p.nome
     ORDER BY total DESC
     LIMIT 7" // Pega as 7 seções com mais lançamentos
);
$lancamentos_por_secao = $stmt_lanc_secao->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Painel Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light-subtle">

<?php 
$page_title_for_header = 'Dashboard'; 
include 'admin_header.php'; 
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="stat-icon bg-light-blue">
                    <i class="bi bi-folder2-open"></i>
                </div>
                <div class="stat-info">
                    <p>Seções Criadas</p>
                    <h3><?php echo $total_secoes; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="stat-icon bg-light-green">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div class="stat-info">
                    <p>Total Lançamentos</p>
                    <h3><?php echo $total_lancamentos; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="stat-icon bg-light-purple">
                    <i class="bi bi-file-richtext"></i>
                </div>
                <div class="stat-info">
                    <p>Páginas de Conteúdo</p>
                    <h3><?php echo $total_paginas; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="stat-icon bg-light-yellow">
                    <i class="bi bi-chat-left-quote"></i>
                </div>
                <div class="stat-info">
                    <p>Ouvidoria (Total)</p>
                    <h3><?php echo $total_ouvidoria; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header border-0 bg-white pt-4 px-4">
                    <h5 class="mb-0 fw-bold">Situação da Ouvidoria</h5>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center p-4">
                    <?php if ($total_ouvidoria > 0): ?>
                        <div style="height: 300px; width: 100%;">
                            <canvas id="ouvidoriaStatusChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox-fill display-4 text-light"></i>
                            <p class="text-muted mt-2">Sem dados para exibir.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header border-0 bg-white pt-4 px-4">
                    <h5 class="mb-0 fw-bold">Lançamentos Recentes por Seção</h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($total_lancamentos > 0): ?>
                        <div style="height: 300px; width: 100%;">
                            <canvas id="lancamentosSecaoChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-bar-chart-fill display-4 text-light"></i>
                            <p class="text-muted mt-2">Sem lançamentos para exibir.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<?php include 'admin_footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de Status da Ouvidoria
    <?php if ($total_ouvidoria > 0 && !empty($stats_status)): ?>
    const ctxStatus = document.getElementById('ouvidoriaStatusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($stats_status)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($stats_status)); ?>,
                backgroundColor: ['#ffc107', '#fd7e14', '#198754', '#6c757d'],
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
    <?php endif; ?>

    // Gráfico de Lançamentos por Seção
    <?php if ($total_lancamentos > 0 && !empty($lancamentos_por_secao)): ?>
    const ctxLancamentos = document.getElementById('lancamentosSecaoChart').getContext('2d');
    new Chart(ctxLancamentos, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($lancamentos_por_secao, 'nome')); ?>,
            datasets: [{
                label: 'Nº de Lançamentos',
                data: <?php echo json_encode(array_column($lancamentos_por_secao, 'total')); ?>,
                backgroundColor: 'rgba(13, 110, 253, 0.7)'
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
    <?php endif; ?>
});
</script>
</body>
</html>