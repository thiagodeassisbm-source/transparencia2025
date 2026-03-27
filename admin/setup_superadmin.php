<?php
require_once '../conexao.php';

try {
    // 1. Garantir que a estrutura está pronta (as colunas já foram criadas no passo anterior, mas vamos garantir)
    $pdo->exec("CREATE TABLE IF NOT EXISTS prefeituras (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        logo VARCHAR(255) DEFAULT NULL,
        cor_principal VARCHAR(20) DEFAULT '#6366f1',
        status ENUM('ativo', 'suspenso') DEFAULT 'ativo',
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $cols = $pdo->query("SHOW COLUMNS FROM usuarios_admin")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('email', $cols)) $pdo->exec("ALTER TABLE usuarios_admin ADD COLUMN email VARCHAR(255) DEFAULT NULL UNIQUE AFTER usuario;");
    if (!in_array('is_superadmin', $cols)) $pdo->exec("ALTER TABLE usuarios_admin ADD COLUMN is_superadmin TINYINT(1) DEFAULT 0 AFTER id_perfil;");
    if (!in_array('id_prefeitura', $cols)) $pdo->exec("ALTER TABLE usuarios_admin ADD COLUMN id_prefeitura INT DEFAULT NULL AFTER is_superadmin;");

    // 2. Criar a prefeitura principal se não existir
    $stmt_p = $pdo->query("SELECT id FROM prefeituras WHERE slug = 'principal' LIMIT 1");
    $pref_id = $stmt_p->fetchColumn();
    if (!$pref_id) {
        $pdo->exec("INSERT INTO prefeituras (nome, slug) VALUES ('Prefeitura Principal', 'principal')");
        $pref_id = $pdo->lastInsertId();
    }

    // 3. RETIRAR superadmin do usuário "admin" comum (ele vira apenas admin da prefeitura principal)
    $admin_pass = password_hash('123456789', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE usuarios_admin SET is_superadmin = 0, id_prefeitura = ?, senha = ? WHERE usuario = 'admin'")
        ->execute([$pref_id, $admin_pass]);

    // 4. CRIAR ou ATUALIZAR o Super Admin Real
    $super_user = 'superadmin';
    $super_email = 'superadmin@sistema.com';
    $super_pass = password_hash('123456789', PASSWORD_DEFAULT);

    $stmt_check = $pdo->prepare("SELECT id FROM usuarios_admin WHERE usuario = ?");
    $stmt_check->execute([$super_user]);
    $super_exists = $stmt_check->fetchColumn();

    if ($super_exists) {
        $pdo->prepare("UPDATE usuarios_admin SET email = ?, senha = ?, is_superadmin = 1, id_prefeitura = NULL WHERE usuario = ?")
            ->execute([$super_email, $super_pass, $super_user]);
    } else {
        $pdo->prepare("INSERT INTO usuarios_admin (usuario, email, senha, id_perfil, is_superadmin, id_prefeitura, nome) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$super_user, $super_email, $super_pass, 1, 1, null, 'Super Gestor']);
    }

    echo "<h1>Separação de usuários concluída!</h1>";
    echo "<ul>
            <li>Usuário <b>admin</b> agora é Administrador Local (Prefeitura Principal).</li>
            <li>Usuário <b>superadmin</b> criado/atualizado com acesso Global.</li>
            <li>Ambos com senha: <b>123456789</b></li>
          </ul>";
    echo "<p><a href='login.php'>Voltar para o Login</a></p>";

} catch (Exception $e) {
    die("Erro ao configurar usuários: " . $e->getMessage());
}
