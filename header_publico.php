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
    <!-- Barra Utilitária Superior (Verde Escuro) -->
    <div class="top-utility-bar-wrapper">
        <div class="container-fluid container-custom-padding d-flex justify-content-between align-items-center">
            <div class="breadcrumb-utility text-white small">
                <span>VOCÊ ESTÁ AQUI:</span> 
                <a href="index.php" class="text-white fw-600 text-decoration-none ms-1">INÍCIO</a>
                <?php if (isset($page_title) && $page_title !== 'Transparência'): ?>
                    <span class="mx-1">/</span>
                    <span class="text-white opacity-75"><?php echo mb_strtoupper(htmlspecialchars($page_title)); ?></span>
                <?php endif; ?>
            </div>
            <div class="accessibility-bar d-flex align-items-center text-white small">
                <div class="me-3 d-flex align-items-center contrast-link" id="contrast-toggle">
                    <i class="bi bi-circle-half me-1"></i> ALTO CONTRASTE
                </div>
                <div class="d-flex align-items-center me-2">
                    <span class="me-2 opacity-75">FONTE:</span>
                    <button id="font-increase" class="btn btn-xs btn-outline-light me-1">A+</button>
                    <button id="font-reset" class="btn btn-xs btn-outline-light me-1">A</button>
                    <button id="font-decrease" class="btn btn-xs btn-outline-light">A-</button>
                </div>
                <div class="ms-2 d-flex gap-2">
                    <a href="admin/login.php" class="btn btn-xs btn-outline-light border-0"><i class="bi bi-lock-fill"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Banner Principal (Verde Claro) -->
    <div class="portal-main-banner text-center py-4">
        <div class="container-fluid container-custom-padding">
            <?php if ($logo_src): ?>
                <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Brasão Prefeitura" class="prefeitura-logo-header mb-2">
            <?php endif; ?>
            <h1 class="portal-main-title mb-1"><?php echo htmlspecialchars($prefeitura_titulo); ?></h1>
            <p class="portal-subtitle mb-0">Acesso rápido e transparente às publicações municipais</p>
        </div>
    </div>
</header>
