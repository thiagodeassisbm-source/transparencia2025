<?php
require 'conexao.php';
$tables = ['categorias', 'portais', 'campos_portal', 'registros', 'valores_registros', 'cards_informativos'];
foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        while($row = $stmt->fetch()) {
            echo "Field: " . $row['Field'] . " | Type: " . $row['Type'] . "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
