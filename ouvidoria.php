<?php
require_once 'conexao.php';
$page_title = "Ouvidoria";

// --- LÓGICA PARA BUSCAR OS DADOS DO RELATÓRIO ESTATÍSTICO ---
$stats = [];
$total_manifestacoes = 0;
try {
    // 1. Conta quantas vezes cada tipo de manifestação aparece na tabela dedicada 'ouvidoria_manifestacoes'
    $stmt_stats = $pdo->query(
        "SELECT tipo_manifestacao, COUNT(id) as total 
         FROM ouvidoria_manifestacoes 
         GROUP BY tipo_manifestacao"
    );
    $stats_raw = $stmt_stats->fetchAll(PDO::FETCH_KEY_PAIR);

    // 2. Organiza os dados e calcula o total
    $tipos_possiveis = ['Elogio', 'Sugestão', 'Reclamação', 'Denúncia', 'Solicitação'];
    foreach ($tipos_possiveis as $tipo) {
        $stats[$tipo] = $stats_raw[$tipo] ?? 0;
    }
    $total_manifestacoes = array_sum($stats);
} catch (Exception $e) {
    // Se houver erro (ex: tabela não existe), o relatório não será exibido
    $stats = [];
    $total_manifestacoes = 0;
}

// Busca as configurações de contato da ouvidoria
$config_ouvidoria = [];
try {
    $stmt_config = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'ouvidoria_%'");
    $config_ouvidoria = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    // Ignora o erro se a tabela de configurações não existir
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ouvidoria - Portal da Transparência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light">

<?php 
$page_title = "Ouvidoria"; 
include 'header_publico.php'; 
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'menu.php'; ?>
        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            
            <h2 class="mb-4 fw-bold">Ouvidoria Municipal</h2>

            <?php if (isset($_GET['protocolo'])): ?>
                <div class="alert alert-success shadow-sm">
                    <h4><i class="bi bi-check-circle-fill me-2"></i>Manifestação Enviada!</h4>
                    <p>Sua manifestação foi registrada com sucesso. Anote o número do seu protocolo para acompanhar:</p>
                    <p class="h5"><strong>Protocolo:</strong> <?php echo htmlspecialchars($_GET['protocolo']); ?></p>
                </div>
            <?php endif; ?>

            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <p class="lead mb-0">Através da ouvidoria, o cidadão pode apresentar sugestões, elogios, solicitações, reclamações e denúncias sobre os serviços públicos municipais.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-white py-3"><h4><i class="bi bi-geo-alt-fill text-primary me-2"></i>Atendimento</h4></div>
                        <div class="card-body">
                            <p><strong>Setor:</strong><br> <?php echo htmlspecialchars($config_ouvidoria['ouvidoria_setor'] ?? 'Não informado'); ?></p>
                            <p><strong>Endereço:</strong><br> <?php echo htmlspecialchars($config_ouvidoria['ouvidoria_endereco'] ?? 'Não informado'); ?></p>
                            <p><strong>E-mail:</strong><br> <?php echo htmlspecialchars($config_ouvidoria['ouvidoria_email'] ?? 'Ouvidoria@municipio.gov.br'); ?></p>
                            <p><strong>Telefone:</strong><br> <?php echo htmlspecialchars($config_ouvidoria['ouvidoria_telefone'] ?? 'Não informado'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-white py-3"><h4><i class="bi bi-plus-circle-fill text-success me-2"></i>Manifestar</h4></div>
                        <div class="card-body d-grid gap-2">
                            <a href="abrir_manifestacao.php?tipo=Sugestão" class="btn btn-dynamic-primary btn-lg text-start"><i class="bi bi-lightbulb-fill me-2"></i> Sugestão</a>
                            <a href="abrir_manifestacao.php?tipo=Elogio" class="btn btn-dynamic-primary btn-lg text-start"><i class="bi bi-hand-thumbs-up-fill me-2"></i> Elogio</a>
                            <a href="abrir_manifestacao.php?tipo=Solicitação" class="btn btn-dynamic-primary btn-lg text-start"><i class="bi bi-chat-left-dots-fill me-2"></i> Solicitação</a>
                            <a href="abrir_manifestacao.php?tipo=Reclamação" class="btn btn-dynamic-primary btn-lg text-start"><i class="bi bi-exclamation-triangle-fill me-2"></i> Reclamação</a>
                            <a href="abrir_manifestacao.php?tipo=Denúncia" class="btn btn-dynamic-primary btn-lg text-start"><i class="bi bi-shield-fill-exclamation me-2"></i> Denúncia</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                         <div class="card-header bg-white py-3"><h4><i class="bi bi-search text-info me-2"></i>Consultar</h4></div>
                        <div class="card-body">
                            <p>Consulte o andamento da sua manifestação usando o número do protocolo.</p>
                            <form action="consulta_protocolo.php" method="GET">
                                <div class="mb-3">
                                    <label for="protocolo" class="form-label">Protocolo*</label>
                                    <input type="text" name="protocolo" id="protocolo" class="form-control" placeholder="Digite seu protocolo" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Acompanhar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h4><i class="bi bi-bar-chart-fill text-warning me-2"></i>Relatório Estatístico</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Manifestações recebidas por tipo (Dados atualizados em tempo real).</p>
                    <hr>
                    <?php if ($total_manifestacoes > 0): ?>
                        <?php foreach ($stats as $tipo => $quantidade):
                            $percentual = ($quantidade / $total_manifestacoes) * 100;
                            $cor_barra = 'bg-secondary';
                            if ($tipo == 'Elogio') $cor_barra = 'bg-success';
                            if ($tipo == 'Sugestão') $cor_barra = 'bg-primary';
                            if ($tipo == 'Reclamação') $cor_barra = 'bg-warning';
                            if ($tipo == 'Denúncia') $cor_barra = 'bg-danger';
                            if ($tipo == 'Solicitação') $cor_barra = 'bg-info';
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold"><?php echo htmlspecialchars($tipo); ?></span>
                                <span class="badge bg-light text-dark border"><?php echo $quantidade; ?> (<?php echo round($percentual, 1); ?>%)</span>
                            </div>
                            <div class="progress" style="height: 12px; border-radius: 6px;">
                                <div class="progress-bar <?php echo $cor_barra; ?> progress-bar-striped" role="progressbar" style="width: <?php echo $percentual; ?>%;" aria-valuenow="<?php echo $percentual; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                         <div class="text-end mt-3">
                            <a href="ouvidoria_relatorio.php" class="btn btn-outline-primary">Ver mais detalhes <i class="bi bi-arrow-right-circle ms-1"></i></a>
                         </div>
                    <?php else: ?>
                        <p class="text-muted italic"><i class="bi bi-info-circle me-1"></i>Ainda não há dados suficientes para gerar um gráfico estatístico.</p>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<?php 
$custom_container_class = "container-custom-padding";
include 'footer_publico.php'; 
?>
</body>
</html>