<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Retorna a configuração SMTP (prefeitura-específica ou fallback global)
 */
function _get_smtp_config(PDO $pdo, int $id_prefeitura): array {
    $config = [];
    if ($id_prefeitura > 0) {
        $stmt = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE id_prefeitura = ? AND chave LIKE 'smtp_%'");
        $stmt->execute([$id_prefeitura]);
        $config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    if (empty($config['smtp_host'])) {
        $stmt = $pdo->query("SELECT chave, valor FROM config_global WHERE chave LIKE 'smtp_%'");
        $config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $config;
}

/**
 * Retorna os textos dos templates (prefeitura-específico com fallback para os defaults)
 */
function _get_email_templates(PDO $pdo, int $id_prefeitura): array {
    $tpl = [];
    if ($id_prefeitura > 0) {
        $stmt = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE id_prefeitura = ? AND chave LIKE 'email_tpl_%'");
        $stmt->execute([$id_prefeitura]);
        $tpl = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    $defaults = [
        'email_tpl_confirmacao_titulo'           => 'Confirmação de Solicitação',
        'email_tpl_confirmacao_assunto'          => 'Protocolo de Atendimento - {SERVICO}',
        'email_tpl_confirmacao_intro'            => 'Sua solicitação no {SERVICO} da Prefeitura foi registrada com sucesso.',
        'email_tpl_confirmacao_label_prot'       => 'Número do Protocolo:',
        'email_tpl_confirmacao_instrucao_titulo' => 'Como acompanhar?',
        'email_tpl_confirmacao_instrucao'        => 'Você pode utilizar este número de protocolo para acompanhar o andamento da sua solicitação diretamente em nosso portal da transparência, na seção correspondente.',
        'email_tpl_confirmacao_rodape'           => 'Esta é uma mensagem automática, por favor não responda a este e-mail.',
        'email_tpl_resposta_titulo'              => 'Sua solicitação foi respondida!',
        'email_tpl_resposta_assunto'             => 'Resposta Disponível - Protocolo {PROTOCOLO}',
        'email_tpl_resposta_intro'               => 'Informamos que a Prefeitura enviou uma resposta oficial para a sua solicitação de {SERVICO}.',
        'email_tpl_resposta_label_prot'          => 'Número do Protocolo:',
        'email_tpl_resposta_instrucao_titulo'    => 'Como visualizar a resposta?',
        'email_tpl_resposta_instrucao'           => 'Para ler o conteúdo completo da resposta, acesse o portal da transparência e utilize a ferramenta de Consulta de Protocolo com o número acima.',
        'email_tpl_resposta_rodape'              => 'Esta é uma mensagem automática, por favor não responda a este e-mail.',
        'email_tpl_resposta_btn'                 => 'Acessar o Portal',
    ];
    foreach ($defaults as $k => $v) {
        if (empty($tpl[$k])) $tpl[$k] = $v;
    }
    return $tpl;
}

/**
 * Substitui variáveis {NOME}, {PROTOCOLO}, {SERVICO} etc. no texto
 */
function _tpl_replace(string $text, array $vars): string {
    foreach ($vars as $k => $v) {
        $text = str_replace('{' . strtoupper($k) . '}', $v, $text);
    }
    return $text;
}

/**
 * Envia e-mail de confirmação de protocolo para o cidadão
 */
function enviar_email_protocolo($pdo, $email_destinatario, $nome_destinatario, $protocolo, $tipo_servico, $id_prefeitura = 0) {
    if (empty($email_destinatario)) return false;

    $config = _get_smtp_config($pdo, (int)$id_prefeitura);
    $host   = $config['smtp_host'] ?? '';
    if (empty($host)) {
        error_log("Tentativa de envio de e-mail sem SMTP configurado (Pref: $id_prefeitura)");
        return false;
    }

    $tpl  = _get_email_templates($pdo, (int)$id_prefeitura);
    $vars = ['nome' => $nome_destinatario, 'protocolo' => $protocolo, 'servico' => strtoupper($tipo_servico)];

    $from_email = $config['smtp_from_email'] ?? ($config['smtp_user'] ?? '');
    $from_name  = $config['smtp_from_name']  ?? 'Prefeitura Municipal';

    $titulo           = _tpl_replace($tpl['email_tpl_confirmacao_titulo'],           $vars);
    $assunto          = _tpl_replace($tpl['email_tpl_confirmacao_assunto'],           $vars);
    $intro            = _tpl_replace($tpl['email_tpl_confirmacao_intro'],             $vars);
    $label_prot       = _tpl_replace($tpl['email_tpl_confirmacao_label_prot'],        $vars);
    $instrucao_titulo = _tpl_replace($tpl['email_tpl_confirmacao_instrucao_titulo'],  $vars);
    $instrucao        = _tpl_replace($tpl['email_tpl_confirmacao_instrucao'],         $vars);
    $rodape           = _tpl_replace($tpl['email_tpl_confirmacao_rodape'],            $vars);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_user']   ?? '';
        $mail->Password   = $config['smtp_pass']   ?? '';
        $mail->SMTPSecure = $config['smtp_secure']  ?? 'tls';
        $mail->Port       = $config['smtp_port']   ?? 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($email_destinatario, $nome_destinatario);
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = "
        <div style='font-family:Arial,sans-serif;color:#333;max-width:600px;margin:0 auto;border:1px solid #eee;border-radius:10px;overflow:hidden;'>
            <div style='background-color:#004a99;color:#ffffff;padding:20px;text-align:center;'>
                <h2 style='margin:0;'>" . htmlspecialchars($titulo) . "</h2>
            </div>
            <div style='padding:30px;'>
                <p>Olá, <strong>" . htmlspecialchars($nome_destinatario) . "</strong>,</p>
                <p>" . nl2br(htmlspecialchars($intro)) . "</p>
                <div style='background-color:#f9f9f9;border-left:5px solid #004a99;padding:20px;margin:25px 0;'>
                    <span style='text-transform:uppercase;font-size:12px;color:#777;font-weight:bold;'>" . htmlspecialchars($label_prot) . "</span><br>
                    <strong style='font-size:24px;color:#004a99;'>" . htmlspecialchars($protocolo) . "</strong>
                </div>
                <p><strong>" . htmlspecialchars($instrucao_titulo) . "</strong></p>
                <p>" . nl2br(htmlspecialchars($instrucao)) . "</p>
                <p style='margin-top:30px;font-size:14px;color:#555;'>" . htmlspecialchars($rodape) . "</p>
            </div>
            <div style='background-color:#f5f5f5;padding:15px;text-align:center;font-size:12px;color:#999;'>
                &copy; " . date('Y') . " " . htmlspecialchars($from_name) . " | Portal da Transparência
            </div>
        </div>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail confirmação: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Envia aviso de que a solicitação foi respondida pela prefeitura
 */
function enviar_email_resposta($pdo, $email_destinatario, $nome_destinatario, $protocolo, $tipo_servico, $id_prefeitura = 0) {
    if (empty($email_destinatario)) return false;

    $config = _get_smtp_config($pdo, (int)$id_prefeitura);
    $host   = $config['smtp_host'] ?? '';
    if (empty($host)) return false;

    $tpl  = _get_email_templates($pdo, (int)$id_prefeitura);
    $vars = ['nome' => $nome_destinatario, 'protocolo' => $protocolo, 'servico' => strtoupper($tipo_servico)];

    $from_email = $config['smtp_from_email'] ?? ($config['smtp_user'] ?? '');
    $from_name  = $config['smtp_from_name']  ?? 'Prefeitura Municipal';

    $titulo           = _tpl_replace($tpl['email_tpl_resposta_titulo'],           $vars);
    $assunto          = _tpl_replace($tpl['email_tpl_resposta_assunto'],           $vars);
    $intro            = _tpl_replace($tpl['email_tpl_resposta_intro'],             $vars);
    $label_prot       = _tpl_replace($tpl['email_tpl_resposta_label_prot'],        $vars);
    $instrucao_titulo = _tpl_replace($tpl['email_tpl_resposta_instrucao_titulo'],  $vars);
    $instrucao        = _tpl_replace($tpl['email_tpl_resposta_instrucao'],         $vars);
    $rodape           = _tpl_replace($tpl['email_tpl_resposta_rodape'],            $vars);
    $btn_text         = _tpl_replace($tpl['email_tpl_resposta_btn'],               $vars);
    $portal_url       = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'prefeitura.gov.br') . '/';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_user']   ?? '';
        $mail->Password   = $config['smtp_pass']   ?? '';
        $mail->SMTPSecure = $config['smtp_secure']  ?? 'tls';
        $mail->Port       = $config['smtp_port']   ?? 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($email_destinatario, $nome_destinatario);
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = "
        <div style='font-family:Arial,sans-serif;color:#333;max-width:600px;margin:0 auto;border:1px solid #eee;border-radius:10px;overflow:hidden;'>
            <div style='background-color:#10b981;color:#ffffff;padding:20px;text-align:center;'>
                <h2 style='margin:0;'>" . htmlspecialchars($titulo) . "</h2>
            </div>
            <div style='padding:30px;'>
                <p>Olá, <strong>" . htmlspecialchars($nome_destinatario) . "</strong>,</p>
                <p>" . nl2br(htmlspecialchars($intro)) . "</p>
                <div style='background-color:#f0fdf4;border-left:5px solid #10b981;padding:20px;margin:25px 0;'>
                    <p style='margin:0;font-size:14px;'>" . htmlspecialchars($label_prot) . "</p>
                    <strong style='font-size:20px;color:#065f46;'>" . htmlspecialchars($protocolo) . "</strong>
                </div>
                <p><strong>" . htmlspecialchars($instrucao_titulo) . "</strong></p>
                <p>" . nl2br(htmlspecialchars($instrucao)) . "</p>
                <div style='text-align:center;margin-top:30px;'>
                    <a href='" . htmlspecialchars($portal_url) . "' style='background-color:#10b981;color:white;padding:12px 25px;text-decoration:none;border-radius:5px;font-weight:bold;'>
                        " . htmlspecialchars($btn_text) . "
                    </a>
                </div>
                <p style='margin-top:30px;font-size:14px;color:#555;'>" . htmlspecialchars($rodape) . "</p>
            </div>
            <div style='background-color:#f5f5f5;padding:15px;text-align:center;font-size:12px;color:#999;'>
                &copy; " . date('Y') . " " . htmlspecialchars($from_name) . " | Portal da Transparência
            </div>
        </div>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail resposta: {$mail->ErrorInfo}");
        return false;
    }
}
