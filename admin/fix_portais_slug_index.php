<?php
// /admin/fix_portais_slug_index.php
require_once dirname(__DIR__) . '/conexao.php';

echo "<!DOCTYPE html>
<html lang='pt-br'>
<head>
    <meta charset='UTF-8'>
    <title>SaaS Update - Correção de Índice de Slugs</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light p-5 border-top border-4 border-warning'>";

echo "<div class='container card shadow p-4'>";
echo "<h3>🛠️ Ajustando Estrutura de Slugs (Multi-Tenant)</h3><hr>";

try {
    // 1. Tenta identificar o nome da constraint/index único na coluna slug
    $stmt = $pdo->query("SHOW INDEX FROM portais WHERE Column_name = 'slug' AND Non_unique = 0");
    $index = $stmt->fetch();

    if ($index) {
        $index_name = $index['Key_name'];
        echo "<div class='alert alert-info'>🔎 Índice único antigo encontrado: <strong>$index_name</strong>. Removendo...</div>";
        $pdo->exec("ALTER TABLE portais DROP INDEX $index_name");
        echo "<div class='alert alert-success'>✅ Índice global removido.</div>";
    } else {
        echo "<div class='alert alert-light'>ℹ️ Nenhum índice global conflitante encontrado na coluna 'slug'.</div>";
    }

    // 2. Cria o novo índice composto (ID_PREFEITURA + SLUG)
    // Isso permite que prefeituras diferentes tenham o mesmo slug (ex: /contratos), 
    // mas a mesma prefeitura não pode repetir.
    echo "<div class='alert alert-info'>🚀 Criando novo índice composto (id_prefeitura, slug)...</div>";
    $pdo->exec("ALTER TABLE portais ADD UNIQUE INDEX unique_slug_per_prefeitura (id_prefeitura, slug)");
    
    echo "<hr><div class='alert alert-success text-center fw-bold'>ESTRUTURA ATUALIZADA COM SUCESSO!</div>";
    echo "<p class='text-muted text-center'>O sistema agora permite nomes de seções idênticos para prefeituras diferentes.</p>";
    echo "<a href='executar_clonagem.php?id=4&slug=hidrolandia' class='btn btn-primary w-100'>Tentar Clonagem Novamente</a>";

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ <strong>ERRO NA ATUALIZAÇÃO:</strong><br>" . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></body></html>";
?>
