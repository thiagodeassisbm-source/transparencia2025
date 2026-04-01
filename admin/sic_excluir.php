<?php
// /admin/sic_excluir.php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

$id = $_GET['id'] ?? 0;
$pref_id = $_SESSION['id_prefeitura'];

if ($id > 0) {
    try {
        $st_info = $pdo->prepare('SELECT protocolo FROM sic_solicitacoes WHERE id = ? AND id_prefeitura = ?');
        $st_info->execute([$id, $pref_id]);
        $protocolo = $st_info->fetchColumn();
        // Garante que só exclui da sua própria prefeitura (SaaS Isolation)
        $stmt = $pdo->prepare('DELETE FROM sic_solicitacoes WHERE id = ? AND id_prefeitura = ?');
        $stmt->execute([$id, $pref_id]);
        if ($stmt->rowCount() > 0) {
            registrar_log(
                $pdo,
                'EXCLUSÃO',
                'sic_solicitacoes',
                'Excluiu solicitação e-SIC — protocolo ' . ($protocolo ?: '#' . $id) . " (ID: $id)."
            );
        }

        $_SESSION['mensagem_sucesso'] = "Solicitação excluída com sucesso!";
    } catch (Exception $e) {
        $_SESSION['mensagem_erro'] = "Erro ao excluir: " . $e->getMessage();
    }
}

header("Location: sic_inbox.php");
exit;
