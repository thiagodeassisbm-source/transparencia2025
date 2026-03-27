<?php
// /admin/logout.php
session_start();
require_once '../conexao.php';
require_once 'functions_logs.php';

if (isset($_SESSION['admin_user_id'])) {
    registrar_log($pdo, 'LOGOUT', 'usuarios_admin', "Usuário saiu do sistema.");
}

// Captura o redirecionamento correto antes de destruir a sessão (SaaS)
$redirect_url = "login.php";
if (isset($_SESSION['id_prefeitura']) && (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1)) {
    $stmt_slug = $pdo->prepare("SELECT slug FROM prefeituras WHERE id = ?");
    $stmt_slug->execute([$_SESSION['id_prefeitura']]);
    $slug = $stmt_slug->fetchColumn();
    if ($slug) {
        // Redireciona para o login da prefeitura específica
        $redirect_url = "/sistemas/transparencia2026/portal/$slug/admin";
    }
}

session_unset();
session_destroy();

header("Location: $redirect_url");
exit;