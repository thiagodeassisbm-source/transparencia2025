<?php
// /admin/fix_config_table.php
require_once dirname(__DIR__) . '/conexao.php';

echo "<!DOCTYPE html>
<html lang='pt-br'>
<head>
    <meta charset='UTF-8'>
    <title>Reparo de Banco de Dados - Multi-Tenant</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light p-5'>";

echo "<div class='container card shadow p-4'>";
echo "<h3>🚀 Iniciando Migração da Tabela 'configuracoes'...</h3><hr>";

try {
    // 1. Verifica se a coluna id_prefeitura já existe
    $stmt = $pdo->query("SHOW COLUMNS FROM configuracoes LIKE 'id_prefeitura'");
    $existe_coluna = $stmt->fetch();
    
    if (!$existe_coluna) {
        $pdo->exec("ALTER TABLE configuracoes ADD COLUMN id_prefeitura INT AFTER id");
        echo "<div class='alert alert-success'>✅ Coluna <strong>id_prefeitura</strong> adicionada com sucesso.</div>";
    } else {
        echo "<div class='alert alert-info'>ℹ️ Coluna <strong>id_prefeitura</strong> já existe. OK.</div>";
    }

    // 2. Localiza e remove o índice UNITÁRIO na coluna 'chave'
    $stmt = $pdo->query("SHOW INDEX FROM configuracoes WHERE COLUMN_NAME = 'chave' AND Non_unique = 0");
    $indices_unicos = $stmt->fetchAll();
    
    foreach ($indices_unicos as $idx) {
        $idx_name = $idx['Key_name'];
        if ($idx_name !== 'UN_PREF_CHAVE') { // Não remove o novo se ele já existir
            $pdo->exec("ALTER TABLE configuracoes DROP INDEX `$idx_name` ");
            echo "<div class='alert alert-warning'>⚠️ Índice único antigo (<code>$idx_name</code>) removido de 'chave'.</div>";
        }
    }

    // 3. Adiciona o novo índice composto (id_prefeitura, chave)
    // Verifica se já existe para evitar erro de duplicata de índice
    $stmt = $pdo->query("SHOW INDEX FROM configuracoes WHERE Key_name = 'UN_PREF_CHAVE'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE configuracoes ADD UNIQUE INDEX UN_PREF_CHAVE (id_prefeitura, chave)");
        echo "<div class='alert alert-success'>✅ Novo índice Multi-Tenant (<strong>id_prefeitura + chave</strong>) criado.</div>";
    } else {
        echo "<div class='alert alert-info'>ℹ️ Índice Multi-Tenant já configurado. OK.</div>";
    }

    // 4. Garante que ninguém ficará sem prefeitura 
    $pdo->exec("UPDATE configuracoes SET id_prefeitura = 1 WHERE id_prefeitura IS NULL OR id_prefeitura = 0");
    echo "<div class='alert alert-success'>✅ Registros órfãos vinculados à prefeitura principal.</div>";

    echo "<hr><div class='alert alert-success text-center fw-bold'>MIGRAÇÃO CONCLUÍDA! Tabela pronta para SaaS.</div>";
    echo "<a href='cadastrar_prefeitura.php' class='btn btn-primary w-100'>Voltar para Cadastrar Prefeitura</a>";

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ <strong>ERRO NA MIGRAÇÃO:</strong><br>" . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></body></html>";
?>
