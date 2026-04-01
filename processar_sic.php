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
        // --- REPARAÇÃO DE EMERGÊNCIA ON-THE-FLY ---
        $stmt_debug = $pdo->query("SHOW COLUMNS FROM sic_solicitacoes");
        $cols_debug = $stmt_debug->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('id_prefeitura', $cols_debug)) {
            $pdo->exec("ALTER TABLE sic_solicitacoes ADD COLUMN id_prefeitura INT DEFAULT 0 AFTER id");
        }
        // ------------------------------------------

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
        $slug_redir = $_POST['pref_slug'] ?? 'principal';
        header("Location: " . $base_url . "portal/" . $slug_redir . "/sic.php?protocolo=" . urlencode($protocolo));
        exit;

    } catch (Exception $e) {
        // Log para debug
        error_log("Erro e-SIC: " . $e->getMessage());
        die("Erro ao registrar solicitação. Detalhe: " . $e->getMessage());
    }
} else {
    // Se o arquivo for acessado diretamente ou sem dados, redireciona para a página do SIC
    header("Location: sic.php");
    exit;
}