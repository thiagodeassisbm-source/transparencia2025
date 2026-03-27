<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Verifica se é superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    die("Acesso negado.");
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    // Busca informações da prefeitura para garantir que existe
    $stmt = $pdo->prepare("SELECT id, nome FROM prefeituras WHERE id = ?");
    $stmt->execute([$id]);
    $pref = $stmt->fetch();

    if ($pref) {
        // Redireciona o contexto da sessão para esta prefeitura
        $_SESSION['id_prefeitura'] = $pref['id'];
        
        // Redireciona para o dashboard comum (agora filtrado por esta prefeitura)
        header("Location: dashboard.php");
    } else {
        header("Location: super_dashboard.php?error=Prefeitura não encontrada");
    }
} else {
    header("Location: super_dashboard.php?error=ID inválido");
}
exit;
