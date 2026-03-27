<?php
require_once '../conexao.php';

try {
    // 1. Criar/Atualizar tabela de prefeituras com campos financeiros e de contrato
    $pdo->exec("CREATE TABLE IF NOT EXISTS prefeituras (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        logo VARCHAR(255) DEFAULT NULL,
        cor_principal VARCHAR(20) DEFAULT '#6366f1',
        status ENUM('ativo', 'suspenso', 'pendente_pagamento') DEFAULT 'ativo',
        data_contratacao DATE DEFAULT NULL,
        data_ultimo_pagamento DATE DEFAULT NULL,
        valor_mensalidade DECIMAL(10,2) DEFAULT 0.00,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Garantir que as colunas novas existam
    $cols_p = $pdo->query("SHOW COLUMNS FROM prefeituras")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('data_contratacao', $cols_p)) $pdo->exec("ALTER TABLE prefeituras ADD COLUMN data_contratacao DATE DEFAULT NULL;");
    if (!in_array('data_ultimo_pagamento', $cols_p)) $pdo->exec("ALTER TABLE prefeituras ADD COLUMN data_ultimo_pagamento DATE DEFAULT NULL;");
    if (!in_array('valor_mensalidade', $cols_p)) $pdo->exec("ALTER TABLE prefeituras ADD COLUMN valor_mensalidade DECIMAL(10,2) DEFAULT 0.00;");

    // 2. Estrutura de usuários e isolamento (como antes)
    $cols = $pdo->query("SHOW COLUMNS FROM usuarios_admin")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('email', $cols)) $pdo->exec("ALTER TABLE usuarios_admin ADD COLUMN email VARCHAR(255) DEFAULT NULL UNIQUE AFTER usuario;");
    if (!in_array('is_superadmin', $cols)) $pdo->exec("ALTER TABLE usuarios_admin ADD COLUMN is_superadmin TINYINT(1) DEFAULT 0 AFTER id_perfil;");
    if (!in_array('id_prefeitura', $cols)) $pdo->exec("ALTER TABLE usuarios_admin ADD COLUMN id_prefeitura INT DEFAULT NULL AFTER is_superadmin;");

    $stmt_p = $pdo->query("SELECT id FROM prefeituras WHERE slug = 'principal' LIMIT 1");
    $pref_id = $stmt_p->fetchColumn();
    if (!$pref_id) {
        $pdo->exec("INSERT INTO prefeituras (nome, slug, data_contratacao, status) VALUES ('Prefeitura Principal', 'principal', CURDATE(), 'ativo')");
        $pref_id = $pdo->lastInsertId();
    }

    $tables = ['portais', 'categorias', 'configuracoes', 'ouvidoria_manifestacoes', 'paginas', 'tipos_documento'];
    foreach ($tables as $table) {
        $res = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($res->rowCount() > 0) {
            $cols_t = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('id_prefeitura', $cols_t)) {
                $pdo->exec("ALTER TABLE $table ADD COLUMN id_prefeitura INT DEFAULT NULL;");
            }
            $pdo->exec("UPDATE $table SET id_prefeitura = $pref_id WHERE id_prefeitura IS NULL");
        }
    }

    // 3. Resetar usuários (Admin vs SuperAdmin)
    $pdo->exec("UPDATE usuarios_admin SET email = 'admin@sistema.com' WHERE usuario = 'admin'");
    $pass_geral = password_hash('123456789', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE usuarios_admin SET is_superadmin = 0, id_prefeitura = ?, senha = ? WHERE usuario = 'admin'")
        ->execute([$pref_id, $pass_geral]);

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

    echo "<h1>Infraestrutura SaaS (Contratos/Pagamentos) Atualizada!</h1>";
    echo "<ul>
            <li>Dashboard Financeiro - OK</li>
            <li>Status de Pagamento em Prefeituras - OK</li>
            <li>Contas separadas (admin vs superadmin) - OK</li>
          </ul>";
    echo "<p><a href='login.php'>Ir para o Login</a></p>";

} catch (Exception $e) {
    die("Erro ao configurar infraestrutura: " . $e->getMessage());
}
