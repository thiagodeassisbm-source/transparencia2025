<?php
// /admin/excluir_secao.php
session_start();
require_once '../conexao.php';
require_once 'functions_logs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['portal_id'])) {
    $portal_id = filter_input(INPUT_POST, 'portal_id', FILTER_VALIDATE_INT);

    if ($portal_id) {
        try {
            $pdo->beginTransaction();

            // 1. Encontrar e apagar os arquivos físicos associados a esta seção
            $stmt_files = $pdo->prepare(
                "SELECT vr.valor FROM valores_registros vr 
                 JOIN registros r ON vr.id_registro = r.id 
                 JOIN campos_portal cp ON vr.id_campo = cp.id 
                 WHERE r.id_portal = ? AND cp.tipo_campo = 'anexo'"
            );
            $stmt_files->execute([$portal_id]);
            $arquivos_para_apagar = $stmt_files->fetchAll(PDO::FETCH_COLUMN);

            foreach ($arquivos_para_apagar as $caminho_arquivo) {
                // Verifica se o arquivo existe e o apaga
                if (file_exists($caminho_arquivo)) {
                    unlink($caminho_arquivo);
                }
            }

            // 2. Apagar a seção do banco de dados (o ON DELETE CASCADE cuidará do resto)
            $stmt_delete = $pdo->prepare("DELETE FROM portais WHERE id = ?");
            $stmt_delete->execute([$portal_id]);

            $pdo->commit();
            
            registrar_log($pdo, 'EXCLUSÃO', 'portais', "Excluiu a seção/portal ID: $portal_id");

            $_SESSION['mensagem_sucesso'] = "Seção e todos os seus dados foram excluídos com sucesso!";

        } catch (Exception $e) {
            $pdo->rollBack();
            // Em um sistema real, seria bom logar o erro: error_log($e->getMessage());
            $_SESSION['mensagem_sucesso'] = "Erro ao tentar excluir a seção. Por favor, tente novamente.";
        }
    }
}

// Redireciona de volta para o painel administrativo
header("Location: index.php");
exit();