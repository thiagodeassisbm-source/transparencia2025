<?php
require_once '../conexao.php';
$stmt = $pdo->prepare("DESCRIBE sic_solicitacoes");
$stmt->execute();
$fields = $stmt->fetchAll();
header('Content-Type: application/json');
echo json_encode($fields);
?>
