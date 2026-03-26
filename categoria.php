<?php
// /categoria.php
require_once 'conexao.php';

$categoria_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$categoria_id) { die("Categoria não especificada."); }

// Busca o nome da categoria para o título da página
$stmt_cat = $pdo->prepare("SELECT nome FROM categorias WHERE id = ?");
$stmt_cat->execute([$categoria_id]);
$categoria = $stmt_cat->fetch();
if (!$categoria) { die("Categoria não encontrada."); }

// Busca todas as seções que pertencem a esta categoria
$stmt_secoes = $pdo->prepare("SELECT nome, slug, descricao FROM portais WHERE id_categoria = ? ORDER BY ordem ASC");
$stmt_secoes->execute([$categoria_id]);
$secoes = $stmt_secoes->fetchAll();

// Define o ID da categoria ativa para o menu saber qual item destacar
$_GET['id'] = $categoria_id; 
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($categoria['nome']); ?> - Portal da Transparência</title>
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
                <li class="breadcrumb-item"><a href="index.php">Transparência</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($categoria['nome']); ?></li>
            </ol>
        </nav>
        <h1><?php echo htmlspecialchars($categoria['nome']); ?></h1>
    </div>
</header>

<div class="container-fluid container-custom-padding">
    <div class="row">
        
        <?php include 'menu.php'; ?>

        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span><?php echo count($secoes); ?> seções encontradas nesta categoria.</span>
            </div>

            <div class="info-card-list">
                <?php if (empty($secoes)): ?>
                    <div class="alert alert-light">Nenhuma seção foi cadastrada para esta categoria ainda.</div>
                <?php else: ?>
                    <?php foreach ($secoes as $secao): ?>
                        <a href="portal.php?slug=<?php echo htmlspecialchars($secao['slug']); ?>" class="info-card">
                            <div class="icon-container">
                                <i class="bi bi-folder" style="font-size: 2rem; color: #6c757d;"></i>
                            </div>
                            <div class="text-container">
                                <div class="card-titulo"><?php echo htmlspecialchars($secao['nome']); ?></div>
                                <div class="card-subtitulo"><?php echo htmlspecialchars($secao['descricao']); ?></div>
                            </div>
                            <div class="favorite-icon">
                                <i class="bi bi-chevron-right"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<footer class="text-center p-3 mt-4">
    &copy; <?php echo date('Y'); ?> - Todos os direitos reservados.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>