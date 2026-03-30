<?php
require_once 'conexao.php';

try {
    // 1. Tabela de Mensagens do Sistema
    $sql1 = "CREATE TABLE IF NOT EXISTS mensagens_sistema (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_prefeitura INT DEFAULT NULL, -- NULL = Todos
        titulo VARCHAR(255) NOT NULL,
        mensagem TEXT NOT NULL,
        cor VARCHAR(20) DEFAULT 'primary', -- primary, warning, danger, success
        ativa TINYINT(1) DEFAULT 1,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql1);
    echo "Tabela mensagens_sistema criada com sucesso!<br>";

    // 2. Tabela de Controle de Leitura/Visualização
    $sql2 = "CREATE TABLE IF NOT EXISTS mensagens_vistas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_mensagem INT NOT NULL,
        id_usuario INT NOT NULL,
        id_prefeitura INT NOT NULL,
        visto_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_mensagem) REFERENCES mensagens_sistema(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql2);
    echo "Tabela mensagens_vistas criada com sucesso!<br>";

} catch (PDOException $e) {
    die("Erro ao criar tabelas de comunicação: " . $e->getMessage());
}
?>
