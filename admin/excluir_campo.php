<?php
session_start();
require_once '../conexao.php';
require_once 'functions_logs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['campo_id'])) {
    $campo_id = filter_input(INPUT_POST, 'campo_id', FILTER_VALIDATE_INT);
    $portal_id = filter_input(INPUT_POST, 'portal_id', FILTER_VALIDATE_INT);

    if ($campo_id && $portal_id) {
        // Graças ao ON DELETE CASCADE no banco, apagar o campo aqui
        // irá apagar automaticamente todos os valores associados na tabela 'valores_registros'.
        $stmt = $pdo->prepare("DELETE FROM campos_portal WHERE id = ?");
        $stmt->execute([$campo_id]);
        
        registrar_log($pdo, 'EXCLUSÃO', 'campos_portal', "Excluiu campo ID: $campo_id (Seção ID: $portal_id)");

        $_SESSION['mensagem_sucesso'] = "Campo excluído com sucesso!";
    }
}

// Redireciona de volta para a página de gerenciamento de campos
header("Location: gerenciar_campos.php?portal_id=" . $portal_id);
exit();