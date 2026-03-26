<?php
// /admin/admin_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Pega as informações do usuário logado da sessão
$perfil_usuario = $_SESSION['admin_user_perfil'] ?? 'editor';
$nome_usuario_logado = $_SESSION['admin_user_nome'] ?? 'Usuário';
$id_usuario_logado = $_SESSION['admin_user_id'] ?? 0;

// Define um título padrão para a página, que pode ser sobrescrito pela página que o inclui
$page_title_for_header = $page_title_for_header ?? 'Painel Administrativo';
?>
<header class="page-header">
    <div class="container-fluid container-custom-padding">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h1><?php echo htmlspecialchars($page_title_for_header); ?></h1>
            <div class="text-end">
                <span class="text-white me-3"><i class="bi bi-person-circle"></i> Bem-vindo, <?php echo htmlspecialchars($nome_usuario_logado); ?>!</span>
                <a href="logout.php" class="btn btn-danger"><i class="bi bi-box-arrow-right"></i> Sair</a>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-12 pt-4">
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark rounded mb-4">
                <div class="container-fluid">
                    <span class="navbar-brand mb-0 h1">Navegação</span>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavContent">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="adminNavContent">
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard.php">Início</a>
                            </li>
                            
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownLancamentos" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Lançamentos
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdownLancamentos">
                                    <li><a class="dropdown-item" href="index.php">Gerenciar Seções</a></li>
                                    </ul>
                            </li>
                            
                            <?php if ($perfil_usuario == 'admin'): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownCadastros" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Cadastros Gerais
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownCadastros">
                                        <li><a class="dropdown-item" href="criar_secoes.php">Nova Seção/Card</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="gerenciar_categorias.php">Categorias</a></li>
                                        <li><a class="dropdown-item" href="gerenciar_tipos_documento.php">Tipos de Documento</a></li>
                                        <li><a class="dropdown-item" href="gerenciar_cards.php">Gerenciar Cards</a></li>
                                        <li><a class="dropdown-item" href="gerenciar_paginas.php">Páginas de Conteúdo</a></li>
                                    </ul>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownPrefeitura" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Prefeitura
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownPrefeitura">
                                        <li><a class="dropdown-item" href="informacoes_prefeitura.php">Informações</a></li>
                                        <li><a class="dropdown-item" href="gerenciar_usuarios.php">Usuários</a></li>
                                    </ul>
                                </li>
                            <?php endif; ?>
                            
                            <?php if ($perfil_usuario == 'admin'): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownEsic" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    e-Sic
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdownEsic">
                                    <li><a class="dropdown-item" href="sic_inbox.php">Caixa de Entrada</a></li>
                                    <li><a class="dropdown-item" href="configuracoes_sic.php">Configurações</a></li>
                                </ul>
                            </li>
                            <?php endif; ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownOuvidoria" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Ouvidoria
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdownOuvidoria">
                                    <li><a class="dropdown-item" href="ouvidoria_inbox.php">Caixa de Entrada</a></li>
                                    <?php if ($perfil_usuario == 'admin'): ?>
                                    <li><a class="dropdown-item" href="config_ouvidoria.php">Configurações</a></li>
                                    <?php endif; ?>
                                </ul>
                            </li>

                            <?php if ($perfil_usuario == 'admin'): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownDados" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Entrada de Dados
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownDados">
                                        <li><a class="dropdown-item" href="gerenciar_tipos_xml.php">Gerenciar XML</a></li>
                                        <li><a class="dropdown-item" href="importar_xml.php">Importar XML</a></li>
                                    </ul>
                                </li>
                                <li class="nav-item"><a class="nav-link" href="relatorio_publicacoes.php">Relatórios</a></li>
                            <?php endif; ?>
                        </ul>
                        
                        <ul class="navbar-nav ms-auto">
                           <li class="nav-item">
                                <a class="nav-link" href="editar_usuario.php?id=<?php echo $id_usuario_logado; ?>">Editar Meu Perfil</a>
                           </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</div>