<?php
// /admin/salvar_ordem.php
require_once '../conexao.php';

// Define o cabeçalho da resposta como JSON
header('Content-Type: application/json');

// Pega os dados enviados pelo JavaScript
$dados = json_decode(file_get_contents('php://input'), true);

// Verifica se os dados e a ordem foram recebidos
if ($dados && isset($dados['ordem'])) {
    $novaOrdem = $dados['ordem'];
    
    try {
        $pdo->beginTransaction();

        // Prepara a query de atualização
        $stmt = $pdo->prepare("UPDATE portais SET ordem = ? WHERE id = ?");

        // Percorre o array com a nova ordem
        foreach ($novaOrdem as $posicao => $id) {
            // A posição no array (0, 1, 2...) será o novo valor da 'ordem'
            $stmt->execute([$posicao, $id]);
        }

        $pdo->commit();
        
        // Retorna uma resposta de sucesso em JSON
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $pdo->rollBack();
        // Retorna uma resposta de erro em JSON
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    // Retorna uma resposta de erro se os dados não foram recebidos corretamente
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
}