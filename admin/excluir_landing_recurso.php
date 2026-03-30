<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Bloqueia se não for superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    // Busca o título para o log antes de excluir
    $stmt_title = $pdo->prepare("SELECT titulo FROM landing_recursos WHERE id = ?");
    $stmt_title->execute([$id]);
    $titulo = $stmt_title->fetchColumn();

    $stmt_del = $pdo->prepare("DELETE FROM landing_recursos WHERE id = ?");
    $stmt_del->execute([$id]);

    registrar_log($pdo, 'SUPERADMIN', 'EXCLUIR_LANDING_RECURSO', "Recurso $titulo excluído da landing page.");
}

header("Location: gerenciar_landing_recursos.php");
exit;
