<?php
require_once 'conexao.php';
$page_title = "Relatório da Ouvidoria";

// Define o ano para o filtro. Padrão: ano atual.
$ano_selecionado = $_GET['exercicio'] ?? date('Y');

// --- LISTA DE ASSUNTOS CONSIDERADOS COMO SECRETARIAS ---
$lista_secretarias = [
    "Secretaria de Saúde", "Secretaria de Habitação", "Secretaria de Políticas para Mulheres",
    "Secretaria de Assistência Social e Direitos Humanos", "Secretaria de Cultura", "Secretaria de Esporte e Lazer",
    "Secretaria de Educação", "Secretaria de Eficiência", "Secretaria de Inovação e Transformação Digital",
    "Secretaria Municipal de Desenvolvimento"
];

// --- LÓGICA PARA BUSCAR OS DADOS ---
$stats_tipo = []; $stats_status = []; $stats_recorrentes = []; $stats_secretaria = []; $total_ano = 0;

try {
    // 1. Contagem por TIPO de manifestação
    $stmt_tipo = $pdo->prepare("SELECT tipo_manifestacao, COUNT(id) as total FROM ouvidoria_manifestacoes WHERE YEAR(data_criacao) = ? GROUP BY tipo_manifestacao");
    $stmt_tipo->execute([$ano_selecionado]);
    $stats_tipo_raw = $stmt_tipo->fetchAll(PDO::FETCH_KEY_PAIR);

    // 2. Contagem por STATUS
    $stmt_status = $pdo->prepare("SELECT status, COUNT(id) as total FROM ouvidoria_manifestacoes WHERE YEAR(data_criacao) = ? GROUP BY status");
    $stmt_status->execute([$ano_selecionado]);
    $stats_status_raw = $stmt_status->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // 3. Contagem de Manifestações APENAS PARA SECRETARIAS
    if (!empty($lista_secretarias)) {
        $placeholders_sec = implode(',', array_fill(0, count($lista_secretarias), '?'));
        $stmt_secretaria = $pdo->prepare(
            "SELECT assunto, COUNT(id) AS total 
             FROM ouvidoria_manifestacoes 
             WHERE YEAR(data_criacao) = ? AND assunto IN ($placeholders_sec)
             GROUP BY assunto ORDER BY total DESC"
        );
        $params_sec = array_merge([$ano_selecionado], $lista_secretarias);
        $stmt_secretaria->execute($params_sec);
        $stats_secretaria = $stmt_secretaria->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. Top 10 Assuntos Recorrentes (QUE NÃO SÃO SECRETARIAS)
    if (!empty($lista_secretarias)) {
        $placeholders_rec = implode(',', array_fill(0, count($lista_secretarias), '?'));
        $stmt_recorrentes = $pdo->prepare(
            "SELECT assunto, COUNT(id) AS total,
                (SELECT tipo_manifestacao FROM ouvidoria_manifestacoes sub WHERE sub.assunto = main.assunto AND YEAR(sub.data_criacao) = ? GROUP BY tipo_manifestacao ORDER BY COUNT(id) DESC LIMIT 1) AS tipo_dominante
             FROM ouvidoria_manifestacoes main WHERE YEAR(data_criacao) = ? AND assunto NOT IN ($placeholders_rec)
             GROUP BY assunto HAVING COUNT(id) > 1 ORDER BY total DESC LIMIT 10"
        );
        $params_recorrentes = array_merge([$ano_selecionado, $ano_selecionado], $lista_secretarias);
        $stmt_recorrentes->execute($params_recorrentes);
        $stats_recorrentes = $stmt_recorrentes->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 5. Organiza dados e calcula total
    $tipos_possiveis = ['Elogio', 'Sugestão', 'Reclamação', 'Denúncia', 'Solicitação'];
    foreach ($tipos_possiveis as $tipo) { $stats_tipo[$tipo] = $stats_tipo_raw[$tipo] ?? 0; }
    $status_possiveis = ['Recebida', 'Em Análise', 'Respondida', 'Finalizada'];
    foreach ($status_possiveis as $status) { $stats_status[$status] = $stats_status_raw[$status] ?? 0; }
    $total_ano = array_sum($stats_tipo);

} catch (Exception $e) {
    // Zera os dados em caso de erro
    $stats_tipo = []; $stats_status = []; $stats_secretaria = []; $stats_recorrentes = []; $total_ano = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - Portal da Transparência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-legend-color { display: inline-block; width: 12px; height: 12px; border-radius: 3px; margin-right: 6px; vertical-align: middle; }
    </style>
</head>
<body class="bg-light-subtle">

<header class="page-header">
    <div class="container-fluid container-custom-padding">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                <li class="breadcrumb-item"><a href="ouvidoria.php">Ouvidoria</a></li>
                <li class="breadcrumb-item active" aria-current="page">Relatório Estatístico</li>
            </ol>
        </nav>
        <h1>Relatório Estatístico da Ouvidoria</h1>
    </div>
</header>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Menu Lateral -->
        <div class="col-md-3 col-lg-2 d-none d-md-block p-0 mb-4">
            <?php include 'menu.php'; ?>
        </div>
        
        <!-- Conteúdo Principal -->
        <main class="col-md-9 ms-auto col-lg-10 px-md-4">
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="ouvidoria_relatorio.php" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="exercicio" class="form-label">Filtrar por Ano</label>
                            <select name="exercicio" id="exercicio" class="form-select">
                                <?php for ($ano = date('Y'); $ano >= 2020; $ano--): ?>
                                    <option value="<?php echo $ano; ?>" <?php if($ano == $ano_selecionado) echo 'selected'; ?>><?php echo $ano; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-5 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><h5>Tipos de Solicitações (<?php echo $ano_selecionado; ?>)</h5></div>
                        <div class="card-body">
                            <h5>Total de Manifestações: <?php echo $total_ano; ?></h5>
                            <hr>
                            <?php foreach($stats_tipo as $tipo => $qtd): ?>
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo htmlspecialchars($tipo); ?></span>
                                    <strong><?php echo $qtd; ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7 mb-4">
                     <div class="card h-100">
                        <div class="card-header"><h5>Situação das Manifestações (<?php echo $ano_selecionado; ?>)</h5></div>
                        <div class="card-body d-flex justify-content-center align-items-center">
                            <?php if ($total_ano > 0): ?><canvas id="statusChart"></canvas><?php else: ?><p class="text-muted">Sem dados para exibir o gráfico.</p><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><h5>Top 10 Assuntos Recorrentes</h5></div>
                        <div class="card-body">
                            <?php if (!empty($stats_recorrentes)): ?>
                                <canvas id="recorrentesChart"></canvas>
                                <div class="mt-3 d-flex justify-content-center flex-wrap" style="font-size: 0.9rem;">
                                    <span class="me-3 mb-1"><span class="chart-legend-color" style="background-color: rgba(25, 135, 84, 0.7);"></span> Elogio</span>
                                    <span class="me-3 mb-1"><span class="chart-legend-color" style="background-color: rgba(13, 110, 253, 0.7);"></span> Sugestão</span>
                                    <span class="me-3 mb-1"><span class="chart-legend-color" style="background-color: rgba(255, 193, 7, 0.7);"></span> Reclamação</span>
                                    <span class="me-3 mb-1"><span class="chart-legend-color" style="background-color: rgba(220, 53, 69, 0.7);"></span> Denúncia</span>
                                    <span class="me-3 mb-1"><span class="chart-legend-color" style="background-color: rgba(13, 202, 240, 0.7);"></span> Solicitação</span>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Nenhum assunto (que não seja secretaria) se repetiu mais de uma vez neste período.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><h5>Manifestações por Secretaria</h5></div>
                        <div class="card-body">
                            <?php if (!empty($stats_secretaria)): ?>
                                <canvas id="secretariasChart"></canvas>
                            <?php else: ?>
                                <p class="text-muted">Nenhuma manifestação para as secretarias listadas foi encontrada neste período.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<footer class="text-center p-3 bg-light mt-4">
    &copy; <?php echo date('Y'); ?> - Todos os direitos reservados.
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php
// Prepara dados para os gráficos
$labels_status = json_encode(array_keys($stats_status));
$data_status = json_encode(array_values($stats_status));

$labels_secretaria = json_encode(array_column($stats_secretaria, 'assunto'));
$data_secretaria = json_encode(array_column($stats_secretaria, 'total'));

$labels_recorrentes = json_encode(array_column($stats_recorrentes, 'assunto'));
$data_recorrentes = json_encode(array_column($stats_recorrentes, 'total'));
$colors_recorrentes = [];
$colorMap = [
    'Elogio' => 'rgba(25, 135, 84, 0.7)', 'Sugestão' => 'rgba(13, 110, 253, 0.7)',
    'Reclamação' => 'rgba(255, 193, 7, 0.7)', 'Denúncia' => 'rgba(220, 53, 69, 0.7)',
    'Solicitação' => 'rgba(13, 202, 240, 0.7)', 'default' => 'rgba(108, 117, 125, 0.7)'
];
foreach($stats_recorrentes as $assunto) { $colors_recorrentes[] = $colorMap[$assunto['tipo_dominante']] ?? $colorMap['default']; }
$colors_recorrentes_json = json_encode($colors_recorrentes);
?>

document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de Pizza (Status)
    <?php if ($total_ano > 0): ?>
    new Chart(document.getElementById('statusChart').getContext('2d'), {
        type: 'doughnut', data: {
            labels: <?php echo $labels_status; ?>,
            datasets: [{ data: <?php echo $data_status; ?>, backgroundColor: ['rgba(255, 193, 7, 0.8)','rgba(13, 110, 253, 0.8)','rgba(25, 135, 84, 0.8)','rgba(108, 117, 125, 0.8)'], borderColor: '#fff' }]
        }, options: { responsive: true, plugins: { legend: { position: 'top' } } }
    });
    <?php endif; ?>

    // Gráfico de Barras (Assuntos Recorrentes)
    <?php if (!empty($stats_recorrentes)): ?>
    new Chart(document.getElementById('recorrentesChart').getContext('2d'), {
        type: 'bar', data: {
            labels: <?php echo $labels_recorrentes; ?>,
            datasets: [{ label: 'Quantidade', data: <?php echo $data_recorrentes; ?>, backgroundColor: <?php echo $colors_recorrentes_json; ?> }]
        }, options: {
            indexAxis: 'y', responsive: true, plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }, barPercentage: 0.6, categoryPercentage: 0.8
        }
    });
    <?php endif; ?>

    // Gráfico de Barras (Secretarias)
    <?php if (!empty($stats_secretaria)): ?>
    new Chart(document.getElementById('secretariasChart').getContext('2d'), {
        type: 'bar', data: {
            labels: <?php echo $labels_secretaria; ?>,
            datasets: [{ label: 'Nº de Manifestações', data: <?php echo $data_secretaria; ?>, backgroundColor: 'rgba(108, 117, 125, 0.7)' }]
        }, options: {
            indexAxis: 'y', responsive: true, plugins: { legend: { display: true, position: 'bottom' } },
            scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }, barPercentage: 0.6, categoryPercentage: 0.8
        }
    });
    <?php endif; ?>
});
</script>
</body>
</html>