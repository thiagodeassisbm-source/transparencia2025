<?php
require_once 'conexao.php';
$stmt = $pdo->prepare("DESCRIBE tipos_documento");
$stmt->execute();
$fields = $stmt->fetchAll();
print_r($fields);
?>
