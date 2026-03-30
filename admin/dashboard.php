<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// --- BUSCA DE DADOS PARA OS CARDS NUMÉRICOS ---
$pref_id = $_SESSION['id_prefeitura'] ?? 0;

$total_secoes = $pdo->prepare("SELECT COUNT(id) FROM portais WHERE id_prefeitura = ?");
$total_secoes->execute([$pref_id]);
$total_secoes = $total_secoes->fetchColumn();

$total_lancamentos = $pdo->prepare("SELECT COUNT(id) FROM registros WHERE id_portal IN (SELECT id FROM portais WHERE id_prefeitura = ?)");
$total_lancamentos->execute([$pref_id]);
$total_lancamentos = $total_lancamentos->fetchColumn();

$total_paginas = $pdo->prepare("SELECT COUNT(id) FROM paginas WHERE id_prefeitura = ?");
$total_paginas->execute([$pref_id]);
$total_paginas = $total_paginas->fetchColumn();

$total_ouvidoria = $pdo->prepare("SELECT COUNT(id) FROM ouvidoria_manifestacoes WHERE id_prefeitura = ?");
$total_ouvidoria->execute([$pref_id]);
$total_ouvidoria = $total_ouvidoria->fetchColumn();

// --- DADOS PARA O GRÁfico DE STATUS DA OUVIDORIA ---
$stmt_ouvidoria = $pdo->prepare(
    "SELECT status, COUNT(id) as total 
     FROM ouvidoria_manifestacoes 
     WHERE id_prefeitura = ?
     GROUP BY status"
);
$stmt_ouvidoria->execute([$pref_id]);
$stats_status_raw = $stmt_ouvidoria->fetchAll(PDO::FETCH_KEY_PAIR);
$stats_status = [];
$status_possiveis = ['Recebida', 'Em Análise', 'Respondida', 'Finalizada'];
foreach ($status_possiveis as $status) {
    $stats_status[$status] = $stats_status_raw[$status] ?? 0;
}


// --- DADOS PARA O GRÁFICO DE LANÇAMENTOS POR SEÇÃO ---
$stmt_lanc_secao = $pdo->prepare(
    "SELECT p.nome, COUNT(r.id) as total
     FROM registros r
     JOIN portais p ON r.id_portal = p.id
     WHERE p.id_prefeitura = ?
     GROUP BY p.nome
     ORDER BY total DESC
     LIMIT 7"
);
$stmt_lanc_secao->execute([$pref_id]);
$lancamentos_por_secao = $stmt_lanc_secao->fetchAll(PDO::FETCH_ASSOC);

$page_title_for_header = 'Dashboard'; 
include 'admin_header.php'; 
?>

<div class="container-fluid">
    <!-- Mensagens do Sistema (Super Admin) -->
    <?php
    $id_usuario_atual = $_SESSION['admin_user_id'];
    $stmt_msg = $pdo->prepare("
        SELECT m.* 
        FROM mensagens_sistema m
        LEFT JOIN mensagens_vistas v ON m.id = v.id_mensagem AND v.id_usuario = ?
        WHERE (m.id_prefeitura IS NULL OR m.id_prefeitura = ?)
        AND m.ativa = 1
        AND v.id IS NULL
        ORDER BY m.criado_em DESC
    ");
    $stmt_msg->execute([$id_usuario_atual, $pref_id]);
    $mensagens_admin = $stmt_msg->fetchAll();

    foreach ($mensagens_admin as $msg): 
        $cor = $msg['cor'] ?? 'primary';
        $icon = 'bi-info-circle-fill';
        if($cor == 'warning') $icon = 'bi-exclamation-triangle-fill';
        if($cor == 'danger') $icon = 'bi-exclamation-octagon-fill';
        if($cor == 'success') $icon = 'bi-megaphone-fill';
    ?>
    <div class="alert alert-<?php echo $cor; ?> alert-dismissible fade show shadow-sm border-0 mb-4 rounded-4 p-4" role="alert">
        <div class="d-flex align-items-center">
            <div class="bg-white bg-opacity-25 rounded-circle p-3 me-4 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                <i class="bi <?php echo $icon; ?> fs-3"></i>
            </div>
            <div>
                <h5 class="alert-heading fw-bold mb-1"><?php echo htmlspecialchars($msg['titulo']); ?></h5>
                <div class="opacity-75"><?php echo nl2br(htmlspecialchars($msg['mensagem'])); ?></div>
            </div>
        </div>
        <button type="button" class="btn-close p-4" data-bs-dismiss="alert" aria-label="Close" onclick="marcarComoLida(<?php echo $msg['id']; ?>)"></button>
    </div>
    <?php endforeach; ?>

    <script>
    function marcarComoLida(id) {
        fetch('marcar_mensagem_lida.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) { console.log('Mensagem marcada como lida!'); }
            });
    }
    </script>
    <!-- Fim Mensagens -->

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card vibrant-card bg-vibrant-blue shadow h-100 border-0">
                <div class="stat-icon">
                    <i class="bi bi-folder2-open"></i>
                </div>
                <div class="stat-info">
                    <p>Seções Criadas</p>
                    <h3><?php echo $total_secoes; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card vibrant-card bg-vibrant-green shadow h-100 border-0">
                <div class="stat-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div class="stat-info">
                    <p>Total Lançamentos</p>
                    <h3><?php echo $total_lancamentos; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card vibrant-card bg-vibrant-purple shadow h-100 border-0">
                <div class="stat-icon">
                    <i class="bi bi-file-richtext"></i>
                </div>
                <div class="stat-info">
                    <p>Páginas de Conteúdo</p>
                    <h3><?php echo $total_paginas; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card vibrant-card bg-vibrant-yellow shadow h-100 border-0">
                <div class="stat-icon">
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                backgroundColor: ['#f59e0b', '#ef4444', '#10b981', '#6b7280'],
                borderWidth: 0
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            }
        }
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
                backgroundColor: '#3b82f6',
                borderRadius: 8
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