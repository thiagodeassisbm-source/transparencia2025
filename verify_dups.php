<?php
require 'conexao.php';
$stmt = $pdo->query("SELECT MAX(id) FROM prefeituras");
$id = $stmt->fetchColumn();

// Fetch categories for this prefeitura
echo "Prefeitura recem criada ID: $id\n";
$stmt = $pdo->query("SELECT id, nome FROM categorias WHERE id_prefeitura = $id");
$cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo count($cats) . " categorias encontradas.\n";

// Fetch portais for this prefeitura
$stmt = $pdo->query("SELECT id, nome, slug FROM portais WHERE id_prefeitura = $id");
$portais = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo count($portais) . " portais encontrados.\n";

// Fetch cards for this prefeitura
$stmt = $pdo->query("SELECT id, titulo, id_categoria, id_secao FROM cards_informativos WHERE id_prefeitura = $id");
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo count($cards) . " cards encontrados.\n";
foreach($cards as $c) {
    echo "ID: {$c['id']} - [CAT: {$c['id_categoria']}] [SEC: {$c['id_secao']}] - {$c['titulo']}\n";
}
