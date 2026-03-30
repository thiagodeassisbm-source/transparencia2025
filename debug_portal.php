<?php
// /debug_portal.php
require_once 'conexao.php';

header('Content-Type: text/plain; charset=utf-8');

echo "--- RELATÓRIO DE DEBUG DO PORTAL ---\n\n";

echo "1. CAMINHOS DO SISTEMA:\n";
echo "Caminho Real do Script: " . realpath(__FILE__) . "\n";
echo "Caminho Real da Raiz: " . realpath(dirname(__FILE__)) . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n\n";

echo "2. VARIÁVEIS DE AMBIENTE:\n";
echo "URL Base Detectada: " . $base_url . "\n";
echo "Domínio Atual: " . $_SERVER['HTTP_HOST'] . "\n\n";

echo "3. CONTEÚDO DO ARQUIVO MENU.PHP:\n";
$menu_path = dirname(__FILE__) . '/menu.php';
if (file_exists($menu_path)) {
    echo "Menu encontrado em: " . $menu_path . "\n";
    echo "Tamanho: " . filesize($menu_path) . " bytes\n";
    echo "--- INÍCIO DO CONTEÚDO ---\n";
    echo file_get_contents($menu_path);
    echo "\n--- FIM DO CONTEÚDO ---\n";
} else {
    echo "ERRO: O arquivo menu.php NÃO foi encontrado na pasta raiz!\n";
}

echo "4. ESTADO DO GIT NO SERVIDOR:\n";
exec('git log -n 1 --pretty=format:"%h - %s (%ad)" --date=short', $git_log);
echo ($git_log[0] ?? 'Comando git não disponível ou erro na execução') . "\n\n";

echo "5. CONEXÃO COM O BANCO:\n";
try {
    $stmt = $pdo->query("SELECT nome FROM prefeituras LIMIT 1");
    $pref = $stmt->fetch();
    echo "Conexão OK. Primeira prefeitura: " . ($pref['nome'] ?? 'Nenhuma encontrada') . "\n";
} catch (Exception $e) {
    echo "ERRO DE BANCO: " . $e->getMessage() . "\n";
}
echo "\nNota: Se o commit acima não for o seu último, o servidor não puxou as mudanças.";
?>
