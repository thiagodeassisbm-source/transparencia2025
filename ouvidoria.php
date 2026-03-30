<?php
require_once 'conexao.php';
$page_title = "Ouvidoria Municipal";

// Lógica para estatísticas (Específicas da prefeitura se necessário)
$stats = [];
$total_manifestacoes = 0;
try {
    // Aqui usaremos o ID da prefeitura ativa se as estatísticas forem separadas
    $curr_pref_id = $id_prefeitura_ativa ?? 0;
    
    // Por enquanto buscamos o global ou podemos filtrar pela prefeitura se a tabela permitir
    $stmt_stats = $pdo->prepare("SELECT tipo_manifestacao, COUNT(id) as total FROM ouvidoria_manifestacoes WHERE id_prefeitura = ? GROUP BY tipo_manifestacao");
    $stmt_stats->execute([$curr_pref_id]);
    $stats_raw = $stmt_stats->fetchAll(PDO::FETCH_KEY_PAIR);

    $tipos_possiveis = ['Elogio', 'Sugestão', 'Reclamação', 'Denúncia', 'Solicitação'];
    foreach ($tipos_possiveis as $tipo) {
        $stats[$tipo] = $stats_raw[$tipo] ?? 0;
    }
    $total_manifestacoes = array_sum($stats);
}
catch (Exception $e) {
    $stats = [];
    $total_manifestacoes = 0;
}

