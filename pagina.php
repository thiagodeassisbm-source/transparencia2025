<?php
require_once 'conexao.php';

$slug = filter_input(INPUT_GET, 'slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if (empty($slug)) {
    header("Location: index.php");
    exit;
}

// Busca a página no banco de dados pelo slug
$stmt = $pdo->prepare("SELECT id, titulo, conteudo FROM paginas WHERE TRIM(slug) = ?");
$stmt->execute([$slug]);
$pagina = $stmt->fetch();

$anexos = [];
if ($pagina) {
    // NOVA BUSCA: Pega os anexos associados a esta página
    $stmt_anexos = $pdo->prepare("SELECT titulo_anexo, caminho_arquivo FROM pagina_anexos WHERE id_pagina = ?");
    $stmt_anexos->execute([$pagina['id']]);
    $anexos = $stmt_anexos->fetchAll();
    $page_title = $pagina['titulo'];
} else {
    $page_title = "Erro 404";
    $pagina['titulo'] = "Página Não Encontrada";
    $pagina['conteudo'] = "<p>O conteúdo que você está tentando acessar não existe ou foi movido.</p><a href='index.php' class='btn btn-primary'>Voltar para a página inicial</a>";
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
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($pagina['titulo']); ?></li>
            </ol>
        </nav>
        <h1><?php echo htmlspecialchars($pagina['titulo']); ?></h1>
    </div>
</header>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <?php include 'menu.php'; ?>
        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            <div class="card">
                <div class="card-body">
                    <h2 class="h3 mb-4"><?php echo htmlspecialchars($pagina['titulo']); ?></h2>
                    
                    <?php echo $pagina['conteudo']; ?>

                    <?php if (!empty($anexos)): ?>
                        <hr class="my-4">
                        <h4 class="h5">Documentos Anexos</h4>
                        <div class="list-group">
                            <?php foreach ($anexos as $anexo): ?>
                                <a href="<?php echo htmlspecialchars($anexo['caminho_arquivo']); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" download>
                                    <span>
                                        <i class="bi bi-file-earmark-arrow-down-fill me-2"></i>
                                        <?php echo htmlspecialchars($anexo['titulo_anexo']); ?>
                                    </span>
                                    <span class="badge bg-primary rounded-pill">Baixar</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
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
// Script de Acessibilidade (cole o seu código completo aqui)
document.addEventListener('DOMContentLoaded', function() {
    // ...
});
</script>

</body>
</html>