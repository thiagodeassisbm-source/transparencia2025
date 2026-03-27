<?php
require_once '../conexao.php';

try {
    // 1. Criar tabela de prefeituras
    $pdo->exec("CREATE TABLE IF NOT EXISTS prefeituras (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        logo VARCHAR(255) DEFAULT NULL,
        cor_principal VARCHAR(20) DEFAULT '#6366f1',
        status ENUM('ativo', 'suspenso') DEFAULT 'ativo',
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Atualizar tabela de usuários admin
    $cols = $pdo->query("SHOW COLUMNS FROM usuarios_admin")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('email', $cols)) {
        $pdo->exec("ALTER TABLE usuarios_admin ADD COLUMN email VARCHAR(255) DEFAULT NULL UNIQUE AFTER usuario;");
    }
    if (!in_array('is_superadmin', $cols)) {
        $pdo->exec("ALTER TABLE usuarios_admin ADD COLUMN is_superadmin TINYINT(1) DEFAULT 0 AFTER id_perfil;");
    }
    if (!in_array('id_prefeitura', $cols)) {
        $pdo->exec("ALTER TABLE usuarios_admin ADD COLUMN id_prefeitura INT DEFAULT NULL AFTER is_superadmin;");
    }

    // Adicionar id_prefeitura em outras tabelas para isolamento de dados
    $tables = ['portais', 'categorias', 'configuracoes'];
    foreach ($tables as $table) {
        $cols_t = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('id_prefeitura', $cols_t)) {
            $pdo->exec("ALTER TABLE $table ADD COLUMN id_prefeitura INT DEFAULT NULL;");
        }
    }

    // 3. Criar a primeira prefeitura (os dados atuais pertencerão a ela)
    $stmt_check_pref = $pdo->query("SELECT COUNT(*) FROM prefeituras");
    if ($stmt_check_pref->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO prefeituras (nome, slug) VALUES ('Prefeitura Principal', 'principal')");
        $first_pref_id = $pdo->lastInsertId();
        
        // Atribuir todos os dados atuais a esta prefeitura
        $pdo->exec("UPDATE usuarios_admin SET id_prefeitura = $first_pref_id WHERE is_superadmin = 0");
        if ($pdo->query("SHOW TABLES LIKE 'portais'")->rowCount() > 0) $pdo->exec("UPDATE portais SET id_prefeitura = $first_pref_id");
        if ($pdo->query("SHOW TABLES LIKE 'categorias'")->rowCount() > 0) $pdo->exec("UPDATE categorias SET id_prefeitura = $first_pref_id");
        if ($pdo->query("SHOW TABLES LIKE 'configuracoes'")->rowCount() > 0) $pdo->exec("UPDATE configuracoes SET id_prefeitura = $first_pref_id");
    }

    // 4. Transformar o admin atual em Super Admin
    $pdo->exec("UPDATE usuarios_admin SET email = 'superadmin@sistema.com', is_superadmin = 1 WHERE usuario = 'admin' LIMIT 1");

    echo "<h1>Migração Super Admin (Compatível) concluída!</h1>";
    echo "<ul>
            <li>Banco de Dados Configurado com Sucesso.</li>
            <li>Usuário 'admin' promovido a Super Admin.</li>
          </ul>";
    echo "<p><a href='login.php'>Ir para Login</a></p>";

} catch (Exception $e) {
    die("Erro na migração: " . $e->getMessage());
}
