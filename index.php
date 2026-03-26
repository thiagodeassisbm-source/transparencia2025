<?php
require_once 'conexao.php';

// --- CONFIGURAÇÕES DA PAGINAÇÃO ---
$itens_por_pagina = 12;
$pagina_atual = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
if ($pagina_atual < 1) { $pagina_atual = 1; }
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$ordem_atual = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'padrao';
$categoria_id = filter_input(INPUT_GET, 'categoria_id', FILTER_VALIDATE_INT);

// --- LÓGICA DE BUSCA ---
$params = [];
$sql_where = " WHERE 1=1";
if ($categoria_id) {
    $sql_where .= " AND id_categoria = ?";
    $params[] = $categoria_id;
}

$sql_order = " ORDER BY acessos DESC";
if ($ordem_atual === 'alpha') {
    $sql_order = " ORDER BY titulo ASC";
}

$sql_count = "SELECT COUNT(id) FROM categorias_cards $sql_where";
$stmt_total = $pdo->prepare($sql_count);
$stmt_total->execute($params);
$total_itens = $stmt_total->fetchColumn();
$total_paginas = ceil($total_itens / $itens_por_pagina);

$sql_cards = "SELECT * FROM categorias_cards $sql_where $sql_order LIMIT ? OFFSET ?";
$stmt_cards = $pdo->prepare($sql_cards);
$stmt_cards->bindValue(1, $itens_por_pagina, PDO::PARAM_INT);
$stmt_cards->bindValue(2, $offset, PDO::PARAM_INT);
if ($categoria_id) {
    $stmt_cards = $pdo->prepare("SELECT * FROM categorias_cards $sql_where $sql_order LIMIT $itens_por_pagina OFFSET $offset");
    $stmt_cards->execute($params);
} else {
    $stmt_cards->execute();
}
$cards = $stmt_cards->fetchAll();

// Busca favoritos (simulação - futuramente pode ser via cookie ou sessão)
$favoritos = isset($_COOKIE['portal_favoritos']) ? json_decode($_COOKIE['portal_favoritos'], true) : [];
foreach ($cards as &$card) {
    $card['favorito'] = in_array($card['id'], $favoritos);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Portal da Transparência - Início</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light">

<?php 
$page_title = "Início"; 
include 'header_publico.php'; 
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'menu.php'; ?>
        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0 fw-bold">Serviços e Informações</h2>
                <div class="btn-group shadow-sm">
                    <?php 
                        $query_params_sort = $_GET;
                        unset($query_params_sort['sort'], $query_params_sort['page']);
                    ?>
                    <a href="?<?php echo http_build_query($query_params_sort); ?>" class="btn btn-sm <?php echo ($ordem_atual === 'padrao') ? 'btn-dynamic-primary' : 'btn-outline-secondary'; ?>">Mais Acessados</a>
                    <?php $query_params_sort['sort'] = 'alpha'; ?>
                    <a href="?<?php echo http_build_query($query_params_sort); ?>" class="btn btn-sm <?php echo ($ordem_atual === 'alpha') ? 'btn-dynamic-primary' : 'btn-outline-secondary'; ?>">Ordem Alfabética</a>
                </div>
            </div>

            <div class="info-card-list">
                <?php if (empty($cards)): ?>
                    <div class="alert alert-light border shadow-sm">Nenhum serviço disponível no momento para esta categoria.</div>
                <?php else: ?>
                    <?php foreach ($cards as $card): ?>
                        <?php
                        $link = !empty($card['link_url']) ? htmlspecialchars($card['link_url']) : (!empty($card['slug']) ? 'portal.php?slug=' . htmlspecialchars($card['slug']) : '#');
                        $target = !empty($card['link_url']) ? '_blank' : '_self';
                        ?>
                        <div class="info-card border-0 shadow-sm">
                            <div class="icon-container position-relative">
                                <div class="dynamic-icon shadow-sm"></div>
                                <div class="dynamic-icon-text">i</div>
                            </div>
                            <div class="text-container">
                                <div class="card-titulo">
                                    <a href="<?php echo $link; ?>" class="stretched-link text-decoration-none text-reset" target="<?php echo $target; ?>">
                                        <?php echo htmlspecialchars($card['titulo']); ?>
                                    </a>
                                </div>
                                <div class="card-subtitulo"><?php echo htmlspecialchars($card['subtitulo']); ?></div>
                            </div>
                            <div class="favorite-icon" data-card-id="<?php echo $card['id']; ?>" style="cursor: pointer; z-index: 10;">
                                <i class="bi <?php echo $card['favorito'] ? 'bi-star-fill text-warning' : 'bi-star'; ?>"></i>
                                <span class="d-none d-md-inline ms-1 small">Favoritar</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($total_paginas > 1): ?>
            <nav aria-label="Navegação das páginas" class="mt-5 d-flex justify-content-center">
                <ul class="pagination shadow-sm">
                    <?php $query_params_page = $_GET; ?>
                    <li class="page-item <?php if($pagina_atual <= 1){ echo 'disabled'; } ?>">
                        <?php $query_params_page['page'] = $pagina_atual - 1; ?>
                        <a class="page-link" href="?<?php echo http_build_query($query_params_page); ?>">Anterior</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <?php $query_params_page['page'] = $i; ?>
                        <li class="page-item <?php if($pagina_atual == $i) { echo 'active-dynamic'; } ?>">
                            <a class="page-link" href="?<?php echo http_build_query($query_params_page); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php 
$custom_container_class = "container-custom-padding";
include 'footer_publico.php'; 
?>
</body>
</html>