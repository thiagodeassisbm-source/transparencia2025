<?php
require_once 'conexao.php';
require_once 'bootstrap_portal.php'; // Carrega o contexto da prefeitura ativa (SaaS)

// --- 1. CONFIGURAÇÕES DA PAGINAÇÃO ---
$itens_por_pagina = 12;
$pagina_atual = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
if ($pagina_atual < 1) { $pagina_atual = 1; }
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// --- 2. LÓGICA DE FILTROS, ORDENAÇÃO E FAVORITOS ---
$ip_usuario = $_SERVER['REMOTE_ADDR'];
$categoria_id = filter_input(INPUT_GET, 'categoria_id', FILTER_VALIDATE_INT);
$categoria_slug = filter_input(INPUT_GET, 'categoria_slug', FILTER_DEFAULT);

// Se tivermos apenas o slug, buscamos o ID correspondente
if (empty($categoria_id) && !empty($categoria_slug)) {
    $stmt_slug = $pdo->prepare("SELECT id FROM categorias WHERE slug = ? LIMIT 1");
    $stmt_slug->execute([$categoria_slug]);
    $categoria_id = $stmt_slug->fetchColumn();
}

$ordem_atual = $_GET['sort'] ?? 'padrao';

$sql_base = "FROM cards_informativos c LEFT JOIN portais p ON c.id_secao = p.id";

// Filtro por Prefeitura (Obrigatório no SaaS)
$sql_where = " WHERE p.id_prefeitura = ?";
$params_where[] = $id_prefeitura_ativa;

if ($categoria_id) {
    $sql_where .= " AND c.id_categoria = ?";
    $params_where[] = $categoria_id;
}

// --- 3. CONTAGEM TOTAL DE ITENS PARA A PAGINAÇÃO ---
$stmt_total = $pdo->prepare("SELECT COUNT(c.id) " . $sql_base . $sql_where);
$stmt_total->execute($params_where);
$total_itens = $stmt_total->fetchColumn();
$total_paginas = ceil($total_itens / $itens_por_pagina);

// --- 4. BUSCA DOS ITENS DA PÁGINA ATUAL ---
// ATUALIZAÇÃO: Favoritos são baseados em cookie ou na tabela favoritos_usuarios se desejar. 
// Mantendo a lógica de favoritos_usuarios via IP para consistência com o anterior.
$sql_select = "SELECT c.id, c.titulo, c.subtitulo, c.caminho_icone, c.tipo_icone, c.link_url, p.slug,
               (SELECT COUNT(*) FROM favoritos_usuarios fu WHERE fu.id_card = c.id AND fu.ip_usuario = ?) as favorito ";

$sql_order = "";
if ($ordem_atual === 'alpha') {
    $sql_order = " ORDER BY favorito DESC, c.titulo ASC";
} else {
    $sql_order = " ORDER BY favorito DESC, c.ordem ASC";
}
$sql_limit = " LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql_select . $sql_base . $sql_where . $sql_order . $sql_limit);
$params_final = array_merge([$ip_usuario], $params_where);
$params_final[] = $itens_por_pagina;
$params_final[] = $offset;

$stmt->execute($params_final);
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Lógica para o título da página ---
$page_title = 'Início';
if ($categoria_id) {
    $stmt_cat = $pdo->prepare("SELECT nome FROM categorias WHERE id = ?");
    $stmt_cat->execute([$categoria_id]);
    $categoria_atual = $stmt_cat->fetch();
    if ($categoria_atual) { $page_title = $categoria_atual['nome']; }
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
    
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light">

<?php 
include 'header_publico.php'; 
?>

<div class="container-fluid">
    <div class="row">
        <?php 
        // Passa o ID da categoria ativa para o menu.php
        $_GET['categoria_id'] = $categoria_id; 
        include 'menu.php'; 
        ?>
        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0 fw-bold"><?php echo htmlspecialchars($page_title); ?></h2>
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
                    <div class="alert alert-light border shadow-sm"><i class="bi bi-info-circle me-2"></i>Nenhum serviço disponível no momento para esta categoria.</div>
                <?php else: ?>
                    <?php foreach ($cards as $card): ?>
                        <?php
                        $link = !empty($card['link_url']) ? htmlspecialchars($card['link_url']) : (!empty($card['slug']) ? 'portal/' . $slug_prefeitura_ativa . '/' . htmlspecialchars($card['slug']) : '#');
                        $target = !empty($card['link_url']) ? '_blank' : '_self';
                        ?>
                        <div class="info-card border-0 shadow-sm">
                            <div class="icon-container position-relative">
                                <div class="dynamic-icon shadow-sm"></div>
                                <div class="dynamic-icon-text">
                                    <?php if (($card['tipo_icone'] ?? 'imagem') === 'bootstrap'): ?>
                                        <i class="bi <?php echo htmlspecialchars($card['caminho_icone']); ?>"></i>
                                    <?php elseif (!empty($card['caminho_icone'])): ?>
                                        <img src="<?php echo htmlspecialchars($card['caminho_icone']); ?>" alt="" style="width: 24px; height: 24px; object-fit: contain;">
                                    <?php else: ?>
                                        <i class="bi bi-info-circle"></i>
                                    <?php endif; ?>
                                </div>
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