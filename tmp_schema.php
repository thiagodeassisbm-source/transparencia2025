<?php
require 'conexao.php';
try {
    $stmt = $pdo->query("DESCRIBE categorias");
    while($row = $stmt->fetch()) {
        echo "Field: " . $row['Field'] . " | Type: " . $row['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
