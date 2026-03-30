<?php
// /menu.php
if (!isset($pdo)) {
    require_once 'conexao.php';
}

// Garante que temos a prefeitura atual para os links
if (!isset($slug_prefeitura_ativa)) {
    $slug_contexto = filter_input(INPUT_GET, 'pref_slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'home';
} else {
    $slug_contexto = $slug_prefeitura_ativa;
}

$categorias = $pdo->query("SELECT id, nome, slug FROM categorias ORDER BY ordem ASC")->fetchAll();
$categoria_ativa_id = $categoria_id ?? ($_GET['categoria_id'] ?? 0); 
?>
<nav class="col-md-3 col-lg-2 d-md-block p-0 shadow-sm bg-white" style="z-index: 100;">
    <div class="sidebar position-sticky">
        <div class="search-box p-3">
            <h5 class="text-white small fw-bold text-uppercase mb-2">Buscar</h5>
            <form action="<?php echo $base_url; ?>busca.php" method="GET" class="d-flex bg-white rounded-pill overflow-hidden p-1">
                <input class="form-control border-0 ps-3" type="search" name="q" placeholder="O que deseja encontrar?" style="box-shadow: none; font-size: 0.85rem;">
                <button class="btn btn-link text-secondary" type="submit"><i class="bi bi-search"></i></button>
            </form>
        </div>
        
        <div class="p-3">
            <h4 class="sidebar-heading text-muted small fw-bold text-uppercase mb-3">Categorias</h4>
            <ul class="sidebar-menu list-unstyled" style="list-style: none !important; padding-left: 0 !important;">
                <li>
                    <a class="<?php echo ($categoria_ativa_id == 0) ? 'active' : ''; ?>" href="<?php echo $base_url; ?>portal/<?php echo $slug_contexto; ?>">
                        <i class="bi bi-grid-fill me-2 opacity-50"></i> Todas
                    </a>
                </li>
                
                <?php foreach ($categorias as $categoria): ?>
                    <?php 
                        // Links sempre absolutos via $base_url + rota amigável
                        $link_cat = !empty($categoria['slug']) 
                            ? $base_url . "portal/$slug_contexto/categoria/" . $categoria['slug'] 
                            : $base_url . "portal/$slug_contexto/?categoria_id=" . $categoria['id'];
                    ?>
                    <li>
                        <a class="<?php echo ($categoria['id'] == $categoria_ativa_id) ? 'active' : ''; ?>" href="<?php echo $link_cat; ?>">
                            <i class="bi bi-folder2-open me-2 opacity-50"></i> <?php echo htmlspecialchars($categoria['nome']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h4 class="sidebar-heading mt-5 text-muted small fw-bold text-uppercase mb-3">Administração</h4>
            <ul class="sidebar-menu list-unstyled">
                 <li>
                    <a href="<?php echo $base_url; ?>admin/login.php" class="text-primary fw-bold">
                        <i class="bi bi-lock-fill me-2"></i> Acesso Restrito
                    </a>
                 </li>
            </ul>
        </div>
    </div>
</nav>