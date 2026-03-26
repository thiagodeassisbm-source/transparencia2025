<?php
// /admin/auth_check.php (Versão com Sistema de Permissões)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verifica login básico
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['admin_user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Garante que $pdo esteja disponível se precisarmos buscar algo
require_once __DIR__ . '/../conexao.php';

// 3. Função Global para Verificar Permissões
function tem_permissao($recurso, $acao = 'ver') {
    global $pdo;
    
    // Suporte para o perfil 'admin' antigo ter acesso total total (fallback seguro)
    if (isset($_SESSION['admin_user_perfil']) && $_SESSION['admin_user_perfil'] === 'admin') {
        return true;
    }

    // Se as permissões não estiverem na sessão, carregue-as do banco
    if (!isset($_SESSION['permissoes_sessao'])) {
        $id_perfil = $_SESSION['admin_user_id_perfil'] ?? 0;
        
        // Se ainda não temos o id_perfil na sessão, busque no banco
        if (!$id_perfil) {
            $stmt = $pdo->prepare("SELECT id_perfil FROM usuarios_admin WHERE id = ?");
            $stmt->execute([$_SESSION['admin_user_id']]);
            $id_perfil = $stmt->fetchColumn();
            $_SESSION['admin_user_id_perfil'] = $id_perfil;
        }

        $stmt_perms = $pdo->prepare("SELECT recurso, p_ver, p_lancar, p_editar, p_excluir FROM permissoes_perfil WHERE id_perfil = ?");
        $stmt_perms->execute([$id_perfil]);
        $perms_raw = $stmt_perms->fetchAll(PDO::FETCH_ASSOC);
        
        $_SESSION['permissoes_sessao'] = [];
        foreach ($perms_raw as $p) {
            $_SESSION['permissoes_sessao'][$p['recurso']] = [
                'ver' => (bool)$p['p_ver'],
                'lancar' => (bool)$p['p_lancar'],
                'editar' => (bool)$p['p_editar'],
                'excluir' => (bool)$p['p_excluir']
            ];
        }
    }

    // Verifica a permissão solicitada
    $perms = $_SESSION['permissoes_sessao'];
    if (isset($perms[$recurso][$acao])) {
        return $perms[$recurso][$acao];
    }

    return false; // Por padrão, se não encontrar, nega acesso
}

// 4. Se o usuário tentar acessar uma URL que ele não tem permissão de ver, podemos fazer um bloqueio automático rápido aqui 
// Mas é melhor deixar por página para flexibilidade total de mensagens.
?>