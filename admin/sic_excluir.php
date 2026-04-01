<?php
// /admin/sic_excluir.php
require_once 'auth_check.php';
require_once '../conexao.php';

$id = $_GET['id'] ?? 0;
$pref_id = $_SESSION['id_prefeitura'];

if ($id > 0) {
    try {
        // Garante que só exclui da sua própria prefeitura (SaaS Isolation)
        $stmt = $pdo->prepare("DELETE FROM sic_solicitacoes WHERE id = ? AND id_prefeitura = ?");
        $stmt->execute([$id, $pref_id]);
        
        $_SESSION['mensagem_sucesso'] = "Solicitação excluída com sucesso!";
    } catch (Exception $e) {
        $_SESSION['mensagem_erro'] = "Erro ao excluir: " . $e->getMessage();
    }
}

header("Location: sic_inbox.php");
exit;
