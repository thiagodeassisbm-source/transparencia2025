<?php
// /admin/auth_check.php (Versão à Prova de Erros)

// Apenas inicia uma sessão se nenhuma já estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se a variável de sessão 'admin_logged_in' não existe ou não é verdadeira
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Se o perfil não estiver na sessão, busca no banco e armazena
if (!isset($_SESSION['admin_user_perfil'])) {
    // Inclui a conexão apenas se for necessário
    require_once '../conexao.php';
    $stmt = $pdo->prepare("SELECT perfil FROM usuarios_admin WHERE id = ?");
    $stmt->execute([$_SESSION['admin_user_id']]);
    $_SESSION['admin_user_perfil'] = $stmt->fetchColumn();
}