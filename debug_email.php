<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'conexao.php';
require_once 'vendor/autoload.php';

// Ativar exibição de erros total
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Buscar a configuração Global
$stmt = $pdo->query("SELECT chave, valor FROM config_global WHERE chave LIKE 'smtp_%'");
$config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

if (empty($config)) {
    die("<h3>ERRO: Nenhuma configuração global encontrada no banco.</h3>");
}

echo "<h3>MODO DEPURAÇÃO SMTP (FULL DEBUG)</h3>";
echo "<p>Testando com as configurações salvas em <strong>config_global</strong>:</p>";
echo "<pre>";
print_r([
    'host' => $config['smtp_host'] ?? 'NÃO DEFINIDO',
    'port' => $config['smtp_port'] ?? 'NÃO DEFINIDO',
    'user' => $config['smtp_user'] ?? 'NÃO DEFINIDO',
    'pass' => '(OCULTA)',
    'secure' => $config['smtp_secure'] ?? 'NÃO DEFINIDO'
]);
echo "</pre>";

$mail = new PHPMailer(true);

try {
    // ATIVA O DEBUG COMPLETO (Vai imprimir todo o log da conversa com o servidor)
    $mail->SMTPDebug = 2; 
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_user'];
    $mail->Password   = $config['smtp_pass'];
    $mail->SMTPSecure = $config['smtp_secure'];
    $mail->Port       = $config['smtp_port'];
    $mail->CharSet    = 'UTF-8';

    // Recipients
    $from_email = $config['smtp_from_email'] ?? $config['smtp_user'];
    $from_name = $config['smtp_from_name'] ?? 'Teste do Sistema';
    $mail->setFrom($from_email, $from_name);
    $mail->addAddress($from_email, 'Teste SMTP Depurador');

    // Content
    $mail->isHTML(true);
    $mail->Subject = "TESTE DE SISTEMA - DEPURAÇÃO ATIVADA";
    $mail->Body    = "Se você está lendo isso, a conexão SMTP foi bem-sucedida!";

    echo "<h4>Log de Conexão:</h4>";
    $mail->send();
    echo "<h3>🎉 SUCESSO! O e-mail foi enviado.</h3>";

} catch (Exception $e) {
    echo "<h3>❌ FALHA CRÍTICA NO ENVIO</h3>";
    echo "Erro do PHPMailer: " . $mail->ErrorInfo;
}

echo "<br><br><a href='admin/configurar_smtp.php'>Voltar para as Configurações</a>";
?>
