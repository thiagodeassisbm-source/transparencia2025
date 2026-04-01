<?php
session_start();
require_once 'conexao.php';

// Apenas aceita requisições do tipo POST com os dados esperados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['tipo_manifestacao'])) {
    try {
        // Gera protocolo único
        $protocolo = date('Ymd') . strtoupper(substr(uniqid(), 7, 6));
        $status = 'Recebida';

        // Detecta prefeitura (SaaS Context)
        $id_prefeitura_form = $_POST['pref_id'] ?? ($_GET['pref_id'] ?? 0);
        if (!$id_prefeitura_form && isset($_SESSION['id_prefeitura'])) $id_prefeitura_form = $_SESSION['id_prefeitura'];

        $stmt = $pdo->prepare(
            "INSERT INTO ouvidoria_manifestacoes 
                (protocolo, id_prefeitura, tipo_manifestacao, assunto, descricao, nome_cidadao, email, telefone, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->execute([
            $protocolo,
            $id_prefeitura_form,
            $_POST['tipo_manifestacao'],
            $_POST['assunto'],
            $_POST['descricao'],
            $_POST['nome_cidadao'],
            $_POST['email'],
            $_POST['telefone'],
            $status
        ]);

        // Redireciona de volta para a página principal da ouvidoria com o protocolo
        header("Location: ouvidoria.php?protocolo=" . urlencode($protocolo));
        exit;

    } catch (Exception $e) {
        // Em caso de erro, exibe uma mensagem
        die("Ocorreu um erro ao registrar sua manifestação. Por favor, tente novamente. Detalhe: " . $e->getMessage());
    }
} else {
    // Se o arquivo for acessado diretamente ou sem dados, redireciona para a ouvidoria
    header("Location: ouvidoria.php");
    exit;
}