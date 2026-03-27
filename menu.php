<?php
// /menu.php
if (!isset($pdo)) {
    require_once 'conexao.php';
}
$categorias = $pdo->query("SELECT id, nome, slug FROM categorias ORDER BY ordem ASC")->fetchAll();
$categoria_ativa_id = $categoria_id ?? ($_GET['categoria_id'] ?? 0); // Usa $categoria_id definida no index.php se existir
?>
<nav class="col-md-3 col-lg-2 d-md-block p-0">
    <div class="sidebar position-sticky">
        <div class="search-box">
            <h5>Buscar</h5>
            <form action="busca.php" method="GET" class="d-flex">
                <input class="form-control me-0" type="search" name="q" placeholder="Digite o que procura" aria-label="Buscar">
                <button class="btn" type="submit"><i class="bi bi-search"></i></button>
            </form>
        </div>
        
        <h4 class="sidebar-heading">Filtrar</h4>
        <ul class="sidebar-menu">
            <li><a class="<?php echo ($categoria_ativa_id == 0) ? 'active' : ''; ?>" href="portal/<?php echo $slug_prefeitura_ativa; ?>">Todas</a></li>
            
            <?php foreach ($categorias as $categoria): ?>
                <?php 
                    // Se tiver slug, usa a URL amigável, senão usa o parâmetro ID (fallback)
                    $link_cat = !empty($categoria['slug']) 
                        ? "portal/$slug_prefeitura_ativa/categoria/" . $categoria['slug'] 
                        : "portal/$slug_prefeitura_ativa/?categoria_id=" . $categoria['id'];
                ?>
                <li>
                    <a class="<?php echo ($categoria['id'] == $categoria_ativa_id) ? 'active' : ''; ?>" href="<?php echo $link_cat; ?>">
                        <?php echo htmlspecialchars($categoria['nome']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <h4 class="sidebar-heading mt-3">Administração</h4>
        <ul class="sidebar-menu">
             <li><a href="admin/index.php"><i class="bi bi-speedometer2 me-2"></i>Painel Administrativo</a></li>
        </ul>
    </div>
</nav>