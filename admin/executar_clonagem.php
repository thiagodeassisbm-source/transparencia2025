<?php
// /admin/executar_clonagem.php
require_once dirname(__DIR__) . '/conexao.php';
require_once 'functions_demo.php';

$id_destino = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_destino) { die("Especifique o ID da prefeitura de destino (ex: ?id=4)"); }

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
    // 1. Verifica se a prefeitura de destino existe
    $stmt = $pdo->prepare("SELECT nome FROM prefeituras WHERE id = ?");
    $stmt->execute([$id_destino]);
    $prefeitura = $stmt->fetch();
    
    if (!$prefeitura) {
        throw new Exception("Prefeitura destino (ID $id_destino) não encontrada.");
    }

    echo "<h3>Gerando conteúdo de demonstração para: <strong>" . $prefeitura['nome'] . "</strong></h3>";
    echo "<p class='text-muted'>Isso pode levar alguns segundos dependendo da quantidade de dados...</p>";

    // ID 1 é a prefeitura modelo/template
    if (clonar_dados_demonstrativos($pdo, 1, $id_destino)) {
        echo "<div class='alert alert-success fw-bold py-4 my-4'>✅ CONTEÚDO CARREGADO COM SUCESSO!</div>";
        echo "<p>Agora você pode acessar o portal desta prefeitura para ver os dados de exemplo.</p>";
        echo "<a href='../portal/" . (isset($_GET['slug']) ? $_GET['slug'] : 'hidrolandia') . "' class='btn btn-success' target='_blank'>Ver Portal Público</a> ";
        echo "<a href='super_dashboard.php' class='btn btn-primary'>Voltar para Central</a>";
    } else {
        echo "<div class='alert alert-danger'>❌ Falha na clonagem. Verifique os logs do servidor.</div>";
    }

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ <strong>ERRO:</strong> " . $e->getMessage() . "</div>";
}

echo "</div></body></html>";
?>
