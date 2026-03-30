<?php
require 'conexao.php';
session_start();

echo "ID Prefeitura na Sessão: " . ($_SESSION['id_prefeitura'] ?? 'NULL') . "<br>";

$stmt = $pdo->query("SELECT id, nome FROM prefeituras");
echo "Prefeituras:<br>";
var_dump($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "<br>Cards Informantes (Últimos 10):<br>";
$stmt = $pdo->query("SELECT id, titulo, id_prefeitura, id_secao FROM cards_informativos ORDER BY id DESC LIMIT 10");
var_dump($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "<br>Portais (Últimos 10):<br>";
$stmt = $pdo->query("SELECT id, nome, id_prefeitura FROM portais ORDER BY id DESC LIMIT 10");
var_dump($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
