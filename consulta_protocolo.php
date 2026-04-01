<?php
require_once 'conexao.php';
$protocolo_busca = $_GET['protocolo'] ?? '';
$manifestacao = null;

if (!empty($protocolo_busca)) {
    $stmt = $pdo->prepare("SELECT * FROM ouvidoria_manifestacoes WHERE protocolo = ?");
    $stmt->execute([$protocolo_busca]);
    $manifestacao = $stmt->fetch(PDO::FETCH_ASSOC);
}
$page_title = "Consulta de Protocolo";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - Portal da Transparência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/style.css?v=<?php echo time(); ?>">
    <script>
    (function () {
        try {
            var n = parseInt(localStorage.getItem('fontSize'), 10);
            if (!isNaN(n) && n >= 12 && n <= 32) document.documentElement.style.fontSize = n + 'px';
            if (localStorage.getItem('highContrast') === 'true') document.documentElement.classList.add('high-contrast');
        } catch (e) {}
    })();
    </script>
</head>
<body>
<header class="page-header">
    <div class="container-fluid container-custom-padding">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                <li class="breadcrumb-item"><a href="ouvidoria.php">Ouvidoria</a></li>
                <li class="breadcrumb-item active" aria-current="page">Consulta de Protocolo</li>
            </ol>
        </nav>
        <h1>Consulta de Protocolo</h1>
    </div>
</header>
<div class="container-fluid py-5">
    <div class="row">
        <!-- Menu Lateral -->
        <div class="col-md-3 col-lg-2 d-none d-md-block px-0 ps-3 mb-4">
            <?php include 'menu.php'; ?>
        </div>
        
        <!-- Conteúdo Principal -->
        <main class="col-md-9 ms-auto col-lg-10 px-md-5">
            <div class="card">
                <div class="card-header">
                    <h4>Detalhes da Manifestação</h4>
                </div>
                <div class="card-body">
                    <?php if ($manifestacao): ?>
                        <dl class="row">
                            <dt class="col-sm-3">Protocolo:</dt>
                            <dd class="col-sm-9"><?php echo htmlspecialchars($manifestacao['protocolo']); ?></dd>
                            
                            <dt class="col-sm-3">Status:</dt>
                            <dd class="col-sm-9"><span class="badge bg-primary fs-6"><?php echo htmlspecialchars($manifestacao['status']); ?></span></dd>
                            
                            <dt class="col-sm-3">Tipo:</dt>
                            <dd class="col-sm-9"><?php echo htmlspecialchars($manifestacao['tipo_manifestacao']); ?></dd>
                            
                            <dt class="col-sm-3">Assunto:</dt>
                            <dd class="col-sm-9"><?php echo htmlspecialchars($manifestacao['assunto']); ?></dd>
                            
                            <dt class="col-sm-3">Sua Manifestação:</dt>
                            <dd class="col-sm-9"><p><?php echo nl2br(htmlspecialchars($manifestacao['descricao'])); ?></p></dd>
                            
                            <hr class="my-3">
                            
                            <dt class="col-sm-3 text-success">Resposta da Ouvidoria:</dt>
                            <dd class="col-sm-9 text-success">
                                <p><?php echo nl2br(htmlspecialchars($manifestacao['resposta'] ?? 'Sua manifestação está em análise. Por favor, aguarde.')); ?></p>
                            </dd>
                        </dl>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            Nenhuma manifestação encontrada com o protocolo informado: <strong><?php echo htmlspecialchars($protocolo_busca); ?></strong>.
                        </div>
                    <?php endif; ?>
                    <a href="ouvidoria.php" class="btn btn-secondary mt-3">Voltar</a>
                </div>
            </div>
        </main>
    </div>
</div>
<footer class="text-center p-3 mt-4"></footer>
<script src="<?php echo $base_url; ?>js/acessibilidade.js"></script>
</body>
</html>