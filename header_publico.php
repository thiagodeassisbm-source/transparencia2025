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
        <div class="d-flex justify-content-between align-items-start top-utility-bar">
            <div class="accessibility-bar-header d-flex align-items-center mb-3">
                <span class="me-2 d-none d-lg-inline text-white small opacity-75">ACESSIBILIDADE</span>
                <button id="font-increase" class="btn btn-xs btn-outline-light me-1" title="Aumentar Fonte">A+</button>
                <button id="font-reset" class="btn btn-xs btn-outline-light me-1" title="Fonte Padrão">A</button>
                <button id="font-decrease" class="btn btn-xs btn-outline-light me-2" title="Diminuir Fonte">A-</button>
                <button id="contrast-toggle" class="btn btn-xs btn-outline-light" title="Alto Contraste"><i class="bi bi-circle-half"></i></button>
            </div>
            <div class="login-admin-link">
                <a href="admin/login.php" class="btn btn-xs btn-outline-light"><i class="bi bi-lock-fill"></i> Acesso Restrito</a>
            </div>
        </div>

        <div class="banner-content text-center py-4">
            <?php if ($logo_src): ?>
                <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Brasão Prefeitura" class="prefeitura-logo-header mb-3">
            <?php endif; ?>
            <h2 class="portal-main-title"><?php echo htmlspecialchars($prefeitura_titulo); ?></h2>
            <p class="portal-subtitle">Acesso rápido e transparente às publicações municipais</p>
            
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-center">
                    <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                    <?php if (isset($page_title) && $page_title !== 'Transparência'): ?>
                        <li class="breadcrumb-item"><a href="index.php">Transparência</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($page_title); ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active" aria-current="page">Transparência</li>
                    <?php endif; ?>
                </ol>
            </nav>
            <h1><?php echo htmlspecialchars($page_title ?? 'Transparência'); ?></h1>
        </div>
    </div>
</header>
