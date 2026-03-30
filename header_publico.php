<?php
// /header_publico.php (Cabeçalho Centralizado para o Portal Público)
require_once 'conexao.php';

// Busca configurações da prefeitura ativa (SaaS)
// REFORÇO: Se o id não veio pelo bootstrap, tenta encontrar pelo slug na URL
if (!isset($id_prefeitura_ativa) || empty($id_prefeitura_ativa)) {
    $slug_para_busca = filter_input(INPUT_GET, 'pref_slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if ($slug_para_busca) {
        $stmt_find = $pdo->prepare("SELECT id, nome, slug FROM prefeituras WHERE slug = ?");
        $stmt_find->execute([$slug_para_busca]);
        $pref_encontrada = $stmt_find->fetch();
        if ($pref_encontrada) {
            $id_pref_header = $pref_encontrada['id'];
            $nome_pref_header = $pref_encontrada['nome'];
            $slug_pref_header = $pref_encontrada['slug'];
        }
    }
}

if (!isset($id_pref_header)) {
    $id_pref_header = $id_prefeitura_ativa ?? 0;
    $nome_pref_header = $nome_prefeitura_ativa ?? 'sua Cidade';
    $slug_pref_header = $slug_prefeitura_ativa ?? 'home';
}

try {
    $stmt_conf = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE id_prefeitura = ? AND chave IN ('prefeitura_titulo', 'prefeitura_logo', 'prefeitura_cor_principal', 'prefeitura_cor_secundaria')");
    $stmt_conf->execute([$id_pref_header]);
    $conf_data = $stmt_conf->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $prefeitura_titulo = !empty($conf_data['prefeitura_titulo']) ? $conf_data['prefeitura_titulo'] : 'Portal da Transparência de ' . $nome_pref_header;
    $prefeitura_logo = !empty($conf_data['prefeitura_logo']) ? $conf_data['prefeitura_logo'] : '';
    $cor_p = $conf_data['prefeitura_cor_principal'] ?? '#2ca444';
    $cor_s = $conf_data['prefeitura_cor_secundaria'] ?? '#1a4d1a';
} catch (Exception $e) { 
    $prefeitura_titulo = 'Portal da Transparência'; 
    $prefeitura_logo = ''; 
    $cor_p = '#2ca444';
    $cor_s = '#1a4d1a';
}

// Normaliza o caminho do logo para o frontend usando a BASE URL
$logo_src = '';
if (!empty($prefeitura_logo)) {
    $caminho_limpo = str_replace('../', '', $prefeitura_logo);
    $logo_src = $base_url . $caminho_limpo;
}

// Helper para links dentro do portal da prefeitura ativa
$portal_home = $base_url . "portal/" . $slug_pref_header;
?>
<style>
    :root {
        --cor-principal: <?php echo $cor_p; ?>;
        --cor-secundaria: <?php echo $cor_s; ?>;
    }
    .portal-main-banner { 
        background-color: var(--cor-principal) !important; 
        background-image: none !important;
    }
    .top-utility-bar-wrapper { 
        background-color: var(--cor-secundaria) !important; 
    }
    .search-box { 
        background-color: var(--cor-secundaria) !important; 
    }
    .btn-dynamic-primary {
        background-color: var(--cor-principal) !important;
        border-color: var(--cor-principal) !important;
        color: #fff !important;
    }
    .btn-dynamic-primary:hover {
        filter: brightness(0.9);
        color: #fff !important;
    }
    .active-dynamic .page-link {
        background-color: var(--cor-principal) !important;
        border-color: var(--cor-principal) !important;
        color: #fff !important;
    }
    .sidebar-menu a.active {
        color: var(--cor-secundaria) !important;
        background-color: rgba(0,0,0,0.03);
    }
    .acc-btn:hover {
        background: rgba(255, 255, 255, 0.1);
    }
</style>
<header>
    <div class="top-utility-bar-wrapper">
        <div class="container-fluid container-custom-padding d-flex justify-content-between align-items-center">
            <div class="breadcrumb-utility text-white small">
                <span>VOCÊ ESTÁ AQUI:</span> 
                <a href="<?php echo $portal_home; ?>" class="text-white fw-600 text-decoration-none ms-1">INÍCIO</a>
                <?php if (isset($page_title) && $page_title !== 'Transparência' && $page_title !== 'Início'): ?>
                    <span class="mx-1">/</span>
                    <span class="text-white opacity-75"><?php echo mb_strtoupper(htmlspecialchars($page_title)); ?></span>
                <?php endif; ?>
            </div>
            <div class="accessibility-bar d-flex align-items-center">
                <span class="accessibility-label me-3">ACESSIBILIDADE</span>
                
                <div class="acc-group d-flex">
                    <button id="font-increase" class="acc-btn" title="Aumentar Fonte">A+</button>
                    <button id="font-reset" class="acc-btn" title="Fonte Normal">A</button>
                    <button id="font-decrease" class="acc-btn" title="Diminuir Fonte">A-</button>
                    <button id="contrast-toggle" class="acc-btn ms-2" title="Alto Contraste">
                        <i class="bi bi-circle-half"></i>
                    </button>
                </div>

                <div class="ms-3 login-utility">
                    <a href="<?php echo $base_url; ?>admin/login.php" class="acc-btn login-btn" title="Acesso Administrativo">
                        <i class="bi bi-lock-fill"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="portal-main-banner text-center py-4">
        <div class="container-fluid container-custom-padding">
            <?php if ($logo_src): ?>
                <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Brasão Prefeitura" class="prefeitura-logo-header mb-2" style="max-height: 150px; width: auto; object-fit: contain;">
            <?php endif; ?>
            <h1 class="portal-main-title mb-1"><?php echo htmlspecialchars($prefeitura_titulo); ?></h1>
            <p class="portal-subtitle mb-0">Acesso rápido e transparente às publicações municipais</p>
        </div>
    </div>
</header>
