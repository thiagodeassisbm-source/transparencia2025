<?php
require_once 'conexao.php';
$stmt = $pdo->query("SHOW COLUMNS FROM prefeituras");
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
