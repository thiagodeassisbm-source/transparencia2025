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
    if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] === 1) {
        $_SESSION['admin_user_perfil_nome'] = 'Super Administrador';
    } else {
        $stmt_p = $pdo->prepare("SELECT nome FROM perfis WHERE id = ?");
        $stmt_p->execute([$id_perfil]);
        $perfil_info = $stmt_p->fetch();
        
        // Fallback se não encontrar perfil (usuário não migrado)
        if (!$perfil_info) {
            $_SESSION['admin_user_perfil_nome'] = ($_SESSION['admin_user_perfil'] == 'admin' ? 'Administrador' : 'Editor');
        } else {
            $_SESSION['admin_user_perfil_nome'] = $perfil_info['nome'];
        }
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

// 4. Função Global para Verificar Permissões (Reativa com banco de dados)
function tem_permissao($recurso, $acao = 'ver') {
    global $pdo;

    // Apenas Super Administrador (Master) ignora as regras granulares
    if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] === 1) {
        return true;
    }

    // Cache estático para evitar múltiplas consultas na mesma página
    static $permissoes_cache = null;

    if ($permissoes_cache === null) {
        $id_perfil = $_SESSION['admin_user_id_perfil'] ?? 0;
        try {
            $stmt = $pdo->prepare("SELECT recurso, p_ver, p_lancar, p_editar, p_excluir FROM permissoes_perfil WHERE id_perfil = ?");
            $stmt->execute([$id_perfil]);
            $rows = $stmt->fetchAll();
            $permissoes_cache = [];
            foreach ($rows as $r) {
                $permissoes_cache[$r['recurso']] = [
                    'ver' => (bool)$r['p_ver'],
                    'lancar' => (bool)$r['p_lancar'],
                    'editar' => (bool)$r['p_editar'],
                    'excluir' => (bool)$r['p_excluir']
                ];
            }
        } catch (Exception $e) {
            $permissoes_cache = [];
        }
    }

    if (isset($permissoes_cache[$recurso][$acao])) {
        return $permissoes_cache[$recurso][$acao];
    }

    return false; // Negado por padrão
}

/**
 * Busca configurações dinâmicas da tabela config_global
 * Duplicado aqui por redundância para garantir que o admin_footer não quebre se o conexao.php demorar a sincronizar
 */
if (!function_exists('get_config_global')) {
    function get_config_global($pdo, $chave, $padrao = '') {
        static $config_cache = null;
        if ($config_cache === null) {
            try {
                $stmt_config = $pdo->query("SELECT chave, valor FROM config_global");
                $config_cache = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
            } catch (Exception $e) { $config_cache = []; }
        }
        return isset($config_cache[$chave]) ? $config_cache[$chave] : $padrao;
    }
}
?>