<?php
require_once 'conexao.php';

try {
    // 1. Criar tabela de recursos da landing page
    $sql = "CREATE TABLE IF NOT EXISTS landing_recursos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        descricao TEXT NOT NULL,
        icone VARCHAR(100) NOT NULL DEFAULT 'bi-info-circle',
        ordem INT DEFAULT 0,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Tabela landing_recursos criada com sucesso!<br>";

    // 2. Inserir dados iniciais se a tabela estiver vazia
    $stmt_check = $pdo->query("SELECT COUNT(*) FROM landing_recursos");
    if ($stmt_check->fetchColumn() == 0) {
        $stmt_insert = $pdo->prepare("INSERT INTO landing_recursos (titulo, descricao, icone, ordem) VALUES (?, ?, ?, ?)");
        
        $recursos = [
            ['Dados Abertos', 'Informações detalhadas sobre receitas, despesas, folha de pagamento e contratos em formato acessível.', 'bi-clipboard-data', 1],
            ['Diário Oficial', 'Publicações oficiais diárias, decretos, leis e atos administrativos com certificação digital.', 'bi-journal-text', 20],
            ['Conformidade Legal', 'Integralmente adequado à Lei de Acesso à Informação (LAI) e Lei de Responsabilidade Fiscal.', 'bi-shield-check', 30]
        ];

        foreach ($recursos as $r) {
            $stmt_insert->execute($r);
        }
        echo "Dados iniciais inseridos com sucesso!<br>";
    } else {
        echo "Tabela já contém dados, pulando inserção inicial.<br>";
    }

} catch (PDOException $e) {
    die("Erro ao criar tabela: " . $e->getMessage());
}
?>
