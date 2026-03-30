<?php
// /bootstrap_portal.php
require_once 'conexao.php';

// 1. Detecta o Slug da Prefeitura na URL (passado via .htaccess)
$pref_slug = filter_input(INPUT_GET, 'pref_slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// 2. Se não houver slug na URL, tenta pegar da sessão (caso o admin esteja alternando prefeituras)
if (!$pref_slug && isset($_SESSION['id_prefeitura'])) {
    $stmt_pref = $pdo->prepare("SELECT slug FROM prefeituras WHERE id = ?");
    $stmt_pref->execute([$_SESSION['id_prefeitura']]);
    $pref_slug = $stmt_pref->fetchColumn();
}

// 3. Busca os dados da prefeitura ativa pelo slug
if ($pref_slug) {
    $stmt_pref_val = $pdo->prepare("SELECT * FROM prefeituras WHERE slug = ?");
    $stmt_pref_val->execute([$pref_slug]);
    $prefeitura_ativa = $stmt_pref_val->fetch();
}

// 4. Se não encontrou nenhuma prefeitura, não define prefeitura ativa (permitindo landing page)
if (!$prefeitura_ativa) {
    $id_prefeitura_ativa = null;
    $nome_prefeitura_ativa = null;
    $slug_prefeitura_ativa = null;
} else {
    // 5. Define as variáveis globais para uso em todo o portal
    $id_prefeitura_ativa = $prefeitura_ativa['id'];
    $nome_prefeitura_ativa = $prefeitura_ativa['nome'];
    $slug_prefeitura_ativa = $prefeitura_ativa['slug'];
}

// 6. Atualiza a URL base para as páginas públicas (Inteligente para Whitelabel)
// Se for um domínio customizado, a base é a raiz. Se for via UP GYN, a base é /portal/slug/
$is_custom_domain = ($domain != 'www.upgyn.com.br' && $domain != 'upgyn.com.br');
$base_portal_url = $is_custom_domain ? $base_url : ($base_url . "portal/" . $slug_prefeitura_ativa . "/");
?>
