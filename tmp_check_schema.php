<?php
require 'conexao.php';
$stmt = $pdo->query("DESCRIBE cards_informativos");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($res, JSON_PRETTY_PRINT);
?>
