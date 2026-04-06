<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Envia e-mail de confirmação de protocolo para o cidadão
 * @param PDO $pdo
 * @param string $email_destinatario
 * @param string $nome_destinatario
 * @param string $protocolo
 * @param string $tipo_servico
 * @param int $id_prefeitura ID da prefeitura para buscar SMTP específico
 */
function enviar_email_protocolo($pdo, $email_destinatario, $nome_destinatario, $protocolo, $tipo_servico, $id_prefeitura = 0) {
    if (empty($email_destinatario)) return false;

    // 1. TENTA BUSCAR CONFIGURAÇÃO ESPECÍFICA DA PREFEITURA
    $config = [];
    if ($id_prefeitura > 0) {
        $stmt = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE id_prefeitura = ? AND chave LIKE 'smtp_%'");
        $stmt->execute([$id_prefeitura]);
        $config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // 2. SE NÃO ENCONTROU HOST NA PREFEITURA, BUSCA NO GLOBAL (FALLBACK)
    if (empty($config['smtp_host'])) {
        $stmt_global = $pdo->query("SELECT chave, valor FROM config_global WHERE chave LIKE 'smtp_%'");
        $config = $stmt_global->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    $host = $config['smtp_host'] ?? '';
    if (empty($host)) {
        error_log("Tentativa de envio de e-mail sem SMTP configurado (Pref: $id_prefeitura)");
        return false; 
    }

    $port = $config['smtp_port'] ?? 587;
    $user = $config['smtp_user'] ?? '';
    $pass = $config['smtp_pass'] ?? '';
    $secure = $config['smtp_secure'] ?? 'tls';
    $from_email = $config['smtp_from_email'] ?? $user;
    $from_name = $config['smtp_from_name'] ?? 'Prefeitura Municipal';

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $user;
        $mail->Password   = $pass;
        $mail->SMTPSecure = $secure;
        $mail->Port       = $port;
        $mail->CharSet    = 'UTF-8';

        // Recipients
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($email_destinatario, $nome_destinatario);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Protocolo de Atendimento - " . strtoupper($tipo_servico);
        
        $body = "
        <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 10px; overflow: hidden;'>
            <div style='background-color: #004a99; color: #ffffff; padding: 20px; text-align: center;'>
                <h2 style='margin: 0;'>Confirmação de Solicitação</h2>
            </div>
            <div style='padding: 30px;'>
                <p>Olá, <strong>{$nome_destinatario}</strong>,</p>
                <p>Sua solicitação no <strong>" . strtoupper($tipo_servico) . "</strong> da Prefeitura foi registrada com sucesso.</p>
                
                <div style='background-color: #f9f9f9; border-left: 5px solid #004a99; padding: 20px; margin: 25px 0;'>
                    <span style='text-transform: uppercase; font-size: 12px; color: #777; font-weight: bold;'>Número do Protocolo:</span><br>
                    <strong style='font-size: 24px; color: #004a99;'>{$protocolo}</strong>
                </div>

                <p><strong>Como acompanhar?</strong></p>
                <p>Você pode utilizar este número de protocolo para acompanhar o andamento da sua solicitação diretamente em nosso portal da transparência, na seção correspondente.</p>
                
                <p style='margin-top: 30px; font-size: 14px; color: #555;'>Esta é uma mensagem automática, por favor não responda a este e-mail.</p>
            </div>
            <div style='background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #999;'>
                &copy; " . date('Y') . " " . htmlspecialchars($from_name) . " | Desenvolvido por UpGyn
            </div>
        </div>";

        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
        return false;
    }
}
