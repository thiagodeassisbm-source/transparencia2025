<?php
require_once 'conexao.php';
$stmt = $pdo->prepare("DESCRIBE ouvidoria_manifestacoes");
$stmt->execute();
echo json_encode($stmt->fetchAll());
?>
