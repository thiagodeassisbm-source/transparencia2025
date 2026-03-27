<?php
session_start();
require_once '../conexao.php';
require_once 'functions_logs.php';

if ($_SESSION['admin_user_perfil'] !== 'admin') { header("Location: index.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_id'])) {
    $card_id = filter_input(INPUT_POST, 'card_id', FILTER_VALIDATE_INT);
    $caminho_icone = $_POST['caminho_icone'];

    if ($card_id) {
        // Registrar log ANTES de apagar
        registrar_log($pdo, 'EXCLUSÃO', 'cards_informativos', "Excluiu o card ID: $card_id");
        // 1. Apagar o arquivo de imagem do servidor
        if (!empty($caminho_icone) && file_exists($caminho_icone)) {
            unlink($caminho_icone);
        }

        // 2. Apagar o registro do banco de dados
        $stmt = $pdo->prepare("DELETE FROM cards_informativos WHERE id = ?");
        $stmt->execute([$card_id]);

        $_SESSION['mensagem_sucesso'] = "Card excluído com sucesso!";
    }
}

header("Location: gerenciar_cards.php");
exit();