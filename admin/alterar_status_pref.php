<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Verifica se é superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    die("Acesso negado.");
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS);

if ($id && in_array($status, ['ativo', 'suspenso', 'pendente_pagamento'])) {
    // Busca nome da prefeitura para o log
    $stmt_name = $pdo->prepare("SELECT nome FROM prefeituras WHERE id = ?");
    $stmt_name->execute([$id]);
    $nome_pref = $stmt_name->fetchColumn();

    $stmt = $pdo->prepare("UPDATE prefeituras SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $id])) {
        registrar_log($id, 'PREFEITURA', 'STATUS_ALTERADO', "Status da prefeitura $nome_pref alterado para $status");
        header("Location: super_dashboard.php?msg=Status atualizado com sucesso");
    } else {
        header("Location: super_dashboard.php?error=Não foi possível atualizar o status");
    }
} else {
    header("Location: super_dashboard.php?error=Parâmetros inválidos");
}
exit;
