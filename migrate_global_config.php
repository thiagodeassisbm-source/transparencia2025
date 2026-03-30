<?php
require_once 'conexao.php';

try {
    // 1. Tabela de Configurações Globais (Chave-Valor)
    $sql = "CREATE TABLE IF NOT EXISTS config_global (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(100) UNIQUE NOT NULL,
        valor TEXT,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Tabela config_global criada com sucesso!<br>";

    // 2. Inserir chaves padrão de copyright
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM config_global WHERE chave = ?");
    
    $configs_padrao = [
        ['chave' => 'copyright_ano', 'valor' => date('Y')],
        ['chave' => 'copyright_dev_nome', 'valor' => 'UpGyn'],
        ['chave' => 'copyright_dev_site', 'valor' => 'https://www.upgyn.com.br'],
        ['chave' => 'copyright_texto', 'valor' => 'Todos os Direitos Reservados']
    ];

    foreach ($configs_padrao as $cfg) {
        $stmt_check->execute([$cfg['chave']]);
        if ($stmt_check->fetchColumn() == 0) {
            $stmt_ins = $pdo->prepare("INSERT INTO config_global (chave, valor) VALUES (?, ?)");
            $stmt_ins->execute([$cfg['chave'], $cfg['valor']]);
            echo "Chave '{$cfg['chave']}' inserida com sucesso!<br>";
        }
    }

} catch (PDOException $e) {
    die("Erro ao criar tabela de configurações globais: " . $e->getMessage());
}
?>
