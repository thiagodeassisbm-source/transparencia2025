<?php
require_once 'auth_check.php';
require_once '../conexao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data);

    if (isset($data->ordem) && is_array($data->ordem)) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE campos_portal SET ordem = ? WHERE id = ?");
            foreach ($data->ordem as $posicao => $id_campo) {
                $stmt->execute([$posicao + 1, $id_campo]);
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Dados de ordenação inválidos.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método não permitido.']);
}