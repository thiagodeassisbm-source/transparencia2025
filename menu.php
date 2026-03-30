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
<div class="sidebar-v2 bg-white shadow-sm h-100" style="border-radius: 12px; overflow: hidden;">
    <div class="sidebar-content">
        <div class="search-box p-3">
            <h5 class="text-white small fw-bold text-uppercase mb-2">Buscar</h5>
            <form action="<?php echo $base_url; ?>busca.php" method="GET" class="d-flex bg-white rounded-pill overflow-hidden p-1 shadow-sm">
                <input class="form-control border-0 ps-3" type="search" name="q" placeholder="Buscar..." style="box-shadow: none; font-size: 0.8rem;">
                <button class="btn btn-link text-secondary p-1" type="submit"><i class="bi bi-search"></i></button>
            </form>
        </div>
        
        <div class="p-3">
            <h6 class="sidebar-heading text-muted small fw-bold text-uppercase mb-3 px-2">Navegação</h6>
            <div class="sidebar-menu-v2">
                <a class="menu-item <?php echo ($categoria_ativa_id == 0) ? 'active' : ''; ?>" href="<?php echo $base_url; ?>portal/<?php echo $slug_contexto; ?>">
                    <i class="bi bi-grid-fill me-2 opacity-50"></i> Todas as Categorias
                </a>
                
                <?php foreach ($categorias as $categoria): ?>
                    <?php 
                        $link_cat = !empty($categoria['slug']) 
                            ? $base_url . "portal/$slug_contexto/categoria/" . $categoria['slug'] 
                            : $base_url . "portal/$slug_contexto/?categoria_id=" . $categoria['id'];
                    ?>
                    <a class="menu-item <?php echo ($categoria['id'] == $categoria_ativa_id) ? 'active' : ''; ?>" href="<?php echo $link_cat; ?>">
                        <i class="bi bi-folder2-open me-2 opacity-50"></i> <?php echo htmlspecialchars($categoria['nome']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <h6 class="sidebar-heading mt-5 text-muted small fw-bold text-uppercase mb-3 px-2">Acesso</h6>
            <div class="sidebar-menu-v2">
                <a href="<?php echo $base_url; ?>admin/login.php" class="menu-item text-primary fw-bold">
                    <i class="bi bi-lock-fill me-2"></i> Painel Administrativo
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para o menu lateral sem listas (DIV based) */
.sidebar-menu-v2 {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.menu-item {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    color: #495057;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.2s;
}
.menu-item:hover {
    background-color: #f1f3f5;
    color: var(--cor-principal, #212529);
}
.menu-item.active {
    background-color: rgba(var(--cor-principal-rgb, 13, 110, 253), 0.1);
    color: var(--cor-principal, #0d6efd);
    font-weight: 600;
}
.menu-item i {
    font-size: 1.1rem;
}
</style>