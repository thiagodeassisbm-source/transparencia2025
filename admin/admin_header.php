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
    $currentFile = basename($_SERVER['PHP_SELF']);
    return $currentFile == $pageName ? 'active' : '';
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin_style.css?v=<?php echo time(); ?>">

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
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
                <li>
                    <a href="index.php" class="nav-link <?php echo isActive('index.php'); ?> <?php echo isActive('ver_lancamentos.php'); ?> <?php echo isActive('lancar_dados.php'); ?>">
                        <i class="bi bi-journal-text"></i> Lançamentos
                    </a>
                </li>
            </ul>

            <?php if ($perfil_usuario == 'admin'): ?>
            <div class="nav-group-title">Cadastros e Config.</div>
            <ul>
                <li>
                    <a href="criar_secoes.php" class="nav-link <?php echo isActive('criar_secoes.php'); ?>">
                        <i class="bi bi-plus-circle"></i> Nova Seção / Card
                    </a>
                </li>
                <li>
                    <a href="gerenciar_categorias.php" class="nav-link <?php echo isActive('gerenciar_categorias.php'); ?>">
                        <i class="bi bi-tags"></i> Categorias
                    </a>
                </li>
                <li>
                    <a href="gerenciar_tipos_documento.php" class="nav-link <?php echo isActive('gerenciar_tipos_documento.php'); ?>">
                        <i class="bi bi-file-earmark-code"></i> Tipos Doc.
                    </a>
                </li>
                <li>
                    <a href="gerenciar_cards.php" class="nav-link <?php echo isActive('gerenciar_cards.php'); ?>">
                        <i class="bi bi-grid-3x3-gap"></i> Gerenciar Cards
                    </a>
                </li>
                <li>
                    <a href="gerenciar_paginas.php" class="nav-link <?php echo isActive('gerenciar_paginas.php'); ?>">
                        <i class="bi bi-file-earmark-text"></i> Páginas
                    </a>
                </li>
                <li>
                    <a href="informacoes_prefeitura.php" class="nav-link <?php echo isActive('informacoes_prefeitura.php'); ?>">
                        <i class="bi bi-building"></i> Orgão / Prefeitura
                    </a>
                </li>
                <li>
                    <a href="gerenciar_usuarios.php" class="nav-link <?php echo isActive('gerenciar_usuarios.php'); ?>">
                        <i class="bi bi-people"></i> Usuários
                    </a>
                </li>
            </ul>

            <div class="nav-group-title">Canais</div>
            <ul>
                <li>
                    <a href="sic_inbox.php" class="nav-link <?php echo isActive('sic_inbox.php'); ?>">
                        <i class="bi bi-mailbox"></i> e-Sic (Inbox)
                    </a>
                </li>
                <li>
                    <a href="ouvidoria_inbox.php" class="nav-link <?php echo isActive('ouvidoria_inbox.php'); ?>">
                        <i class="bi bi-chat-dots"></i> Ouvidoria (Inbox)
                    </a>
                </li>
            </ul>

            <div class="nav-group-title">Ferramentas</div>
            <ul>
                <li>
                    <a href="importar_xml.php" class="nav-link <?php echo isActive('importar_xml.php'); ?>">
                        <i class="bi bi-filetype-xml"></i> Importar XML
                    </a>
                </li>
                <li>
                    <a href="relatorio_publicacoes.php" class="nav-link <?php echo isActive('relatorio_publicacoes.php'); ?>">
                        <i class="bi bi-bar-chart"></i> Relatórios
                    </a>
                </li>
            </ul>
            <?php else: ?>
                <div class="nav-group-title">Canais</div>
                <ul>
                    <li>
                        <a href="ouvidoria_inbox.php" class="nav-link <?php echo isActive('ouvidoria_inbox.php'); ?>">
                            <i class="bi bi-chat-dots"></i> Ouvidoria (Inbox)
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
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
                    <a href="#" class="user-profile dropdown-toggle" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="d-none d-md-inline me-2">
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


        <main class="admin-content">