// Busca as configurações da ouvidoria para a prefeitura ativa
$config_ouvidoria = [];
try {
    $stmt_config = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE id_prefeitura = ? AND chave LIKE 'ouvidoria_%'");
    $stmt_config->execute([$curr_pref_id]);
    $config_ouvidoria = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {}

// Fallbacks de contato
$config_ouvidoria['ouvidoria_setor'] = $config_ouvidoria['ouvidoria_setor'] ?? 'Ouvidoria Municipal';
$config_ouvidoria['ouvidoria_endereco'] = $config_ouvidoria['ouvidoria_endereco'] ?? 'Sede Administrativa';
$config_ouvidoria['ouvidoria_email'] = $config_ouvidoria['ouvidoria_email'] ?? 'ouvidoria@municipio.gov.br';
$config_ouvidoria['ouvidoria_telefone'] = $config_ouvidoria['ouvidoria_telefone'] ?? '(62) 0000-0000';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Portal da Transparência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light">

<?php 
include 'header_publico.php'; 
?>

<div class="container-fluid">
    <div class="row">
        <!-- Menu Lateral -->
        <div class="col-lg-3 mb-4">
            <?php include 'menu.php'; ?>
        </div>

        <!-- Conteúdo Principal -->
        <main class="col-lg-9">
            <h2 class="mb-4 fw-bold text-dark border-bottom pb-2">Ouvidoria Municipal</h2>
            
            <?php if (isset($_GET['protocolo'])): ?>
                <div class="alert alert-success shadow-sm border-0 rounded-4 p-4 mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill fs-2 me-3 text-success"></i>
                        <div>
                            <h5 class="mb-1 fw-bold">Manifestação Registrada!</h5>
                            <p class="mb-0">Sua manifestação foi enviada. Anote o protocolo: <strong><?php echo htmlspecialchars($_GET['protocolo']); ?></strong></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card mb-4 border-0 shadow-sm rounded-4 overlay-hidden bg-white">
                <div class="card-body p-4 border-start border-4 border-primary">
                    <p class="lead mb-0 fs-6">A ouvidoria é o seu canal direto com a gestão municipal. Aqui você pode registrar elogios, sugestões, reclamações, solicitações ou denúncias de forma segura e transparente.</p>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Informações de Atendimento -->
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white py-3 border-0">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-headset text-primary me-2"></i> Atendimento</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="small text-muted fw-bold text-uppercase">Responsável:</label>
                                <p class="mb-0 fw-medium"><?php echo htmlspecialchars($config_ouvidoria['ouvidoria_setor']); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted fw-bold text-uppercase">Endereço:</label>
                                <p class="mb-0 fw-medium"><?php echo htmlspecialchars($config_ouvidoria['ouvidoria_endereco']); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted fw-bold text-uppercase">E-mail:</label>
                                <p class="mb-0 fw-medium text-primary"><?php echo htmlspecialchars($config_ouvidoria['ouvidoria_email']); ?></p>
                            </div>
                            <div>
                                <label class="small text-muted fw-bold text-uppercase">Telefone:</label>
                                <p class="mb-0 fw-medium"><?php echo htmlspecialchars($config_ouvidoria['ouvidoria_telefone']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 bg-white">
                        <div class="card-header bg-white py-3 border-0">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-fill text-success me-2"></i> Manifestar</h5>
                        </div>
                        <div class="card-body d-grid gap-2">
                            <a href="<?php echo $base_url; ?>portal/<?php echo $slug_pref_header; ?>/abrir_manifestacao.php?tipo=Sugestão" class="btn btn-outline-dynamic btn-sm text-start py-2 px-3 fw-bold"><i class="bi bi-lightbulb-fill me-2 text-warning"></i> Sugestão</a>
                            <a href="<?php echo $base_url; ?>portal/<?php echo $slug_pref_header; ?>/abrir_manifestacao.php?tipo=Elogio" class="btn btn-outline-dynamic btn-sm text-start py-2 px-3 fw-bold"><i class="bi bi-hand-thumbs-up-fill me-2 text-success"></i> Elogio</a>
                            <a href="<?php echo $base_url; ?>portal/<?php echo $slug_pref_header; ?>/abrir_manifestacao.php?tipo=Solicitação" class="btn btn-outline-dynamic btn-sm text-start py-2 px-3 fw-bold"><i class="bi bi-chat-left-dots-fill me-2 text-info"></i> Solicitação</a>
                            <a href="<?php echo $base_url; ?>portal/<?php echo $slug_pref_header; ?>/abrir_manifestacao.php?tipo=Reclamação" class="btn btn-outline-dynamic btn-sm text-start py-2 px-3 fw-bold"><i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i> Reclamação</a>
                            <a href="<?php echo $base_url; ?>portal/<?php echo $slug_pref_header; ?>/abrir_manifestacao.php?tipo=Denúncia" class="btn btn-outline-dynamic btn-sm text-start py-2 px-3 fw-bold"><i class="bi bi-shield-fill-exclamation me-2 text-danger"></i> Denúncia</a>
                        </div>
                    </div>
                </div>

                <!-- Consulta de Protocolo -->
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white py-3 border-0">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-search text-info me-2"></i> Consultar</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-4">Acompanhe sua manifestação digitando o número do protocolo abaixo.</p>
                            <form action="<?php echo $base_url; ?>portal/<?php echo $slug_pref_header; ?>/consulta_protocolo.php" method="GET">
                                <div class="mb-3">
                                    <input type="text" name="protocolo" class="form-control rounded-start-pill rounded-end-pill px-4" placeholder="Ex: 2024.030.001" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-dynamic-primary rounded-pill py-2 fw-bold shadow-sm">Acompanhar Manifestação</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Estatístico -->
            <?php if ($total_manifestacoes > 0): ?>
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-bar-chart-fill text-dark me-2"></i> Relatório em Tempo Real</h5>
                    <span class="badge bg-light text-dark fw-normal border px-3">Total: <?php echo $total_manifestacoes; ?> manifestações</span>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <?php foreach ($stats as $tipo => $quantidade): 
                            $percentual = ($quantidade / $total_manifestacoes) * 100;
                            $cor_barra = 'bg-primary'; 
                            if($tipo == 'Denúncia') $cor_barra = 'bg-danger';
                            if($tipo == 'Elogio') $cor_barra = 'bg-success';
                            if($tipo == 'Reclamação') $cor_barra = 'bg-warning';
                            if($tipo == 'Sugestão') $cor_barra = 'bg-warning-subtle';
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="d-flex justify-content-between mb-1 small fw-bold">
                                <span><?php echo $tipo; ?></span>
                                <span><?php echo $quantidade; ?> (<?php echo round($percentual, 1); ?>%)</span>
                            </div>
                            <div class="progress" style="height: 10px; border-radius: 5px;">
                                <div class="progress-bar <?php echo $cor_barra; ?> shadow-sm" style="width: <?php echo $percentual; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3 pt-3 border-top">
                        <a href="<?php echo $base_url; ?>portal/<?php echo $slug_pref_header; ?>/ouvidoria_relatorio.php" class="text-primary text-decoration-none fw-bold small">Ver Relatório Completo Detalhado <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
.btn-outline-dynamic {
    border: 1px solid #eee;
    color: #444;
}
.btn-outline-dynamic:hover {
    border-color: var(--cor-principal);
    background: rgba(var(--cor-principal-rgb), 0.05);
}
</style>

<?php include 'footer_publico.php'; ?>
</body>
</html>