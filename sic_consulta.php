<?php
require_once 'conexao.php';
$page_title = "Consulta de Solicitação SIC";
$protocolo_busca = $_GET['protocolo'] ?? '';
$solicitacao = null;

if (!empty($protocolo_busca)) {
    // Busca a solicitação na tabela dedicada 'sic_solicitacoes'
    $stmt = $pdo->prepare("SELECT * FROM sic_solicitacoes WHERE protocolo = ?");
    $stmt->execute([$protocolo_busca]);
    $solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);
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

<header class="page-header">
    <div class="container-fluid container-custom-padding">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                <li class="breadcrumb-item"><a href="sic.php">SIC</a></li>
                <li class="breadcrumb-item active" aria-current="page">Consulta de Solicitação</li>
            </ol>
        </nav>
        <h1>Consulta de Solicitação</h1>
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
            <div class="card">
                <div class="card-header">
                    <h4>Detalhes da Solicitação</h4>
                </div>
                <div class="card-body">
                    <?php if ($solicitacao): ?>
                        <dl class="row">
                            <dt class="col-sm-3">Protocolo:</dt>
                            <dd class="col-sm-9"><?php echo htmlspecialchars($solicitacao['protocolo']); ?></dd>
                            
                            <dt class="col-sm-3">Status:</dt>
                            <dd class="col-sm-9"><span class="badge bg-primary fs-6"><?php echo htmlspecialchars($solicitacao['status']); ?></span></dd>
                            
                            <dt class="col-sm-3">Data da Solicitação:</dt>
                            <dd class="col-sm-9"><?php echo date('d/m/Y H:i', strtotime($solicitacao['data_solicitacao'])); ?></dd>

                            <dt class="col-sm-3">Seu Pedido:</dt>
                            <dd class="col-sm-9"><p><?php echo nl2br(htmlspecialchars($solicitacao['descricao_pedido'])); ?></p></dd>
                            
                            <hr class="my-3">
                            
                            <dt class="col-sm-3 text-success">Resposta do Órgão:</dt>
                            <dd class="col-sm-9 text-success">
                                <p><?php echo nl2br(htmlspecialchars($solicitacao['resposta'] ?? 'Sua solicitação está em análise. Por favor, aguarde uma resposta.')); ?></p>
                            </dd>
                        </dl>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            Nenhuma solicitação encontrada com o protocolo informado: <strong><?php echo htmlspecialchars($protocolo_busca); ?></strong>.
                        </div>
                    <?php endif; ?>
                    <a href="sic.php" class="btn btn-secondary mt-3">Fazer Nova Consulta</a>
                </div>
            </div>
        </main>
    </div>
</div>

<footer class="text-center p-3 mt-4">
    </footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>