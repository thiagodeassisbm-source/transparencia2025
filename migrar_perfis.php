<?php
require_once 'conexao.php';

try {
    // 1. Criar tabela de perfis
    $pdo->exec("CREATE TABLE IF NOT EXISTS perfis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(50) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Criar tabela de permissões por perfil
    $pdo->exec("CREATE TABLE IF NOT EXISTS permissoes_perfil (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_perfil INT NOT NULL,
        recurso VARCHAR(100) NOT NULL,
        p_ver TINYINT(1) DEFAULT 0,
        p_lancar TINYINT(1) DEFAULT 0,
        p_editar TINYINT(1) DEFAULT 0,
        p_excluir TINYINT(1) DEFAULT 0,
        FOREIGN KEY (id_perfil) REFERENCES perfis(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. Inserir perfis padrão se não existirem
    $stmt = $pdo->query("SELECT COUNT(*) FROM perfis");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO perfis (nome) VALUES ('Administrador'), ('Editor')");
        echo "Perfis 'Administrador' e 'Editor' criados.<br>";
        
        // Permissões padrão para Administrador (Tudo)
        $id_admin = $pdo->lastInsertId() - 1; // Ajuste simples para pegar o primeiro inserted
        // Na verdade, buscaremos os IDs
        $stmt_ids = $pdo->query("SELECT id, nome FROM perfis");
        $perfis_ids = $stmt_ids->fetchAll(PDO::FETCH_KEY_PAIR);
        $id_admin = array_search('Administrador', $perfis_ids);
        $id_editor = array_search('Editor', $perfis_ids);

        // Administrador tem acesso a tudo (registros genéricos para representar módulos)
        $recursos = ['dashboard', 'usuarios', 'configuracoes', 'ouvidoria', 'sic', 'secoes'];
        foreach ($recursos as $r) {
            $pdo->prepare("INSERT INTO permissoes_perfil (id_perfil, recurso, p_ver, p_lancar, p_editar, p_excluir) VALUES (?, ?, 1, 1, 1, 1)")
                ->execute([$id_admin, $r]);
        }
        
        // Editor tem acesso limitado (exemplo)
        $pdo->prepare("INSERT INTO permissoes_perfil (id_perfil, recurso, p_ver, p_lancar, p_editar, p_excluir) VALUES (?, 'dashboard', 1, 0, 0, 0)")
            ->execute([$id_editor]);
    }

    // 4. Adicionar coluna id_perfil na tabela usuarios_admin se não existir
    try {
        $pdo->exec("ALTER TABLE usuarios_admin ADD COLUMN id_perfil INT AFTER perfil");
        echo "Coluna 'id_perfil' adicionada em usuarios_admin.<br>";
        
        // Migrar dados da coluna 'perfil' antiga para a nova 'id_perfil'
        $stmt_ids = $pdo->query("SELECT id, nome FROM perfis");
        $perfis_map = $stmt_ids->fetchAll(PDO::FETCH_KEY_PAIR);
        $perfil_admin_id = array_search('Administrador', $perfis_map);
        $perfil_editor_id = array_search('Editor', $perfis_map);

        $pdo->exec("UPDATE usuarios_admin SET id_perfil = $perfil_admin_id WHERE perfil = 'admin'");
        $pdo->exec("UPDATE usuarios_admin SET id_perfil = $perfil_editor_id WHERE perfil = 'editor'");
        echo "Migração de perfis de usuários concluída.<br>";

    } catch (PDOException $e) {
        // Ignora se a coluna já existe
    }

    echo "<h3>Migração de Perfis e Permissões concluída com sucesso!</h3>";
    echo "<p>Agora você pode gerenciar o que cada perfil pode acessar.</p>";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>Erro na migração:</h2> " . $e->getMessage();
}
?>
