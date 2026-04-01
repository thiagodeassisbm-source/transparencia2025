<?php
require_once 'auth_check.php';
require_once '../conexao.php';

echo "<h3>Lista de Usuários e Perfis</h3>";
$stmt = $pdo->query("SELECT id, usuario, perfil, id_perfil FROM usuarios_admin");
$users = $stmt->fetchAll();

echo "<table border='1'><tr><th>ID</th><th>Usuário</th><th>Perfil (ANTIGO)</th><th>ID Perfil (NOVO)</th></tr>";
foreach ($users as $u) {
    echo "<tr><td>{$u['id']}</td><td>{$u['usuario']}</td><td>{$u['perfil']}</td><td>" . ($u['id_perfil'] ?: 'NULL') . "</td></tr>";
}
echo "</table>";

echo "<h3>Lista de Perfis Cadastrados</h3>";
$stmt2 = $pdo->query("SELECT id, nome FROM perfis");
$perfis = $stmt2->fetchAll();
echo "<ul>";
foreach($perfis as $p) {
    echo "<li>ID {$p['id']}: {$p['nome']}</li>";
}
echo "</ul>";
?>
