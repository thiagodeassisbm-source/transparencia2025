<?php
// /index.php (Página Inicial Pública - Versão Definitiva Corrigida)
// Teste de Deploy Hostinger - Webhook
require_once 'conexao.php';

// --- CÓDIGO CORRIGIDO PARA BUSCAR O TÍTULO DA PREFEITURA ---
$prefeitura_titulo = ''; // Define um valor padrão
try {
    // A consulta agora busca na coluna 'valor' ONDE a coluna 'chave' é 'prefeitura_titulo'
    $stmt_pref = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = ? LIMIT 1");
    $stmt_pref->execute(['prefeitura_titulo']);
    
    $config = $stmt_pref->fetch(PDO::FETCH_ASSOC);

    // Verificamos e usamos a coluna 'valor'
    if ($config && !empty($config['valor'])) {
        $prefeitura_titulo = $config['valor'];
    }
} catch (PDOException $e) {
    // Em um ambiente de produção, é melhor registrar o erro do que exibi-lo na tela.
    error_log("Erro ao buscar configuração da prefeitura: " . $e->getMessage());
}
// --- FIM DO CÓDIGO CORRIGIDO ---


ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- 1. CONFIGURAÇÕES DA PAGINAÇÃO ---
$itens_por_pagina = 10;
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
$sql_where = "";
$params_where = [];

if ($categoria_id) {
    $sql_where = " WHERE c.id_categoria = ?";
    $params_where[] = $categoria_id;
}

// --- 3. CONTAGEM TOTAL DE ITENS PARA A PAGINAÇÃO ---
$stmt_total = $pdo->prepare("SELECT COUNT(c.id) " . $sql_base . $sql_where);
$stmt_total->execute($params_where);
$total_itens = $stmt_total->fetchColumn();
$total_paginas = ceil($total_itens / $itens_por_pagina);

// --- 4. BUSCA DOS ITENS DA PÁGINA ATUAL ---
$sql_select = "SELECT c.id, c.titulo, c.subtitulo, c.caminho_icone, c.link_url, p.slug,
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

// Executa a query com o array de parâmetros
$stmt->execute($params_final);
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Lógica para o título da página ---
$page_title = 'Transparência';
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
<body>

