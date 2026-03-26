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

<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Seções Criadas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_secoes; ?></div>
                        </div>
                        <div class="col-auto"><i class="bi bi-folder-fill fs-2 text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total de Lançamentos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_lancamentos; ?></div>
                        </div>
                        <div class="col-auto"><i class="bi bi-file-earmark-text-fill fs-2 text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Páginas de Conteúdo</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_paginas; ?></div>
                        </div>
                        <div class="col-auto"><i class="bi bi-file-richtext-fill fs-2 text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Manifestações (Ouvidoria)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_ouvidoria; ?></div>
                        </div>
                        <div class="col-auto"><i class="bi bi-chat-left-quote-fill fs-2 text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow h-100">
                <div class="card-header">Situação das Manifestações (Ouvidoria)</div>
                <div class="card-body d-flex justify-content-center align-items-center">
                    <?php if ($total_ouvidoria > 0): ?>
                        <canvas id="ouvidoriaStatusChart"></canvas>
                    <?php else: ?>
                        <p class="text-muted">Sem dados da ouvidoria para exibir.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-7 mb-4">
            <div class="card shadow h-100">
                <div class="card-header">Lançamentos por Seção</div>
                <div class="card-body">
                    <?php if ($total_lancamentos > 0): ?>
                        <canvas id="lancamentosSecaoChart"></canvas>
                    <?php else: ?>
                        <p class="text-muted">Sem lançamentos para exibir no gráfico.</p>
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