<?php
require_once '../conexao.php';

// --- DEFINA SUAS CREDENCIAIS AQUI ---
$usuario_admin = 'admin';
$senha_admin = 'senhaforte123'; // Troque por uma senha forte

// Criptografa a senha de forma segura
$senha_hash = password_hash($senha_admin, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO usuarios_admin (usuario, senha) VALUES (?, ?)");
    $stmt->execute([$usuario_admin, $senha_hash]);
    echo "<h1>Usuário Administrador criado com sucesso!</h1>";
    echo "<p><b>Usuário:</b> " . htmlspecialchars($usuario_admin) . "</p>";
    echo "<p><b>Senha:</b> " . htmlspecialchars($senha_admin) . "</p>";
    echo "<h2 style='color:red;'>ATENÇÃO: DELETE ESTE ARQUIVO (setup_admin.php) AGORA MESMO!</h2>";
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        die("Erro: O usuário '" . htmlspecialchars($usuario_admin) . "' já existe no banco de dados.");
    }
    die("Erro ao criar usuário: " . $e->getMessage());
}
?>