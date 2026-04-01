<?php
// /admin/excluir_secao.php
session_start();
require_once '../conexao.php';
require_once 'functions_logs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $portal_id = filter_input(INPUT_POST, 'portal_id', FILTER_VALIDATE_INT);
    $card_id = filter_input(INPUT_POST, 'card_id', FILTER_VALIDATE_INT);

    if ($portal_id || $card_id) {
        try {
            $pdo->beginTransaction();

            if ($portal_id) {
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
                    if (!empty($caminho_arquivo) && file_exists($caminho_arquivo)) {
                        unlink($caminho_arquivo);
                    }
                }

                // 2. Apagar a seção do banco de dados (o ON DELETE CASCADE deve cuidar da maioria, mas vamos garantir o card)
                $stmt_delete = $pdo->prepare("DELETE FROM portais WHERE id = ?");
                $stmt_delete->execute([$portal_id]);
                
                // Remove o card explicitamente se o cascade não pegar (alguns bancos não configuram cascade para cards)
                $pdo->prepare("DELETE FROM cards_informativos WHERE id_secao = ?")->execute([$portal_id]);
            } else if ($card_id) {
                // É apenas um card de link direto
                $pdo->prepare("DELETE FROM cards_informativos WHERE id = ?")->execute([$card_id]);
            }

            $pdo->commit();
            registrar_log($pdo, 'EXCLUSÃO', 'portais/cards', "Excluiu portal: $portal_id / card: $card_id");
            $_SESSION['mensagem_sucesso'] = "Excluído com sucesso!";

        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['mensagem_sucesso'] = "Erro ao tentar excluir: " . $e->getMessage();
        }
    }
}

header("Location: criar_secoes.php");
exit();