<?php
require_once '../conexao.php';

echo "<h2>Migração de Banco de Dados</h2>";

try {
    // Tenta adicionar a coluna
    $pdo->exec("ALTER TABLE prefeituras ADD COLUMN dominio_customizado VARCHAR(255) DEFAULT NULL AFTER slug");
    echo "<div style='color:green; padding:10px; border:1px solid green;'>✅ SUCESSO: A coluna 'dominio_customizado' foi criada com sucesso!</div>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "<div style='color:blue; padding:10px; border:1px solid blue;'>ℹ️ AVISO: A coluna já existe no banco de dados.</div>";
    } else {
        echo "<div style='color:red; padding:10px; border:1px solid red;'>❌ ERRO: " . $e->getMessage() . "</div>";
    }
}

echo "<br><a href='cadastrar_prefeitura.php'>Voltar para o Cadastro</a>";
?>
