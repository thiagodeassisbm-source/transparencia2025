<?php
require_once '../conexao.php';
header('Content-Type: application/json');

$dados = json_decode(file_get_contents('php://input'), true);

if ($dados && isset($dados['ordem'])) {
    $novaOrdem = $dados['ordem'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE categorias SET ordem = ? WHERE id = ?");
        foreach ($novaOrdem as $posicao => $id) {
            $stmt->execute([$posicao, $id]);
        }
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
}