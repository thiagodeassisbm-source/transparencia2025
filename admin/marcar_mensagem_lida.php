<?php
// admin/marcar_mensagem_lida.php
require_once 'auth_check.php';
require_once '../conexao.php';

$id_mensagem = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_mensagem) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$id_usuario = $_SESSION['admin_user_id'];
$id_prefeitura = $_SESSION['id_prefeitura'];

try {
    // Insere apenas se já não houver o registro (evita duplicados caso o user recarregue)
    $stmt_check = $pdo->prepare("SELECT id FROM mensagens_vistas WHERE id_mensagem = ? AND id_usuario = ?");
    $stmt_check->execute([$id_mensagem, $id_usuario]);

    if (!$stmt_check->fetch()) {
        $stmt_ins = $pdo->prepare("INSERT INTO mensagens_vistas (id_mensagem, id_usuario, id_prefeitura) VALUES (?, ?, ?)");
        $stmt_ins->execute([$id_mensagem, $id_usuario, $id_prefeitura]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
