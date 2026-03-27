<?php
require_once '../conexao.php';

try {
    // 1. Garantir que a estrutura está pronta
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

    // Adicionar id_prefeitura em TODAS as tabelas críticas para isolamento
    $tables = ['portais', 'categorias', 'configuracoes', 'ouvidoria_manifestacoes', 'paginas', 'tipos_documento'];
    foreach ($tables as $table) {
        // Verifica se a tabela existe
        $res = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($res->rowCount() > 0) {
            $cols_t = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('id_prefeitura', $cols_t)) {
                $pdo->exec("ALTER TABLE $table ADD COLUMN id_prefeitura INT DEFAULT NULL;");
            }
            // Garante que os dados antigos pertençam à prefeitura principal
            $pdo->exec("UPDATE $table SET id_prefeitura = $pref_id WHERE id_prefeitura IS NULL");
        }
    }

    // 3. RETIRAR superadmin do usuário "admin" comum
    $pdo->exec("UPDATE usuarios_admin SET email = 'admin@sistema.com' WHERE usuario = 'admin'");
    $pass_comum = password_hash('123456789', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE usuarios_admin SET is_superadmin = 0, id_prefeitura = ?, senha = ? WHERE usuario = 'admin'")
        ->execute([$pref_id, $pass_comum]);

    // 4. CRIAR ou ATUALIZAR o Super Admin Real
    $super_user = 'superadmin';
    $super_email = 'superadmin@sistema.com';
    $super_pass = password_hash('123456789', PASSWORD_DEFAULT);

    $stmt_super = $pdo->prepare("SELECT id FROM usuarios_admin WHERE usuario = ?");
    $stmt_super->execute([$super_user]);
    $super_id = $stmt_super->fetchColumn();

    if ($super_id) {
        $pdo->prepare("UPDATE usuarios_admin SET email = ?, senha = ?, is_superadmin = 1, id_prefeitura = NULL WHERE id = ?")
            ->execute([$super_email, $super_pass, $super_id]);
    } else {
        $pdo->prepare("INSERT INTO usuarios_admin (usuario, email, senha, id_perfil, is_superadmin, id_prefeitura, nome) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$super_user, $super_email, $super_pass, 1, 1, null, 'Super Gestor']);
    }

    echo "<h1>Migração Finalizada com Sucesso!</h1>";
    echo "<ul>
            <li>Isolamento de dados aplicado em Ouvidoria, Páginas e Portais.</li>
            <li>Acessos configurados para Separar Admin de SuperAdmin.</li>
          </ul>";
    echo "<p><a href='login.php'>Ir para o Login</a></p>";

} catch (Exception $e) {
    die("Erro ao configurar usuários: " . $e->getMessage());
}
