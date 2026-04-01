<?php
require_once 'conexao.php';
$stmt = $pdo->prepare("SELECT id, nome, slug FROM prefeituras WHERE slug = 'principal'");
$stmt->execute();
$pref = $stmt->fetch();
echo json_encode($pref);
?>
