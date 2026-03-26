<?php
// /admin/auth_check.php (Versão Corrigida)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verifica login básico
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['admin_user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Garante que $pdo esteja disponível
require_once __DIR__ . '/../conexao.php';

// 3. Carrega permissões e informações do perfil para a sessão (se não carregadas)
if (!isset($_SESSION['permissoes_sessao']) || !isset($_SESSION['admin_user_perfil_nome'])) {
    $id_perfil = $_SESSION['admin_user_id_perfil'] ?? 0;
    
    // Busca o nome do perfil
    $stmt_p = $pdo->prepare("SELECT nome FROM perfis WHERE id = ?");
    $stmt_p->execute([$id_perfil]);
    $perfil_info = $stmt_p->fetch();
    
    // Fallback se não encontrar perfil (usuário não migrado)
    if (!$perfil_info) {
        $_SESSION['admin_user_perfil_nome'] = ($_SESSION['admin_user_perfil'] == 'admin' ? 'Administrador' : 'Editor');
    } else {
        $_SESSION['admin_user_perfil_nome'] = $perfil_info['nome'];
    }

    // Busca as permissões
    $permissoes = [];
    $stmt = $pdo->prepare("SELECT recurso, p_ver, p_lancar, p_editar, p_excluir FROM permissoes_perfil WHERE id_perfil = ?");
    $stmt->execute([$id_perfil]);
    while ($row = $stmt->fetch()) {
        $permissoes[$row['recurso']] = [
            'ver' => (bool)$row['p_ver'],
            'lancar' => (bool)$row['p_lancar'],
            'editar' => (bool)$row['p_editar'],
            'excluir' => (bool)$row['p_excluir']
        ];
    }
    $_SESSION['permissoes_sessao'] = $permissoes;
}

// 4. Função Global para Verificar Permissões
function tem_permissao($recurso, $acao = 'ver') {
    // Admin master (antigo) tem acesso total
    if (isset($_SESSION['admin_user_perfil']) && $_SESSION['admin_user_perfil'] === 'admin') {
        return true;
    }

    $perms = $_SESSION['permissoes_sessao'] ?? [];
    
    if (isset($perms[$recurso][$acao])) {
        return $perms[$recurso][$acao];
    }

    return false; // Negado por padrão
}
?>