<?php
require 'conexao.php';
$stmt = $pdo->query("DESCRIBE cards_informativos");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
