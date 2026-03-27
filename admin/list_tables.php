<?php
require_once dirname(__DIR__) . '/conexao.php';

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "<h3>Tabela: $table</h3>";
    $columns = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll();
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
    }
    echo "</ul><hr>";
}
?>
