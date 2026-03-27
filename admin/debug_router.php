<?php
// /admin/debug_router.php
require_once dirname(__DIR__) . '/conexao.php';

echo "<!DOCTYPE html>
<html lang='pt-br'>
<head>
    <meta charset='UTF-8'>
    <title>SaaS Diagnostic - Auditor de Rotas</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-dark text-white p-5'>";

echo "<div class='container card bg-secondary shadow p-4'>";
echo "<h3>🛰️ Diagnosticando Rota SaaS</h3><hr>";

echo "<h5>Informações Recebidas pelo Servidor:</h5>";
echo "<pre class='bg-dark text-success p-3 rounded'>";
echo "REQUEST_URI: " . htmlspecialchars($_SERVER['REQUEST_URI']) . "\n";
echo "QUERY_STRING: " . htmlspecialchars($_SERVER['QUERY_STRING'] ?? '') . "\n";
echo "SLUG DETECTADO: " . htmlspecialchars($_GET['slug'] ?? 'Nenhum') . "\n";
echo "PREF_SLUG DETECTADO: " . htmlspecialchars($_GET['pref_slug'] ?? 'Nenhum') . "\n";
echo "</pre>";

if (isset($_GET['slug']) || isset($_GET['pref_slug'])) {
    $slug = $_GET['slug'] ?? $_GET['pref_slug'];
    $stmt = $pdo->prepare("SELECT id, nome FROM prefeituras WHERE slug = ?");
    $stmt->execute([$slug]);
    $pref = $stmt->fetch();
    
    if ($pref) {
        echo "<div class='alert alert-success'>✅ Prefeitura indentificada: <strong>" . $pref['nome'] . " (ID: " . $pref['id'] . ")</strong></div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Nenhuma prefeitura cadastrada com o slug: <strong>$slug</strong></div>";
    }
}

echo "<hr><p>Verificação de Caminhos:</p>";
echo "<ul>";
echo "<li>Raiz: " . dirname(__DIR__) . "</li>";
echo "<li>Pasta Admin existe? " . (is_dir(__DIR__) ? 'Sim' : 'Não') . "</li>";
echo "<li>Login.php existe? " . (file_exists(__DIR__ . '/login.php') ? 'Sim' : 'Não') . "</li>";
echo "</ul>";

echo "<a href='login.php' class='btn btn-primary w-100 mt-3'>Ir para Login Direto</a>";
echo "</div></body></html>";
?>
