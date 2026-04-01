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
        // --- REPARAÇÃO DE EMERGÊNCIA ON-THE-FLY ---
        $stmt_debug = $pdo->query("SHOW COLUMNS FROM ouvidoria_manifestacoes");
        $cols_debug = $stmt_debug->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('id_prefeitura', $cols_debug)) {
            $pdo->exec("ALTER TABLE ouvidoria_manifestacoes ADD COLUMN id_prefeitura INT DEFAULT 0 AFTER id");
        }
        // ------------------------------------------

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
        // Usamos base_url para garantir compatibilidade SaaS
        header("Location: " . $base_url . "portal/" . $slug_pref_header . "/ouvidoria.php?protocolo=" . urlencode($protocolo));
        exit;

    } catch (Exception $e) {
        // Log para debug
        error_log("Erro Ouvidoria: " . $e->getMessage());
        die("Erro ao registrar manifestação. Detalhe: " . $e->getMessage());
    }
} else {
    // Se o arquivo for acessado diretamente ou sem dados, redireciona para a ouvidoria
    header("Location: ouvidoria.php");
    exit;
}