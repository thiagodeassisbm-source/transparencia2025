<?php
// /admin/debug_schema.php
require_once '../conexao.php';

echo "<h2>Debug Schema: sic_solicitacoes</h2>";

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM sic_solicitacoes");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($cols as $col) {
        echo "<li><b>{$col['Field']}</b> - {$col['Type']} (" . ($col['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . ")</li>";
    }
    echo "</ul>";

} catch (Exception $e) {
    echo "<h3 style='color:red'>Erro no debug: " . $e->getMessage() . "</h3>";
}
