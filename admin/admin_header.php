<?php
// /admin/admin_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Re-carrega permissões se necessário (opcional, para refletir mudanças imediatas)
require_once 'auth_check.php';

$nome_usuario_logado = $_SESSION['admin_user_nome'] ?? 'Usuário';
$id_usuario_logado = $_SESSION['admin_user_id'] ?? 0;
$perfil_nome = $_SESSION['admin_user_perfil_nome'] ?? 'Perfil';

// Define um título padrão para a página
$page_title_for_header = $page_title_for_header ?? 'Painel Administrativo';

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
</head>
<body>

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar shadow">
        <div class="sidebar-header">
            <h2>Painel de controle</h2>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <?php if (tem_permissao('dashboard', 'ver')): ?>
                <li>
                    <a href="dashboard.php" class="nav-link <?php echo isActive('dashboard.php'); ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <ul>
                <!-- LANÇAMENTOS (Módulo Principal) -->
                <?php if (tem_permissao('secoes', 'ver')): ?>
                <li>
                    <?php $lancamentos_active = isActive(['index.php', 'ver_lancamentos.php', 'lancar_dados.php']); ?>
                    <a href="#collapseLancamentos" class="nav-link has-submenu <?php echo $lancamentos_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                        <span><i class="bi bi-journal-text"></i> Lançamentos</span>
                        <i class="bi bi-chevron-down submenu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $lancamentos_active ? 'show' : ''; ?>" id="collapseLancamentos">
                        <ul class="submenu">
                            <li><a href="index.php" class="nav-link <?php echo isActive(['index.php', 'ver_lancamentos.php', 'lancar_dados.php']); ?>">Gerenciar Seções</a></li>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                <!-- CADASTROS E ESTRUTURA (Somente Admin ou Custom) -->
                <?php if (tem_permissao('secoes', 'editar')): ?>
                <li>
                    <?php $cadastros_active = isActive(['criar_secoes.php', 'gerenciar_categorias.php', 'gerenciar_tipos_documento.php', 'gerenciar_cards.php']); ?>
                    <a href="#collapseCadastros" class="nav-link has-submenu <?php echo $cadastros_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                        <span><i class="bi bi-folder-plus"></i> Estrutura</span>
                        <i class="bi bi-chevron-down submenu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $cadastros_active ? 'show' : ''; ?>" id="collapseCadastros">
                        <ul class="submenu">
                            <li><a href="criar_secoes.php" class="nav-link <?php echo isActive('criar_secoes.php'); ?>">Criar Seção / Card</a></li>
                            <li><a href="gerenciar_categorias.php" class="nav-link <?php echo isActive('gerenciar_categorias.php'); ?>">Categorias</a></li>
                            <li><a href="gerenciar_tipos_documento.php" class="nav-link <?php echo isActive('gerenciar_tipos_documento.php'); ?>">Tipos de Doc.</a></li>
                            <li><a href="gerenciar_cards.php" class="nav-link <?php echo isActive('gerenciar_cards.php'); ?>">Gerenciar Cards</a></li>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                <!-- CONFIGURAÇÕES PREFEITURA -->
                <?php if (tem_permissao('configuracoes', 'ver')): ?>
                <li>
                    <?php $prefeitura_active = isActive(['informacoes_prefeitura.php']); ?>
                    <a href="#collapsePrefeitura" class="nav-link has-submenu <?php echo $prefeitura_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                        <span><i class="bi bi-building"></i> Prefeitura</span>
                        <i class="bi bi-chevron-down submenu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $prefeitura_active ? 'show' : ''; ?>" id="collapsePrefeitura">
                        <ul class="submenu">
                            <li><a href="informacoes_prefeitura.php" class="nav-link <?php echo isActive('informacoes_prefeitura.php'); ?>">Configurações da Prefeitura</a></li>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                <!-- E-SIC -->
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

                <!-- OUVIDORIA -->
                <?php if (tem_permissao('ouvidoria', 'ver')): ?>
                <li>
                    <?php $ouvidoria_active = isActive(['ouvidoria_inbox.php', 'responder_manifestacao.php', 'config_ouvidoria.php']); ?>
                    <a href="#collapseOuvidoria" class="nav-link has-submenu <?php echo $ouvidoria_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                        <span><i class="bi bi-chat-dots"></i> Ouvidoria</span>
                        <i class="bi bi-chevron-down submenu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $ouvidoria_active ? 'show' : ''; ?>" id="collapseOuvidoria">
                        <ul class="submenu">
                            <li><a href="ouvidoria_inbox.php" class="nav-link <?php echo isActive(['ouvidoria_inbox.php', 'responder_manifestacao.php']); ?>">Inbox</a></li>
                            <?php if (tem_permissao('ouvidoria', 'editar')): ?>
                                <li><a href="config_ouvidoria.php" class="nav-link <?php echo isActive('config_ouvidoria.php'); ?>">Configurações</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>

                <!-- CONFIGURAÇÕES (Geral) -->
                <?php if (tem_permissao('usuarios', 'ver') || $_SESSION['admin_user_perfil'] === 'admin'): ?>
                <li>
                    <?php $entrada_active = isActive(['importar_xml.php', 'mapear_xml.php', 'gerenciar_tipos_xml.php', 'gerenciar_usuarios.php', 'gerenciar_perfis.php', 'editar_usuario.php', 'editar_permissoes_perfil.php']); ?>
                    <a href="#collapseConfig" class="nav-link has-submenu <?php echo $entrada_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button">
                        <span><i class="bi bi-gear-fill"></i> CONFIGURAÇÕES</span>
                        <i class="bi bi-chevron-down submenu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $entrada_active ? 'show' : ''; ?>" id="collapseConfig">
                        <ul class="submenu">
                            <?php if (tem_permissao('usuarios', 'ver')): ?>
                                <li><a href="gerenciar_usuarios.php" class="nav-link <?php echo isActive(['gerenciar_usuarios.php', 'editar_usuario.php']); ?>">Usuários</a></li>
                            <?php endif; ?>
                            <?php if ($_SESSION['admin_user_perfil'] === 'admin'): ?>
                                <li><a href="gerenciar_perfis.php" class="nav-link <?php echo isActive(['gerenciar_perfis.php', 'editar_permissoes_perfil.php']); ?>">Perfis e Acessos</a></li>
                                <li class="border-top my-1 opacity-25"></li>
                                <li><a href="mapear_xml.php" class="nav-link <?php echo isActive(['mapear_xml.php', 'gerenciar_tipos_xml.php']); ?>">Gerenciar XML</a></li>
                                <li><a href="importar_xml.php" class="nav-link <?php echo isActive('importar_xml.php'); ?>">Importar XML</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <h1 class="h5 mb-0 fw-600"><?php echo htmlspecialchars($page_title_for_header); ?></h1>
            </div>
            <div class="topbar-right">
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