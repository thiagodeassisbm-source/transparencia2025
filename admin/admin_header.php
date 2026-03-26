<?php
// /admin/admin_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Pega as informações do usuário logado da sessão
$perfil_usuario = $_SESSION['admin_user_perfil'] ?? 'editor';
$nome_usuario_logado = $_SESSION['admin_user_nome'] ?? 'Usuário';
$id_usuario_logado = $_SESSION['admin_user_id'] ?? 0;

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
    
    <!-- CSS Globais -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Estilo do Painel -->
    <link rel="stylesheet" href="admin_style.css?v=<?php echo time(); ?>">
    
    <style>
        /* Ajustes finos para compatibilidade com o novo layout */
        .admin-content { min-height: calc(100vh - 140px); }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar shadow">
        <div class="sidebar-header">
            <h2>Transparência</h2>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-group-title">Principal</div>
            <ul>
                <li>
                    <a href="dashboard.php" class="nav-link <?php echo isActive('dashboard.php'); ?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
            </ul>

            <div class="nav-group-title">Módulos</div>
            <ul>
                <!-- LANÇAMENTOS -->
                <li>
                    <?php $lancamentos_active = isActive(['index.php', 'ver_lancamentos.php', 'lancar_dados.php']); ?>
                    <a href="#collapseLancamentos" class="nav-link has-submenu <?php echo $lancamentos_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button" aria-expanded="<?php echo $lancamentos_active ? 'true' : 'false'; ?>">
                        <span><i class="bi bi-journal-text"></i> Lançamentos</span>
                        <i class="bi bi-chevron-down submenu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $lancamentos_active ? 'show' : ''; ?>" id="collapseLancamentos">
                        <ul class="submenu">
                            <li><a href="index.php" class="nav-link <?php echo isActive(['index.php', 'ver_lancamentos.php', 'lancar_dados.php']); ?>">Gerenciar Seções</a></li>
                        </ul>
                    </div>
                </li>

                <!-- CADASTROS -->
                <?php if ($perfil_usuario == 'admin'): ?>
                <li>
                    <?php $cadastros_active = isActive(['criar_secoes.php', 'gerenciar_categorias.php', 'gerenciar_tipos_documento.php', 'gerenciar_cards.php', 'gerenciar_paginas.php']); ?>
                    <a href="#collapseCadastros" class="nav-link has-submenu <?php echo $cadastros_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button" aria-expanded="<?php echo $cadastros_active ? 'true' : 'false'; ?>">
                        <span><i class="bi bi-folder-plus"></i> Cadastros</span>
                        <i class="bi bi-chevron-down submenu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $cadastros_active ? 'show' : ''; ?>" id="collapseCadastros">
                        <ul class="submenu">
                            <li><a href="criar_secoes.php" class="nav-link <?php echo isActive('criar_secoes.php'); ?>">Seção / Card</a></li>
                            <li><a href="gerenciar_categorias.php" class="nav-link <?php echo isActive('gerenciar_categorias.php'); ?>">Categorias</a></li>
                            <li><a href="gerenciar_tipos_documento.php" class="nav-link <?php echo isActive('gerenciar_tipos_documento.php'); ?>">Tipos de Doc.</a></li>
                            <li><a href="gerenciar_cards.php" class="nav-link <?php echo isActive('gerenciar_cards.php'); ?>">Gerenciar Cards</a></li>
                            <li><a href="gerenciar_paginas.php" class="nav-link <?php echo isActive('gerenciar_paginas.php'); ?>">Páginas de Conteúdo</a></li>
                        </ul>
                    </div>
                </li>

                <!-- PREFEITURA -->
                <li>
                    <?php $prefeitura_active = isActive(['informacoes_prefeitura.php', 'gerenciar_usuarios.php', 'editar_usuario.php']); ?>
                    <a href="#collapsePrefeitura" class="nav-link has-submenu <?php echo $prefeitura_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button" aria-expanded="<?php echo $prefeitura_active ? 'true' : 'false'; ?>">
                        <span><i class="bi bi-building"></i> Prefeitura</span>
                        <i class="bi bi-chevron-down submenu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $prefeitura_active ? 'show' : ''; ?>" id="collapsePrefeitura">
                        <ul class="submenu">
                            <li><a href="informacoes_prefeitura.php" class="nav-link <?php echo isActive('informacoes_prefeitura.php'); ?>">Informações</a></li>
                            <li><a href="gerenciar_usuarios.php" class="nav-link <?php echo isActive(['gerenciar_usuarios.php', 'editar_usuario.php']); ?>">Usuários</a></li>
                        </ul>
                    </div>
                </li>

                <!-- E-SIC -->
                <li>
                    <?php $esic_active = isActive(['sic_inbox.php', 'configuracoes_sic.php', 'responder_esic.php']); ?>
                    <a href="#collapseESic" class="nav-link has-submenu <?php echo $esic_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button" aria-expanded="<?php echo $esic_active ? 'true' : 'false'; ?>">
                        <span><i class="bi bi-mailbox"></i> E-Sic</span>
                        <i class="bi bi-chevron-down submenu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $esic_active ? 'show' : ''; ?>" id="collapseESic">
                        <ul class="submenu">
                            <li><a href="sic_inbox.php" class="nav-link <?php echo isActive(['sic_inbox.php', 'responder_esic.php']); ?>">Caixa de Entrada</a></li>
                            <li><a href="configuracoes_sic.php" class="nav-link <?php echo isActive('configuracoes_sic.php'); ?>">Configurações</a></li>
                        </ul>
                    </div>
                </li>

                <!-- OUVIDORIA (Mantido por ser essencial) -->
                <li>
                    <?php $ouvidoria_active = isActive(['ouvidoria_inbox.php', 'responder_manifestacao.php', 'config_ouvidoria.php']); ?>
                    <a href="#collapseOuvidoria" class="nav-link has-submenu <?php echo $ouvidoria_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button" aria-expanded="<?php echo $ouvidoria_active ? 'true' : 'false'; ?>">
                        <span><i class="bi bi-chat-dots"></i> Ouvidoria</span>
                        <i class="bi bi-chevron-down submenu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $ouvidoria_active ? 'show' : ''; ?>" id="collapseOuvidoria">
                        <ul class="submenu">
                            <li><a href="ouvidoria_inbox.php" class="nav-link <?php echo isActive(['ouvidoria_inbox.php', 'responder_manifestacao.php']); ?>">Inbox</a></li>
                            <li><a href="config_ouvidoria.php" class="nav-link <?php echo isActive('config_ouvidoria.php'); ?>">Configurações</a></li>
                        </ul>
                    </div>
                </li>

                <!-- ENTRADA DE DADOS -->
                <li>
                    <?php $entrada_active = isActive(['importar_xml.php', 'mapear_xml.php', 'gerenciar_tipos_xml.php']); ?>
                    <a href="#collapseEntrada" class="nav-link has-submenu <?php echo $entrada_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button" aria-expanded="<?php echo $entrada_active ? 'true' : 'false'; ?>">
                        <span><i class="bi bi-filetype-xml"></i> Entrada de Dados</span>
                        <i class="bi bi-chevron-down submenu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $entrada_active ? 'show' : ''; ?>" id="collapseEntrada">
                        <ul class="submenu">
                            <li><a href="mapear_xml.php" class="nav-link <?php echo isActive(['mapear_xml.php', 'gerenciar_tipos_xml.php']); ?>">Gerenciar XML</a></li>
                            <li><a href="importar_xml.php" class="nav-link <?php echo isActive('importar_xml.php'); ?>">Importar XML</a></li>
                        </ul>
                    </div>
                </li>

                <!-- RELATÓRIOS -->
                <li>
                    <?php $relatorios_active = isActive(['relatorio_publicacoes.php']); ?>
                    <a href="#collapseRelatorios" class="nav-link has-submenu <?php echo $relatorios_active ? '' : 'collapsed'; ?>" data-bs-toggle="collapse" role="button" aria-expanded="<?php echo $relatorios_active ? 'true' : 'false'; ?>">
                        <span><i class="bi bi-bar-chart"></i> Relatórios</span>
                        <i class="bi bi-chevron-down submenu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $relatorios_active ? 'show' : ''; ?>" id="collapseRelatorios">
                        <ul class="submenu">
                            <li><a href="relatorio_publicacoes.php" class="nav-link <?php echo isActive('relatorio_publicacoes.php'); ?>">Lista de Relatórios</a></li>
                        </ul>
                    </div>
                </li>
                <?php else: ?>
                    <!-- Menu Simplificado para Editores -->
                    <li><a href="index.php" class="nav-link <?php echo isActive(['index.php', 'ver_lancamentos.php', 'lancar_dados.php']); ?>"><i class="bi bi-journal-text"></i> Lançamentos</a></li>
                    <li><a href="ouvidoria_inbox.php" class="nav-link <?php echo isActive('ouvidoria_inbox.php'); ?>"><i class="bi bi-chat-dots"></i> Ouvidoria (Inbox)</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <h1><?php echo htmlspecialchars($page_title_for_header); ?></h1>
            </div>
            <div class="topbar-right">
                <div class="dropdown">
                    <a href="#" class="user-profile dropdown-toggle" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false" data-bs-offset="0,10">
                        <span class="d-none d-md-inline me-2 text-dark">
                            <?php 
                            $exibir_nome = $_SESSION['admin_user_nome_real'] ?? $_SESSION['admin_user_nome'];
                            echo "Bem-vindo, " . htmlspecialchars($exibir_nome) . "!"; 
                            ?>
                        </span>
                        <div class="avatar-circle">
                            <?php 
                            $iniciais = '';
                            if (!empty($_SESSION['admin_user_nome_real'])) {
                                $partes = explode(' ', $_SESSION['admin_user_nome_real']);
                                $iniciais = strtoupper(substr($partes[0], 0, 1));
                                if (count($partes) > 1) $iniciais .= strtoupper(substr($partes[count($partes)-1], 0, 1));
                            } else {
                                $iniciais = strtoupper(substr($_SESSION['admin_user_nome'], 0, 2));
                            }
                            echo $iniciais;
                            ?>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="userMenuDropdown">
                        <li>
                            <a class="dropdown-item py-2" href="editar_usuario.php?id=<?php echo $id_usuario_logado; ?>">
                                <i class="bi bi-person-gear me-2"></i> Meu Perfil
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item py-2 text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> Sair do Sistema
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="admin-content p-4">
