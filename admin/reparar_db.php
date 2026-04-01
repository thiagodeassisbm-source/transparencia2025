<?php
require_once '../conexao.php';

echo "<h2>Iniciando Reparo do Banco de Dados</h2>";

try {
    // 1. Tabela sic_solicitacoes
    $stmt = $pdo->query("SHOW COLUMNS FROM sic_solicitacoes");
    $colunas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('id_prefeitura', $colunas)) {
        echo "<p>Adicionando id_prefeitura em <b>sic_solicitacoes</b>...</p>";
        $pdo->exec("ALTER TABLE sic_solicitacoes ADD COLUMN id_prefeitura INT DEFAULT 0 AFTER id");
        echo "<p style='color:green'>Sucesso!</p>";
    } else {
        echo "<p>id_prefeitura já existe em sic_solicitacoes.</p>";
    }

    // 2. Tabela ouvidoria_manifestacoes
    $stmt2 = $pdo->query("SHOW COLUMNS FROM ouvidoria_manifestacoes");
    $colunas2 = $stmt2->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('id_prefeitura', $colunas2)) {
        echo "<p>Adicionando id_prefeitura em <b>ouvidoria_manifestacoes</b>...</p>";
        $pdo->exec("ALTER TABLE ouvidoria_manifestacoes ADD COLUMN id_prefeitura INT DEFAULT 0 AFTER id");
        echo "<p style='color:green'>Sucesso!</p>";
    } else {
        echo "<p>id_prefeitura já existe em ouvidoria_manifestacoes.</p>";
    }

    echo "<h3>Reparo Concluído. Você já pode acessar a <a href='sic_inbox.php'>Caixa de Entrada</a>.</h3>";
    echo "<p><i>Este arquivo (reparar_db.php) pode ser removido por segurança.</i></p>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Erro no reparo: " . $e->getMessage() . "</h3>";
}
