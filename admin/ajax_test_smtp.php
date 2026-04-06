<?php
require_once 'auth_check.php';
require_once '../conexao.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
    exit;
}

$host = $_POST['smtp_host'] ?? '';
$port = $_POST['smtp_port'] ?? '';
$user = $_POST['smtp_user'] ?? '';
$pass = $_POST['smtp_pass'] ?? '';
$secure = $_POST['smtp_secure'] ?? '';
$from_email = $_POST['smtp_from_email'] ?? $user;
$from_name = $_POST['smtp_from_name'] ?? 'Teste do Sistema';

if (empty($host) || empty($user) || empty($pass)) {
    echo json_encode(['status' => 'error', 'message' => 'Preencha Servidor, Usuário e Senha para testar.']);
    exit;
}

$mail = new PHPMailer(true);

try {
    // Configurações do Servidor
    $mail->isSMTP();
    $mail->Host       = $host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $user;
    $mail->Password   = $pass;
    $mail->SMTPSecure = $secure;
    $mail->Port       = $port;
    $mail->Timeout    = 10; // Timeout curto para o teste
    $mail->CharSet    = 'UTF-8';

    // Remetente e Destinatário (envia para si mesmo)
    $mail->setFrom($from_email, $from_name);
    $mail->addAddress($user, 'Teste SMTP');

    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = 'Teste de Conexão SMTP - ' . date('d/m/Y H:i');
    $mail->Body    = 'Se você recebeu este e-mail, as configurações de e-mail do seu portal estão configuradas corretamente!';

    $mail->send();

    echo json_encode([
        'status' => 'success', 
        'message' => '🎉 Sucesso! Conexão estabelecida e e-mail de teste enviado para <strong>' . $user . '</strong>.'
    ]);

} catch (Exception $e) {
    $error = $mail->ErrorInfo;
    $suggestion = "Verifique as credenciais.";

    // Lógica de Sugestões baseada no erro
    if (strpos($error, 'Could not connect to SMTP host') !== false) {
        if ($port == '465') {
            $suggestion = "Tente mudar a Porta para <strong>587</strong> e a Segurança para <strong>TLS</strong>.";
        } elseif ($port == '587') {
            $suggestion = "Tente mudar a Porta para <strong>465</strong> e a Segurança para <strong>SSL</strong>.";
        } else {
            $suggestion = "A porta informada pode estar incorreta. Use 587 (TLS) ou 465 (SSL).";
        }
    } elseif (strpos($error, 'Authentication failed') !== false || strpos($error, 'Password') !== false) {
        $suggestion = "Usuário ou Senha incorretos. Lembre-se que para Gmail/Outlook você precisa usar uma <strong>Senha de Aplicativo</strong>.";
    } elseif (strpos($error, 'TLS') !== false || strpos($error, 'SSL') !== false) {
        $suggestion = "Erro de protocolo de segurança. Tente inverter entre SSL e TLS.";
    }

    echo json_encode([
        'status' => 'error', 
        'message' => '❌ Falha de Conexão.',
        'debug' => $error,
        'suggestion' => $suggestion
    ]);
}
