<?php
// /admin/setup_demo_module.php
require_once dirname(__DIR__) . '/conexao.php';

echo "<!DOCTYPE html>
<html lang='pt-br'>
<head>
    <meta charset='UTF-8'>
    <title>SaaS Update - Módulo de Demonstração</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light p-5 border-top border-4 border-primary'>";

echo "<div class='container card shadow p-4'>";
echo "<h3>🚀 Ativando Módulo de Demonstração (Demo Content)</h3><hr>";

try {
    // 1. Adiciona 'is_demo' na tabela 'portais' (Seções)
    $stmt = $pdo->query("SHOW COLUMNS FROM portais LIKE 'is_demo'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE portais ADD COLUMN is_demo TINYINT(1) DEFAULT 0 AFTER id_prefeitura");
        echo "<div class='alert alert-success'>✅ ID_DEMO adicionado à tabela de <strong>Portais (Seções)</strong>.</div>";
    }

    // 2. Adiciona 'is_demo' na tabela 'registros' (Lançamentos de dados)
    $stmt = $pdo->query("SHOW COLUMNS FROM registros LIKE 'is_demo'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE registros ADD COLUMN is_demo TINYINT(1) DEFAULT 0 AFTER id_portal");
        echo "<div class='alert alert-success'>✅ ID_DEMO adicionado à tabela de <strong>Registros (Lançamentos)</strong>.</div>";
    }

    // 3. Adiciona 'is_demo' na tabela 'cards_informativos' (Home do Portal)
    $stmt = $pdo->query("SHOW COLUMNS FROM cards_informativos LIKE 'is_demo'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE cards_informativos ADD COLUMN is_demo TINYINT(1) DEFAULT 0 AFTER id_secao");
        echo "<div class='alert alert-success'>✅ ID_DEMO adicionado à tabela de <strong>Cards Informativos (Home)</strong>.</div>";
    }

    echo "<hr><div class='alert alert-success text-center fw-bold'>MÓDULO DE DEMONSTRAÇÃO PRONTO!</div>";
    echo "<p class='text-muted small text-center'>A estrutura de dados agora suporta a identificação e limpeza automática de conteúdo fictício.</p>";
    echo "<a href='super_dashboard.php' class='btn btn-primary w-100'>Ir para a Central de Inteligência</a>";

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ <strong>ERRO NA ATUALIZAÇÃO:</strong><br>" . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></body></html>";
?>
