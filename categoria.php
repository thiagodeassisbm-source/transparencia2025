<?php
// /categoria.php
require_once 'conexao.php';
require_once 'bootstrap_portal.php';

// Redirecionamento amigável se acessado via ID legado (opcional)
$categoria_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$categoria_slug = filter_input(INPUT_GET, 'categoria_slug', FILTER_DEFAULT);

if (empty($categoria_id) && !empty($categoria_slug)) {
    $stmt_slug = $pdo->prepare("SELECT id FROM categorias WHERE slug = ? LIMIT 1");
    $stmt_slug->execute([$categoria_slug]);
    $categoria_id = $stmt_slug->fetchColumn();
}

if (!$categoria_id) { die("Categoria não especificada."); }

// Busca o nome da categoria para o título da página
$stmt_cat = $pdo->prepare("SELECT nome FROM categorias WHERE id = ?");
$stmt_cat->execute([$categoria_id]);
$categoria_data = $stmt_cat->fetch();
if (!$categoria_data) { die("Categoria não encontrada."); }

$page_title = $categoria_data['nome'];

// Busca todos os CARDS que pertencem a esta categoria (respeitando o SaaS)
$stmt_cards = $pdo->prepare("SELECT c.id, c.titulo, c.subtitulo, c.caminho_icone, c.tipo_icone, c.link_url, p.slug 
                             FROM cards_informativos c 
                             LEFT JOIN portais p ON c.id_secao = p.id 
                             WHERE c.id_categoria = ? AND (c.id_prefeitura = ? OR p.id_prefeitura = ?)
                             ORDER BY c.ordem ASC");
$stmt_cards->execute([$categoria_id, $id_prefeitura_ativa, $id_prefeitura_ativa]);
$cards = $stmt_cards->fetchAll();

$_GET['categoria_id'] = $categoria_id; 
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
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

<div class="container-fluid">
    <div class="row">
        <?php include 'menu.php'; ?>

        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0 fw-bold"><?php echo htmlspecialchars($page_title); ?></h2>
                <span class="text-muted small"><?php echo count($cards); ?> itens encontrados</span>
            </div>

            <div class="info-card-list">
                <?php if (empty($cards)): ?>
                    <div class="alert alert-light border shadow-sm"><i class="bi bi-info-circle me-2"></i>Nenhum serviço disponível no momento para esta categoria.</div>
                <?php else: ?>
                    <?php foreach ($cards as $card): ?>
                        <?php
                        $is_external = !empty($card['link_url']);
                        $link = '#';
                        $target = '_self';

                        if ($is_external) {
                            $link_raw = $card['link_url'];
                            if (strpos($link_raw, '.php') !== false && strpos($link_raw, 'http') === false) {
                                $link = $base_url . 'portal/' . $slug_prefeitura_ativa . '/' . ltrim($link_raw, '/');
                            } else {
                                $link = htmlspecialchars($link_raw);
                                $target = '_blank';
                            }
                        } elseif (!empty($card['slug'])) {
                            $link = $base_url . 'portal/' . $slug_prefeitura_ativa . '/' . htmlspecialchars($card['slug']);
                        }
                        ?>
                        <div class="info-card border-0 shadow-sm">
                            <div class="icon-container position-relative">
                                <div class="dynamic-icon shadow-sm"></div>
                                <div class="dynamic-icon-text">
                                    <?php if (($card['tipo_icone'] ?? 'imagem') === 'bootstrap'): ?>
                                        <i class="bi <?php echo htmlspecialchars($card['caminho_icone']); ?>"></i>
                                    <?php elseif (!empty($card['caminho_icone'])): ?>
                                        <img src="<?php echo $base_url . str_replace('../', '', $card['caminho_icone']); ?>" alt="" style="width: 24px; height: 24px; object-fit: contain;">
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
                            <div class="favorite-icon">
                                <i class="bi bi-chevron-right opacity-50"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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