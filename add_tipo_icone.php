<?php
require 'conexao.php';
try {
    $pdo->exec("ALTER TABLE cards_informativos ADD COLUMN tipo_icone ENUM('imagem', 'bootstrap') DEFAULT 'imagem' AFTER caminho_icone");
    echo "Coluna tipo_icone adicionada com sucesso!";
} catch (PDOException $e) {
    echo "Erro (pode já existir): " . $e->getMessage();
}
