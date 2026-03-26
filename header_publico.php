<?php
// /header_publico.php (Cabeçalho Centralizado para o Portal Público)
require_once 'conexao.php';

// Busca configurações globais da prefeitura
try {
    $stmt_conf = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave IN ('prefeitura_titulo', 'prefeitura_logo')");
    $conf_data = $stmt_conf->fetchAll(PDO::FETCH_KEY_PAIR);
    $prefeitura_titulo = !empty($conf_data['prefeitura_titulo']) ? $conf_data['prefeitura_titulo'] : 'Portal da Transparência Municipal';
    $prefeitura_logo = !empty($conf_data['prefeitura_logo']) ? $conf_data['prefeitura_logo'] : '';
} catch (Exception $e) { $prefeitura_titulo = 'Portal da Transparência Municipal'; $prefeitura_logo = ''; }

// Normaliza o caminho do logo para o frontend (remove ../ se houver)
$logo_src = str_replace('../', '', $prefeitura_logo);
?>
<header class="portal-header-banner public-banner">
    <div class="container-fluid container-custom-padding">
        <div class="banner-content d-flex justify-content-between align-items-center py-2">
            <div class="d-flex align-items-center">
                <?php if ($logo_src): ?>
                    <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Brasão Prefeitura" class="prefeitura-logo-header me-3 mb-0">
                <?php endif; ?>
                <div class="text-start">
                    <h2 class="portal-main-title mb-0"><?php echo htmlspecialchars($prefeitura_titulo); ?></h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0" style="background:none; padding:0; backdrop-filter:none; border:none;">
                            <li class="breadcrumb-item"><a href="index.php" class="small opacity-75">Início</a></li>
                            <?php if (isset($page_title) && $page_title !== 'Transparência'): ?>
                                <li class="breadcrumb-item"><a href="index.php" class="small opacity-75">Transparência</a></li>
                                <li class="breadcrumb-item active small" aria-current="page"><?php echo htmlspecialchars($page_title); ?></li>
                            <?php else: ?>
                                <li class="breadcrumb-item active small" aria-current="page">Transparência</li>
                            <?php endif; ?>
                        </ol>
                    </nav>
                </div>
            </div>
            <div class="accessibility-bar-header d-flex align-items-center">
                <span class="me-2 d-none d-lg-inline text-white small opacity-75">ACESSIBILIDADE</span>
                <button id="font-increase" class="btn btn-xs btn-outline-light me-1" title="Aumentar Fonte">A+</button>
                <button id="font-reset" class="btn btn-xs btn-outline-light me-1" title="Fonte Padrão">A</button>
                <button id="font-decrease" class="btn btn-xs btn-outline-light me-2" title="Diminuir Fonte">A-</button>
                <button id="contrast-toggle" class="btn btn-xs btn-outline-light me-3" title="Alto Contraste"><i class="bi bi-circle-half"></i></button>
                <a href="admin/login.php" class="btn btn-xs btn-outline-light"><i class="bi bi-lock-fill"></i></a>
            </div>
        </div>
    </div>
</header>
