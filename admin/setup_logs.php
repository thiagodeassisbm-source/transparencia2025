<?php
require_once '../conexao.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS logs_sistema (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT DEFAULT 0,
        usuario_nome VARCHAR(255) DEFAULT NULL,
        acao VARCHAR(50) NOT NULL,
        tabela VARCHAR(100) DEFAULT NULL,
        detalhes TEXT,
        ip_endereco VARCHAR(45) DEFAULT NULL,
        horario DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    echo "Tabela logs_sistema criada com sucesso!";
} catch (PDOException $e) {
    die("Erro ao criar tabela de logs: " . $e->getMessage());
}
