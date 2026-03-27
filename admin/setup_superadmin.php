<?php
require_once '../conexao.php';

try {
    // 1. Criar/Atualizar tabela de prefeituras com dados do responsável e cobrança
    $pdo->exec("CREATE TABLE IF NOT EXISTS prefeituras (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        responsavel_nome VARCHAR(255) DEFAULT NULL,
        responsavel_contato VARCHAR(50) DEFAULT NULL,
        dia_vencimento INT DEFAULT 10,
        status ENUM('ativo', 'suspenso', 'pendente_pagamento') DEFAULT 'ativo',
        data_contratacao DATE DEFAULT NULL,
        data_ultimo_pagamento DATE DEFAULT NULL,
        valor_mensalidade DECIMAL(10,2) DEFAULT 0.00,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Garantir que todas as colunas novas existam
    $cols_p = $pdo->query("SHOW COLUMNS FROM prefeituras")->fetchAll(PDO::FETCH_COLUMN);
    $novas_colunas = [
        'responsavel_nome' => "ALTER TABLE prefeituras ADD COLUMN responsavel_nome VARCHAR(255) DEFAULT NULL AFTER slug;",
        'responsavel_contato' => "ALTER TABLE prefeituras ADD COLUMN responsavel_contato VARCHAR(50) DEFAULT NULL AFTER responsavel_nome;",
        'dia_vencimento' => "ALTER TABLE prefeituras ADD COLUMN dia_vencimento INT DEFAULT 10 AFTER responsavel_contato;",
        'data_contratacao' => "ALTER TABLE prefeituras ADD COLUMN data_contratacao DATE DEFAULT NULL;",
        'data_ultimo_pagamento' => "ALTER TABLE prefeituras ADD COLUMN data_ultimo_pagamento DATE DEFAULT NULL;",
        'valor_mensalidade' => "ALTER TABLE prefeituras ADD COLUMN valor_mensalidade DECIMAL(10,2) DEFAULT 0.00;"
    ];

    foreach ($novas_colunas as $col => $sql) {
        if (!in_array($col, $cols_p)) {
            $pdo->exec($sql);
        }
    }

    // 2. Garantir isolamento em tabelas críticas (como antes)
    $tables = ['portais', 'categorias', 'configuracoes', 'ouvidoria_manifestacoes', 'paginas', 'tipos_documento', 'logs_sistema'];
    foreach ($tables as $table) {
        $res = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($res->rowCount() > 0) {
            $cols_t = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('id_prefeitura', $cols_t)) {
                $pdo->exec("ALTER TABLE $table ADD COLUMN id_prefeitura INT DEFAULT NULL;");
            }
        }
    }

    // 3. Garantir que os usuários estão corretos
    $super_user = 'superadmin';
    $super_pass = password_hash('123456789', PASSWORD_DEFAULT);
    $super_email = 'superadmin@sistema.com';

    $stmt_s = $pdo->prepare("SELECT id FROM usuarios_admin WHERE usuario = ?");
    $stmt_s->execute([$super_user]);
    $super_id = $stmt_s->fetchColumn();

    if ($super_id) {
        $pdo->prepare("UPDATE usuarios_admin SET is_superadmin = 1, email = ?, senha = ?, id_prefeitura = NULL WHERE id = ?")
            ->execute([$super_email, $super_pass, $super_id]);
    } else {
        $pdo->prepare("INSERT INTO usuarios_admin (usuario, email, senha, is_superadmin, id_prefeitura, nome, id_perfil) VALUES (?, ?, ?, 1, NULL, 'Super Gestor', 1)")
            ->execute([$super_user, $super_email, $super_pass]);
    }

    echo "<h1>Saas Upgrade: Gestão de Clientes Ativada!</h1>";
    echo "<ul><li>Base Financeira e Contratos PRONTA.</li><li>Dados do Responsável PRONTOS.</li></ul>";
    echo "<p><a href='login.php'>Voltar ao Login</a></p>";

} catch (Exception $e) {
    die("Erro no Upgrade Saas: " . $e->getMessage());
}
