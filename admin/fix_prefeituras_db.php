<?php
require_once 'conexao.php';

try {
    // Adiciona a coluna dominio_customizado se ela não existir
    $pdo->exec("ALTER TABLE prefeituras ADD COLUMN dominio_customizado VARCHAR(255) DEFAULT NULL AFTER slug");
    echo "Coluna 'dominio_customizado' adicionada com sucesso!";
} catch (PDOException $e) {
    echo "Aviso: " . $e->getMessage();
}
?>
