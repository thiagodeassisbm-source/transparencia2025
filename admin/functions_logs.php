<?php
function registrar_log($pdo, $acao, $tabela, $detalhes) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $usuario_id = $_SESSION['admin_user_id'] ?? 0;
    $usuario_nome = $_SESSION['admin_user_nome'] ?? 'Sistema/Visitante';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO logs_sistema (usuario_id, usuario_nome, acao, tabela, detalhes, ip_endereco) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $usuario_nome, $acao, $tabela, $detalhes, $ip]);
    } catch (PDOException $e) {
        // Silenciosamente falha se a tabela não existir, ou loga em arquivo de erro se necessário
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
}
