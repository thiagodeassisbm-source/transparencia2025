<?php
require_once 'conexao.php';
require_once 'bootstrap_portal.php'; // Carrega o contexto da prefeitura ativa (SaaS)

$slug = filter_input(INPUT_GET, 'slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if (empty($slug)) {
    header("Location: index.php");
    exit;
}

// Busca a página no banco de dados pelo slug e prefeitura ativa
$stmt = $pdo->prepare("SELECT id, titulo, conteudo FROM paginas WHERE TRIM(slug) = ? AND id_prefeitura = ?");
$stmt->execute([$slug, $id_prefeitura_ativa]);
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
    <base href="<?php echo $base_url; ?>">
    <title><?php echo htmlspecialchars($page_title); ?> - Portal da Transparência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light">

<?php include 'header_publico.php'; ?>

<div class="container-fluid py-5">
    <div class="row">
        <!-- Menu Lateral -->
        <div class="col-md-3 col-lg-2 d-none d-md-block px-0 ps-3 mb-4">
            <?php include 'menu.php'; ?>
        </div>
        <main class="col-md-9 ms-auto col-lg-10 px-md-5">
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

<?php
$custom_container_class = "container-custom-padding";
include 'footer_publico.php';
?>
</body>
</html>