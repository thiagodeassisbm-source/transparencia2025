<?php
session_start();
require_once 'conexao.php';

// Apenas aceita requisições do tipo POST com os dados esperados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['nome_solicitante'])) {
    try {
        // Gera protocolo único e mais robusto
        $protocolo = 'SIC' . date('Y') . rand(10000, 99999);
        $status = 'Recebido';

        // Detecta prefeitura (Contexto SaaS)
        $id_prefeitura_form = $_POST['pref_id'] ?? ($_GET['pref_id'] ?? 0);
        if (!$id_prefeitura_form && isset($_SESSION['id_prefeitura'])) $id_prefeitura_form = $_SESSION['id_prefeitura'];

        $stmt = $pdo->prepare(
            "INSERT INTO sic_solicitacoes 
                (protocolo, id_prefeitura, nome_solicitante, email, telefone, tipo_documento, numero_documento, descricao_pedido, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->execute([
            $protocolo,
            $id_prefeitura_form,
            $_POST['nome_solicitante'],
            $_POST['email'],
            $_POST['telefone'],
            $_POST['tipo_documento'],
            $_POST['numero_documento'],
            $_POST['descricao_pedido'],
            $status
        ]);

        // Redireciona de volta para a página principal do SIC com a mensagem de sucesso
        header("Location: sic.php?protocolo=" . urlencode($protocolo));
        exit;

    } catch (Exception $e) {
        // Em caso de erro, exibe uma mensagem clara
        die("Ocorreu um erro ao registrar sua solicitação. Por favor, tente novamente. Detalhe técnico: " . $e->getMessage());
    }
} else {
    // Se o arquivo for acessado diretamente ou sem dados, redireciona para a página do SIC
    header("Location: sic.php");
    exit;
}