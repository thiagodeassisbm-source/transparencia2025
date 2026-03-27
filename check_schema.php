<?php
require 'conexao.php';
$stmt = $pdo->query('DESCRIBE cards_informativos');
echo json_encode($stmt->fetchAll(), JSON_PRETTY_PRINT);