<header class="page-header">
    <div class="container-fluid container-custom-padding">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h2 class="h4 fw-bold text-white mb-2 font-poppins"><?php echo htmlspecialchars($prefeitura_titulo); ?></h2>
                
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($page_title); ?></li>
                    </ol>
                </nav>
                <h1><?php echo htmlspecialchars($page_title); ?></h1>
            </div>
            <div class="accessibility-bar-header d-flex align-items-center pt-2">
                <span class="me-2 d-none d-lg-inline text-white" style="font-size: 0.8rem;">ACESSIBILIDADE</span>
                <button id="font-increase" class="btn btn-sm btn-outline-light me-1" title="Aumentar Fonte">A+</button>
                <button id="font-reset" class="btn btn-sm btn-outline-light me-1" title="Fonte Padrão">A</button>
                <button id="font-decrease" class="btn btn-sm btn-outline-light me-2" title="Diminuir Fonte">A-</button>
                <button id="contrast-toggle" class="btn btn-sm btn-outline-light" title="Alto Contraste"><i class="bi bi-circle-half"></i></button>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid">
    <div class="row">
        <?php 
        $_GET['id'] = $categoria_id ?? 0;
        include 'menu.php'; 
        ?>
        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span><?php echo $total_itens; ?> itens encontrados.</span>
                <div>
                    <?php
                        $query_params_sort = $_GET;
                        unset($query_params_sort['sort'], $query_params_sort['page']);
                    ?>
                    <a href="?<?php echo http_build_query($query_params_sort); ?>" class="btn btn-sm <?php echo ($ordem_atual === 'padrao') ? 'btn-success' : 'btn-outline-secondary'; ?>">Mais Acessados</a>
                    <?php $query_params_sort['sort'] = 'alpha'; ?>
                    <a href="?<?php echo http_build_query($query_params_sort); ?>" class="btn btn-sm <?php echo ($ordem_atual === 'alpha') ? 'btn-success' : 'btn-outline-secondary'; ?>">Ordem Alfabética</a>
                </div>
            </div>
            <div class="info-card-list">
                <?php if (empty($cards)): ?>
                    <div class="alert alert-light">Nenhum card informativo foi encontrado.</div>
                <?php else: ?>
                    <?php foreach ($cards as $card): ?>
                        <?php
                        $link = !empty($card['link_url']) ? htmlspecialchars($card['link_url']) : (!empty($card['slug']) ? 'portal.php?slug=' . htmlspecialchars($card['slug']) : '#');
                        $target = !empty($card['link_url']) ? '_blank' : '_self';
                        ?>
                        <div class="info-card">
                            <div class="icon-container">
                                <img src="<?php echo htmlspecialchars(str_replace('../', '', $card['caminho_icone'])); ?>" alt="">
                            </div>
                            <div class="text-container">
                                <div class="card-titulo">
                                    <a href="<?php echo $link; ?>" class="stretched-link text-decoration-none text-reset" target="<?php echo $target; ?>">
                                        <?php echo htmlspecialchars($card['titulo']); ?>
                                    </a>
                                </div>
                                <div class="card-subtitulo"><?php echo htmlspecialchars($card['subtitulo']); ?></div>
                            </div>
                            <div class="favorite-icon" data-card-id="<?php echo $card['id']; ?>" style="cursor: pointer;">
                                <i class="bi <?php echo $card['favorito'] ? 'bi-star-fill text-warning' : 'bi-star'; ?>"></i>
                                <span class="d-none d-md-inline ms-1">Favoritar</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if ($total_paginas > 1): ?>
            <nav aria-label="Navegação das páginas" class="mt-4 d-flex justify-content-center">
                <ul class="pagination">
                    <?php $query_params_page = $_GET; ?>
                    <li class="page-item <?php if($pagina_atual <= 1){ echo 'disabled'; } ?>">
                        <?php $query_params_page['page'] = $pagina_atual - 1; ?>
                        <a class="page-link" href="?<?php echo http_build_query($query_params_page); ?>">Anterior</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <?php $query_params_page['page'] = $i; ?>
                        <li class="page-item <?php if($pagina_atual == $i) { echo 'active'; } ?>">
                            <a class="page-link" href="?<?php echo http_build_query($query_params_page); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php if($pagina_atual >= $total_paginas) { echo 'disabled'; } ?>">
                        <?php $query_params_page['page'] = $pagina_atual + 1; ?>
                        <a class="page-link" href="?<?php echo http_build_query($query_params_page); ?>">Próximo</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<footer class="p-3 mt-4">
    <div class="container-fluid container-custom-padding">
        <div class="d-flex justify-content-between align-items-center" style="font-size: 14px;">
            <div>
                &copy; <?php echo date('Y'); ?> - Todos os direitos reservados.
            </div>
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
document.addEventListener('DOMContentLoaded', function () {
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

    const favoriteIcons = document.querySelectorAll('.favorite-icon');
    favoriteIcons.forEach(iconDiv => {
        iconDiv.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const cardId = this.dataset.cardId;
            const starIcon = this.querySelector('i');
            starIcon.classList.toggle('bi-star');
            starIcon.classList.toggle('bi-star-fill');
            starIcon.classList.toggle('text-warning');
            fetch('favoritar_publico.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ card_id: cardId })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Ocorreu um erro ao salvar o favorito. Tente novamente.');
                    starIcon.classList.toggle('bi-star');
                    starIcon.classList.toggle('bi-star-fill');
                    starIcon.classList.toggle('text-warning');
                }
            });
        });
    });
});
</script>

</body>
</html>