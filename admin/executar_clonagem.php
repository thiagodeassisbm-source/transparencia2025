<?php
// /admin/executar_clonagem.php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/functions_logs.php';
require_once __DIR__ . '/functions_demo.php';

if (empty($_SESSION['is_superadmin'])) {
    header('Location: super_dashboard.php?error=Acesso negado');
    exit;
}

$id_destino = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_destino) {
    die('Especifique o ID da prefeitura de destino (ex: ?id=4)');
}

echo "<!DOCTYPE html>
<html lang='pt-br'>
<head>
    <meta charset='UTF-8'>
    <title>SaaS - Clonagem de Dados</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light p-5'>";

echo "<div class='container card shadow p-4 text-center'>";

try {
    $stmt = $pdo->prepare('SELECT nome FROM prefeituras WHERE id = ?');
    $stmt->execute([$id_destino]);
    $prefeitura = $stmt->fetch();

    if (!$prefeitura) {
        throw new Exception("Prefeitura destino (ID $id_destino) não encontrada.");
    }

    echo '<h3>Gerando conteúdo de demonstração para: <strong>' . htmlspecialchars($prefeitura['nome']) . '</strong></h3>';
    echo '<p class="text-muted">Isso pode levar alguns segundos dependendo da quantidade de dados...</p>';

    if (clonar_dados_demonstrativos($pdo, 1, $id_destino)) {
        registrar_log(
            $pdo,
            'SUPERADMIN',
            'CLONAGEM_DEMO',
            'Clonou dados demonstrativos para prefeitura "' . $prefeitura['nome'] . '" (ID: ' . $id_destino . ').'
        );
        echo "<div class='alert alert-success fw-bold py-4 my-4'>✅ CONTEÚDO CARREGADO COM SUCESSO!</div>";
        echo '<p>Agora você pode acessar o portal desta prefeitura para ver os dados de exemplo.</p>';
        $slug_portal = isset($_GET['slug']) ? htmlspecialchars((string) $_GET['slug'], ENT_QUOTES, 'UTF-8') : 'hidrolandia';
        echo '<a href="../portal/' . $slug_portal . '" class="btn btn-success" target="_blank">Ver Portal Público</a> ';
        echo "<a href='super_dashboard.php' class='btn btn-primary'>Voltar para Central</a>";
    } else {
        echo "<div class='alert alert-danger'>❌ Falha na clonagem. Verifique os logs do servidor.</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ <strong>ERRO:</strong> " . htmlspecialchars($e->getMessage()) . '</div>';
}

echo '</div></body></html>';
