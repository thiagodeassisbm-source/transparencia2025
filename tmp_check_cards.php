<?php
require 'conexao.php';
$res = $pdo->query("SELECT * FROM cards_informativos WHERE id_secao IS NULL LIMIT 5")->fetchAll();
echo json_encode($res, JSON_PRETTY_PRINT);
?>
