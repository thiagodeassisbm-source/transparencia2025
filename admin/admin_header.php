<?php
// /admin/admin_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpeza automática silenciosa de clones falhos/testes a cada load logado
try {
    global $pdo;
    if (isset($pdo) && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $pdo->exec("
            DELETE c1 FROM cards_informativos c1
            INNER JOIN cards_informativos c2 
            WHERE c1.id > c2.id 
              AND c1.id_categoria = c2.id_categoria 
              AND c1.titulo = c2.titulo 
              AND c1.id_prefeitura = c2.id_prefeitura
        ");
        $pdo->exec("
            DELETE FROM cards_informativos 
            WHERE titulo IN ('Teste Lista de Creche', 'Acesso Link', 'Testes', 'Vacinação da Covid-19', 'Informações Institucionais')
        ");
    }
} catch(Exception $e) {}

// Re-carrega permissões se necessário (opcional, para refletir mudanças imediatas)
require_once 'auth_check.php';
require_once 'functions_logs.php';

$nome_usuario_logado = $_SESSION['admin_user_nome'] ?? 'Usuário';
$id_usuario_logado = $_SESSION['admin_user_id'] ?? 0;
$perfil_nome = $_SESSION['admin_user_perfil_nome'] ?? 'Perfil';
$nome_completo_boas_vindas = trim((string)($_SESSION['admin_user_nome_real'] ?? ''));
if ($nome_completo_boas_vindas === '') {
    $nome_completo_boas_vindas = $nome_usuario_logado;
}

// Define um título padrão para a página
$page_title_for_header = $page_title_for_header ?? 'Painel Administrativo';
$super_pages = ['super_dashboard.php', 'super_logs.php', 'cadastrar_prefeitura.php', 'editar_prefeitura.php', 'gerenciar_prefeituras.php', 'switch_pref.php', 'alterar_status_pref.php', 'gerenciar_landing_recursos.php', 'editar_landing_recurso.php', 'gerenciar_mensagens.php', 'enviar_mensagem.php', 'configurar_copyright.php', 'configurar_smtp.php', 'gerenciar_superadmins.php', 'editar_usuario.php', 'debug_clone_ambiente.php'];
$is_super_context = in_array(basename($_SERVER['PHP_SELF']), $super_pages) && isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] === 1;

// Função para verificar se a página atual é a ativa
function isActive($pageName) {
    if (is_array($pageName)) {
        $currentFile = basename($_SERVER['PHP_SELF']);
        return in_array($currentFile, $pageName) ? 'active' : '';
    }
    $currentFile = basename($_SERVER['PHP_SELF']);
    return $currentFile == $pageName ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title_for_header); ?> - Transparência</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="admin_style.css?v=<?php echo time(); ?>">
    <script>
    (function () {
        try {
            var n = parseInt(localStorage.getItem('fontSize'), 10);
            if (!isNaN(n) && n >= 12 && n <= 32) {
                document.documentElement.style.fontSize = n + 'px';
            }
            if (localStorage.getItem('highContrast') === 'true') {
                document.documentElement.classList.add('high-contrast');
            }
        } catch (e) {}
    })();
    </script>
</head>
<body class="<?php echo $is_super_context ? 'superadmin-theme' : ''; ?>">

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar shadow">
        <div class="sidebar-header">
            <h2>Painel de controle</h2>
        </div>
        <nav class="sidebar-nav">
            <?php 
            if ($is_super_context && isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] === 1): 
            ?>
                <!-- MENU EXCLUSIVO SUPER ADMIN (VISÃO GLOBAL) -->
                <ul>
                    <li>
                        <a href="super_dashboard.php" class="nav-link <?php echo isActive('super_dashboard.php'); ?>">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <?php $cad_active = isActive(['gerenciar_prefeituras.php', 'cadastrar_prefeitura.php', 'editar_prefeitura.php']); ?>
                        <a href="#collapseSuperCad" class="nav-link has-submenu <?php echo $cad_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                            <span><i class="bi bi-plus-circle"></i> Gerenciar</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <div class="collapse <?php echo $cad_active ? 'show' : ''; ?>" id="collapseSuperCad">
                            <ul class="submenu">
                                <li><a href="gerenciar_prefeituras.php" class="nav-link <?php echo isActive(['gerenciar_prefeituras.php', 'editar_prefeitura.php']); ?>">Prefeituras</a></li>
                                <li><a href="cadastrar_prefeitura.php" class="nav-link <?php echo isActive('cadastrar_prefeitura.php'); ?>">Nova Prefeitura</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <a href="super_logs.php" class="nav-link <?php echo isActive('super_logs.php'); ?>">
                            <i class="bi bi-activity"></i> Auditoria Global
                        </a>
                    </li>
                    <li>
                        <a href="debug_clone_ambiente.php" class="nav-link <?php echo isActive('debug_clone_ambiente.php'); ?>">
                            <i class="bi bi-bug"></i> Debug clonagem
                        </a>
                    </li>
                    <li>
                        <?php $landing_active = isActive(['gerenciar_landing_recursos.php', 'editar_landing_recurso.php']); ?>
                        <a href="#collapseLanding" class="nav-link has-submenu <?php echo $landing_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                            <span><i class="bi bi-window-stack"></i> Página Principal</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <div class="collapse <?php echo $landing_active ? 'show' : ''; ?>" id="collapseLanding">
                            <ul class="submenu">
                                <li><a href="gerenciar_landing_recursos.php" class="nav-link <?php echo isActive(['gerenciar_landing_recursos.php', 'editar_landing_recurso.php']); ?>">Recursos</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <?php $comm_active = isActive(['gerenciar_mensagens.php', 'enviar_mensagem.php']); ?>
                        <a href="#collapseComm" class="nav-link has-submenu <?php echo $comm_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                            <span><i class="bi bi-chat-left-text"></i> Comunicação</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <div class="collapse <?php echo $comm_active ? 'show' : ''; ?>" id="collapseComm">
                            <ul class="submenu">
                                <li><a href="gerenciar_mensagens.php" class="nav-link <?php echo isActive(['gerenciar_mensagens.php', 'enviar_mensagem.php']); ?>">Mensagens</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <?php $config_active = isActive(['configurar_copyright.php', 'gerenciar_superadmins.php']); ?>
                        <a href="#collapseConfigGlobal" class="nav-link has-submenu <?php echo $config_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                            <span><i class="bi bi-gear"></i> Configurações</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <div class="collapse <?php echo $config_active ? 'show' : ''; ?>" id="collapseConfigGlobal">
                            <ul class="submenu">
                                <li><a href="configurar_copyright.php" class="nav-link <?php echo isActive('configurar_copyright.php'); ?>">Copyright / Rodapé</a></li>
                                <li><a href="configurar_smtp.php" class="nav-link <?php echo isActive('configurar_smtp.php'); ?>">Configuração SMTP</a></li>
                                <li><a href="gerenciar_superadmins.php" class="nav-link <?php echo isActive('gerenciar_superadmins.php'); ?>">Usuários Superadmin</a></li>
                            </ul>
                        </div>
                    </li>
                    <li class="mt-4">
                        <a href="logout.php" class="nav-link text-danger">
                            <i class="bi bi-box-arrow-left"></i> Sair da Central
                        </a>
                    </li>
                </ul>
            <?php else: ?>
                <!-- MENU NORMAL (ADMIN/EDITOR) -->
                <ul>
                    <?php if (tem_permissao('dashboard', 'ver')): ?>
                    <li>
                        <a href="dashboard.php" class="nav-link <?php echo isActive('dashboard.php'); ?>">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] === 1): ?>
                    <li>
                        <a href="super_dashboard.php" class="nav-link text-warning fw-bold border border-warning border-opacity-25 rounded m-2">
                            <i class="bi bi-shield-lock-fill"></i> Central Super Admin
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>

                <ul class="mt-3">
                    <li class="px-3 mb-2 small text-muted text-uppercase fw-bold">Conteúdo</li>
                    <?php 
                    // Lógica para carregar as seções permitidas para este usuário
                    $pref_id = $_SESSION['id_prefeitura'];
                    $has_global_secoes = tem_permissao('secoes', 'ver');
                    
                    // Busca todas as seções desta prefeitura (e as globais) e agrupa se forem permitidas
                    $stmt_menu_secoes = $pdo->prepare("
                        SELECT p.id, p.nome as portal_nome, c.nome as categoria_nome 
                        FROM portais p 
                        LEFT JOIN categorias c ON p.id_categoria = c.id 
                        WHERE (p.id_prefeitura = :pref_id OR p.id_prefeitura IS NULL)
                        ORDER BY c.ordem ASC, p.nome ASC
                    ");
                    $stmt_menu_secoes->execute([':pref_id' => $pref_id]);
                    $secoes_menu = $stmt_menu_secoes->fetchAll();
                    
                    $permitted_in_menu = [];
                    foreach ($secoes_menu as $sm) {
                        if (tem_permissao('form_' . $sm['id'], 'ver')) {
                            $permitted_in_menu[$sm['categoria_nome'] ?? 'Outros'][] = $sm;
                        }
                    }
                    
                    if ($has_global_secoes || !empty($permitted_in_menu)): 
                    ?>
                    <li>
                        <?php $lancamentos_active = isActive(['index.php', 'ver_lancamentos.php', 'lancar_dados.php']); ?>
                        <a href="#collapseLancamentos" class="nav-link has-submenu <?php echo $lancamentos_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                            <span><i class="bi bi-journal-text"></i> Lançamentos</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <div class="collapse <?php echo $lancamentos_active ? 'show' : ''; ?>" id="collapseLancamentos">
                            <ul class="submenu">
                                <?php foreach ($permitted_in_menu as $cat_label => $items): ?>
                                    <li class="submenu-header text-uppercase opacity-50 small ps-3 mt-2" style="font-size: 0.65rem;"><?php echo htmlspecialchars($cat_label); ?></li>
                                    <?php foreach ($items as $it): ?>
                                        <li><a href="ver_lancamentos.php?portal_id=<?php echo $it['id']; ?>" class="nav-link <?php echo (isset($_GET['portal_id']) && $_GET['portal_id'] == $it['id']) ? 'active' : ''; ?>"><?php echo htmlspecialchars($it['portal_nome']); ?></a></li>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>

                    <?php if (tem_permissao('secoes', 'editar') || tem_permissao('estrutura', 'editar')): ?>
                    <li>
                        <?php $cadastros_active = isActive(['criar_secoes.php', 'gerenciar_categorias.php', 'gerenciar_tipos_documento.php', 'gerenciar_cards.php']); ?>
                        <a href="#collapseCadastros" class="nav-link has-submenu <?php echo $cadastros_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                            <span><i class="bi bi-folder-plus"></i> Estrutura</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <div class="collapse <?php echo $cadastros_active ? 'show' : ''; ?>" id="collapseCadastros">
                            <ul class="submenu">
                                <li><a href="criar_secoes.php" class="nav-link <?php echo isActive('criar_secoes.php'); ?>">Criar Seção / Card</a></li>
                                <li><a href="gerenciar_paginas.php" class="nav-link <?php echo isActive(['gerenciar_paginas.php', 'editor_pagina.php']); ?>">Páginas de Conteúdo</a></li>
                                <li><a href="gerenciar_categorias.php" class="nav-link <?php echo isActive('gerenciar_categorias.php'); ?>">Categorias</a></li>
                                <li><a href="gerenciar_tipos_documento.php" class="nav-link <?php echo isActive('gerenciar_tipos_documento.php'); ?>">Tipos de Doc.</a></li>
                                <li><a href="gerenciar_cards.php" class="nav-link <?php echo isActive('gerenciar_cards.php'); ?>">Gerenciar Cards</a></li>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>

                    <?php if (tem_permissao('sic', 'ver')): ?>
                    <li>
                        <?php $esic_active = isActive(['sic_inbox.php', 'configuracoes_sic.php', 'responder_esic.php']); ?>
                        <a href="#collapseESic" class="nav-link has-submenu <?php echo $esic_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                            <span><i class="bi bi-mailbox"></i> E-Sic</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <div class="collapse <?php echo $esic_active ? 'show' : ''; ?>" id="collapseESic">
                            <ul class="submenu">
                                <li><a href="sic_inbox.php" class="nav-link <?php echo isActive(['sic_inbox.php', 'responder_esic.php']); ?>">Caixa de Entrada</a></li>
                                <?php if (tem_permissao('sic', 'editar')): ?>
                                    <li><a href="configuracoes_sic.php" class="nav-link <?php echo isActive('configuracoes_sic.php'); ?>">Configurações</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>

                    <?php if (tem_permissao('ouvidoria', 'ver')): ?>
                    <li>
                        <?php $ouvidoria_active = isActive(['ouvidoria_inbox.php', 'responder_manifestacao.php', 'config_ouvidoria.php']); ?>
                        <a href="#collapseOuvidoria" class="nav-link has-submenu <?php echo $ouvidoria_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                            <span><i class="bi bi-chat-dots"></i> Ouvidoria</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <div class="collapse <?php echo $ouvidoria_active ? 'show' : ''; ?>" id="collapseOuvidoria">
                            <ul class="submenu">
                                <li><a href="ouvidoria_inbox.php" class="nav-link <?php echo isActive(['ouvidoria_inbox.php', 'responder_manifestacao.php']); ?>">Caixa de Entrada</a></li>
                                <?php if (tem_permissao('ouvidoria', 'editar')): ?>
                                    <li><a href="config_ouvidoria.php" class="nav-link <?php echo isActive('config_ouvidoria.php'); ?>">Configurações</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>

                    <?php if (tem_permissao('configuracoes', 'ver') || tem_permissao('prefeitura', 'ver') || $_SESSION['admin_user_perfil'] === 'admin'): ?>
                    <li>
                        <?php $prefeitura_active = isActive(['informacoes_prefeitura.php', 'configurar_copyright.php', 'configurar_smtp.php']); ?>
                        <a href="#collapsePrefeitura" class="nav-link has-submenu <?php echo $prefeitura_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                            <span><i class="bi bi-building"></i> Prefeitura</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <div class="collapse <?php echo $prefeitura_active ? 'show' : ''; ?>" id="collapsePrefeitura">
                            <ul class="submenu">
                                <li><a href="informacoes_prefeitura.php" class="nav-link <?php echo isActive('informacoes_prefeitura.php'); ?>">Identidade & Cores</a></li>
                                <li><a href="configurar_smtp.php" class="nav-link <?php echo isActive('configurar_smtp.php'); ?>">E-mail (SMTP)</a></li>
                                <li><a href="configurar_copyright.php" class="nav-link <?php echo isActive('configurar_copyright.php'); ?>">Rodapé & Copyright</a></li>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>

                    <?php if (tem_permissao('relatorios', 'ver') || $_SESSION['admin_user_perfil'] === 'admin' || $_SESSION['admin_user_nome'] === 'admin'): ?>
                    <li>
                        <?php $relatorios_active = isActive(['relatorio_publicacoes.php']); ?>
                        <a href="#collapseRelatorios" class="nav-link has-submenu <?php echo $relatorios_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                            <span><i class="bi bi-bar-chart-line"></i> Relatórios</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <div class="collapse <?php echo $relatorios_active ? 'show' : ''; ?>" id="collapseRelatorios">
                            <ul class="submenu">
                                <li><a href="relatorio_publicacoes.php" class="nav-link <?php echo isActive('relatorio_publicacoes.php'); ?>">Relatório de Publicações</a></li>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>

                <ul class="mt-4">
                    <li class="px-3 mb-2 small text-muted text-uppercase fw-bold">Configurações</li>
                    <?php if ($_SESSION['admin_user_perfil'] === 'admin'): ?>
                    <li>
                        <?php $entrada_dados_active = isActive(['importar_xml.php', 'mapear_xml.php', 'preview_importacao.php', 'processar_importacao_final.php', 'gerenciar_tipos_xml.php']); ?>
                        <a href="#collapseEntradaDados" class="nav-link has-submenu <?php echo $entrada_dados_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                            <span><i class="bi bi-database-add"></i> Entrada de Dados</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <div class="collapse <?php echo $entrada_dados_active ? 'show' : ''; ?>" id="collapseEntradaDados">
                            <ul class="submenu">
                                <li><a href="gerenciar_tipos_xml.php" class="nav-link <?php echo isActive('gerenciar_tipos_xml.php'); ?>">Gerenciar XML</a></li>
                                <li><a href="importar_xml.php" class="nav-link <?php echo isActive(['importar_xml.php', 'mapear_xml.php', 'preview_importacao.php', 'processar_importacao_final.php']); ?>">Importar XML</a></li>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (tem_permissao('usuarios', 'ver') || $_SESSION['admin_user_perfil'] === 'admin'): ?>
                    <li>
                        <?php $admin_config_active = isActive(['gerenciar_usuarios.php', 'editar_usuario.php', 'gerenciar_perfis.php', 'editar_permissoes_perfil.php', 'logs_gerais.php']); ?>
                        <a href="#collapseConfig" class="nav-link has-submenu <?php echo $admin_config_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                            <span><i class="bi bi-gear-fill"></i> ADMINISTRAÇÃO</span>
                            <i class="bi bi-chevron-down submenu-arrow"></i>
                        </a>
                        <div class="collapse <?php echo $admin_config_active ? 'show' : ''; ?>" id="collapseConfig">
                            <ul class="submenu">
                                <?php if (tem_permissao('usuarios', 'ver')): ?>
                                    <li><a href="gerenciar_usuarios.php" class="nav-link <?php echo isActive(['gerenciar_usuarios.php', 'editar_usuario.php']); ?>">Usuários</a></li>
                                <?php endif; ?>
                                <?php if ($_SESSION['admin_user_perfil'] === 'admin'): ?>
                                    <li><a href="gerenciar_perfis.php" class="nav-link <?php echo isActive(['gerenciar_perfis.php', 'editar_permissoes_perfil.php']); ?>">Perfis e Acessos</a></li>
                                    <li class="border-top my-1 opacity-25"></li>
                                    <li><a href="logs_gerais.php" class="nav-link <?php echo isActive('logs_gerais.php'); ?>">Auditória</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>

                <ul class="mt-4">
                    <li>
                        <a href="logout.php" class="nav-link text-danger fw-bold">
                            <i class="bi bi-box-arrow-left"></i> Sair do Painel
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left d-flex flex-wrap align-items-center">
                <p class="topbar-welcome mb-0" role="status">Bem-vindo, <strong><?php echo htmlspecialchars($nome_completo_boas_vindas); ?></strong></p>
            </div>
            <div class="topbar-right d-flex align-items-center flex-wrap justify-content-end gap-2 gap-md-3">
                <div class="dropdown">
                    <a href="#" class="user-profile dropdown-toggle" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="d-none d-md-inline me-2 text-dark small">
                            <strong><?php echo htmlspecialchars($nome_usuario_logado); ?></strong>
                            <br><small class="text-muted"><?php echo htmlspecialchars($perfil_nome); ?></small>
                        </span>
                        <div class="avatar-circle">
                            <?php echo strtoupper(substr($nome_usuario_logado, 0, 1)); ?>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li><a class="dropdown-item" href="editar_usuario.php?id=<?php echo $id_usuario_logado; ?>"><i class="bi bi-person me-2"></i> Meu Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sair</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="admin-content p-4">