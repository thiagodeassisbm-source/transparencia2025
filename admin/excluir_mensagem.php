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
    $stmt_del = $pdo->prepare("DELETE FROM mensagens_sistema WHERE id = ?");
    $stmt_del->execute([$id]);
    registrar_log($pdo, 'SUPERADMIN', 'EXCLUIR_MENSAGEM', "Mensagem de aviso excluída.");
}

header("Location: gerenciar_mensagens.php");
exit;
