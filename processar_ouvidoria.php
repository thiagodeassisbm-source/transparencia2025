<?php
session_start();
require_once 'conexao.php';

// Apenas aceita requisições do tipo POST com os dados esperados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['tipo_manifestacao'])) {
    try {
        // Gera protocolo único
        $protocolo = date('Ymd') . strtoupper(substr(uniqid(), 7, 6));
        $status = 'Recebida';

        // Detecta prefeitura (SaaS) — mesmo padrão do processar_sic.php; formulários antigos enviam id_prefeitura
        $id_prefeitura_form = (int)($_POST['pref_id'] ?? $_POST['id_prefeitura'] ?? $_GET['pref_id'] ?? 0);
        $pref_slug_post = trim((string)($_POST['pref_slug'] ?? ''));
        if ($id_prefeitura_form <= 0 && $pref_slug_post !== '') {
            $stmt_slug = $pdo->prepare('SELECT id FROM prefeituras WHERE slug = ? LIMIT 1');
            $stmt_slug->execute([$pref_slug_post]);
            $id_prefeitura_form = (int)$stmt_slug->fetchColumn();
        }
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
        
        $nome_cidadao = trim((string)($_POST['nome_cidadao'] ?? $_POST['nome_solicitante'] ?? ''));
        $descricao = (string)($_POST['descricao'] ?? $_POST['mensagem'] ?? '');
        $assunto = trim((string)($_POST['assunto'] ?? ''));

        $stmt->execute([
            $protocolo,
            $id_prefeitura_form,
            $_POST['tipo_manifestacao'],
            $assunto,
            $descricao,
            $nome_cidadao,
            $_POST['email'] ?? '',
            $_POST['telefone'] ?? '',
            $status
        ]);

        // Redireciona de volta para a página principal da ouvidoria com o protocolo
        $slug_redir = $_POST['pref_slug'] ?? 'principal';
        header("Location: " . $base_url . "portal/" . $slug_redir . "/ouvidoria.php?protocolo=" . urlencode($protocolo));
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