<?php
require 'conexao.php';
$stmt = $pdo->query("SELECT id, titulo, id_categoria, id_secao FROM cards_informativos WHERE id_prefeitura = 1");
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "CARDS ORIGINAIS (id_prefeitura=1):\n";
foreach($cards as $c) {
    echo "ID: {$c['id']} - Titulo: {$c['titulo']} - Cat: {$c['id_categoria']} - Secao: {$c['id_secao']}\n";
}
