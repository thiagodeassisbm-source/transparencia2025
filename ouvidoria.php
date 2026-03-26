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
    <title><?php echo htmlspecialchars($page_title); ?> - Portal da Transparência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="accessibility-bar py-1">
    <div class="container-fluid container-custom-padding">
        <div class="d-flex justify-content-end align-items-center">
            <span class="me-3 fw-bold d-none d-md-inline"><i class="bi bi-universal-access"></i> ACESSIBILIDADE</span>
            <button id="font-increase" class="btn btn-sm btn-outline-dark me-1" title="Aumentar Fonte">A+</button>
            <button id="font-reset" class="btn btn-sm btn-outline-dark me-1" title="Fonte Padrão">A</button>
            <button id="font-decrease" class="btn btn-sm btn-outline-dark me-2" title="Diminuir Fonte">A-</button>
            <button id="contrast-toggle" class="btn btn-sm btn-outline-dark" title="Alto Contraste"><i class="bi bi-circle-half"></i></button>
        </div>
    </div>
</div>

<header class="page-header">
    <div class="container-fluid container-custom-padding">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                <li class="breadcrumb-item active" aria-current="page">Ouvidoria</li>
            </ol>
        </nav>
        <h1>Bem-vindo à Ouvidoria</h1>
    </div>
</header>

<div class="container-fluid">
    <div class="row">
        <?php include 'menu.php'; ?>
        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            
            <?php if (isset($_GET['protocolo'])): ?>
                <div class="alert alert-success">
                    <h4>Manifestação Enviada!</h4>
                    <p>Sua manifestação foi registrada com sucesso. Anote o número do seu protocolo para acompanhar:</p>
                    <p class="h5"><strong>Protocolo:</strong> <?php echo htmlspecialchars($_GET['protocolo']); ?></p>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <p class="lead">Na ouvidoria, os cidadãos possuem um canal de manifestação e podem apresentar sugestões, elogios, solicitações, reclamações e denúncias.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><h4>Manifestação Presencial</h4></div>
                        <div class="card-body">
                            <p><strong>Setor:</strong><br> <?php echo htmlspecialchars($config_ouvidoria['ouvidoria_setor'] ?? 'Não informado'); ?></p>
                            <p><strong>Endereço:</strong><br> <?php echo htmlspecialchars($config_ouvidoria['ouvidoria_endereco'] ?? 'Não informado'); ?></p>
                            <p><strong>E-mail:</strong><br> <?php echo htmlspecialchars($config_ouvidoria['ouvidoria_email'] ?? 'Não informado'); ?></p>
                            <p><strong>Telefone:</strong><br> <?php echo htmlspecialchars($config_ouvidoria['ouvidoria_telefone'] ?? 'Não informado'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><h4>Nova Manifestação</h4></div>
                        <div class="card-body d-grid gap-2">
                            <a href="abrir_manifestacao.php?tipo=Sugestão" class="btn btn-success btn-lg text-start"><i class="bi bi-lightbulb-fill me-2"></i> Sugestão</a>
                            <a href="abrir_manifestacao.php?tipo=Elogio" class="btn btn-success btn-lg text-start"><i class="bi bi-hand-thumbs-up-fill me-2"></i> Elogio</a>
                            <a href="abrir_manifestacao.php?tipo=Solicitação" class="btn btn-success btn-lg text-start"><i class="bi bi-chat-left-dots-fill me-2"></i> Solicitação</a>
                            <a href="abrir_manifestacao.php?tipo=Reclamação" class="btn btn-success btn-lg text-start"><i class="bi bi-exclamation-triangle-fill me-2"></i> Reclamação</a>
                            <a href="abrir_manifestacao.php?tipo=Denúncia" class="btn btn-success btn-lg text-start"><i class="bi bi-shield-fill-exclamation me-2"></i> Denúncia</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                         <div class="card-header"><h4>Acompanhar Manifestação</h4></div>
                        <div class="card-body">
                            <p>Consulte o andamento da sua manifestação usando o número do protocolo.</p>
                            <form action="consulta_protocolo.php" method="GET">
                                <div class="mb-3">
                                    <label for="protocolo" class="form-label">Protocolo*</label>
                                    <input type="text" name="protocolo" id="protocolo" class="form-control" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Acompanhar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h4>Relatório Estatístico</h4>
                </div>
                <div class="card-body">
                    <p>O relatório estatístico da Ouvidoria apresenta informações sobre as manifestações recebidas, possibilitando ao cidadão analisar a qualidade dos serviços prestados.</p>
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
                            <div class="d-flex justify-content-between">
                                <span><?php echo htmlspecialchars($tipo); ?></span>
                                <span><?php echo $quantidade; ?></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar <?php echo $cor_barra; ?>" role="progressbar" style="width: <?php echo $percentual; ?>%;" aria-valuenow="<?php echo $percentual; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                         <div class="text-end mt-3">
                            <a href="ouvidoria_relatorio.php" class="btn btn-primary">Ver mais detalhes <i class="bi bi-arrow-right-circle"></i></a>
                         </div>
                    <?php else: ?>
                        <p class="text-muted">Ainda não há dados suficientes para gerar um relatório.</p>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<footer class="p-3 mt-4">
    <div class="container-fluid container-custom-padding">
        <div class="d-flex justify-content-between align-items-center" style="font-size: 14px;">
            <div>&copy; <?php echo date('Y'); ?> - Todos os direitos reservados.</div>
            <div>
                Desenvolvido por |
                <a href="https://www.upgyn.com.br" target="_blank" class="ms-2">
                    <img src="imagens/logo-up.png" alt="UPGYN" style="height: 40px; vertical-align: middle;">
                </a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const body = document.body;
    const btnIncrease = document.getElementById('font-increase');
    const btnReset = document.getElementById('font-reset');
    const btnDecrease = document.getElementById('font-decrease');
    const btnContrast = document.getElementById('contrast-toggle');
    let currentFontSize = parseInt(localStorage.getItem('fontSize') || 16);
    let highContrast = localStorage.getItem('highContrast') === 'true';
    function applySettings() {
        body.style.fontSize = currentFontSize + 'px';
        if (highContrast) { body.classList.add('high-contrast'); } 
        else { body.classList.remove('high-contrast'); }
    }
    if(btnIncrease) { btnIncrease.addEventListener('click', function() { if (currentFontSize < 24) { currentFontSize += 2; localStorage.setItem('fontSize', currentFontSize); applySettings(); } }); }
    if(btnDecrease) { btnDecrease.addEventListener('click', function() { if (currentFontSize > 12) { currentFontSize -= 2; localStorage.setItem('fontSize', currentFontSize); applySettings(); } }); }
    if(btnReset) { btnReset.addEventListener('click', function() { currentFontSize = 16; localStorage.removeItem('fontSize'); applySettings(); }); }
    if(btnContrast) { btnContrast.addEventListener('click', function() { highContrast = !highContrast; localStorage.setItem('highContrast', highContrast); applySettings(); }); }
    applySettings();
});
</script>
</body>
</html>