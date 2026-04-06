<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'conexao.php';
require_once 'vendor/autoload.php';

// Ativar exibição de erros total
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h3>🕵️ LOCALIZADOR DE CONFIGURAÇÕES SMTP</h3>";

// 1. Procurar na config_global (ID 0)
$stmt_global = $pdo->query("SELECT chave, valor FROM config_global WHERE chave LIKE 'smtp_%'");
$config_global = $stmt_global->fetchAll(PDO::FETCH_KEY_PAIR);

// 2. Procurar na configuracoes (ID > 0)
$stmt_pref = $pdo->query("SELECT id_prefeitura, chave, valor FROM configuracoes WHERE chave LIKE 'smtp_%'");
$config_pref_raw = $stmt_pref->fetchAll(PDO::FETCH_ASSOC);

// Agrupar config_pref por prefeitura
$config_pref = [];
foreach ($config_pref_raw as $row) {
    $config_pref[$row['id_prefeitura']][$row['chave']] = $row['valor'];
}

echo "<h4>1. Tabela <code>config_global</code> (Configuração Global/Reserva):</h4>";
if (empty($config_global)) {
    echo "<p style='color: red;'>❌ NÃO ENCONTRADO NADA NESSA TABELA.</p>";
} else {
    echo "<p style='color: green;'>✅ CONFIGURAÇÃO GLOBAL ENCONTRADA!</p>";
    echo "<pre>"; print_r($config_global); echo "</pre>";
    $final_config = $config_global;
}

echo "<h4>2. Tabela <code>configuracoes</code> (Configurações por Prefeitura):</h4>";
if (empty($config_pref)) {
    echo "<p style='color: red;'>❌ NENHUMA PREFEITURA TEM SMTP CONFIGURADO NESTA TABELA.</p>";
} else {
    foreach ($config_pref as $id_pref => $data) {
        echo "<p style='color: green;'>✅ ENCONTRADO PARA PREFEITURA ID: $id_pref</p>";
        echo "<pre>"; print_r($data); echo "</pre>";
        $final_config = $data;
    }
}

if (!isset($final_config)) {
    die("<h2 style='color: red;'>FALHA: Nenhuma credencial SMTP foi encontrada no banco de dados. 
    Por favor, certifique-se de preencher o formulário no painel admin e clicar no botão 'SALVAR'.</h2>");
}

echo "<hr><h3>Iniciando Teste Real com as credenciais acima...</h3>";

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2; 
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host       = $final_config['smtp_host'] ?? '';
    $mail->SMTPAuth   = true;
    $mail->Username   = $final_config['smtp_user'] ?? '';
    $mail->Password   = $final_config['smtp_pass'] ?? '';
    $mail->SMTPSecure = $final_config['smtp_secure'] ?? '';
    $mail->Port       = $final_config['smtp_port'] ?? '';
    $mail->CharSet    = 'UTF-8';

    $from_email = $final_config['smtp_from_email'] ?? ($final_config['smtp_user'] ?? '');
    $from_name = $final_config['smtp_from_name'] ?? 'Teste do Sistema';
    $mail->setFrom($from_email, $from_name);
    $mail->addAddress($from_email, 'Teste SMTP');

    $mail->isHTML(true);
    $mail->Subject = "TESTE DE SISTEMA - LOCALIZADOR E DEPURAÇÃO";
    $mail->Body    = "DEPURAÇÃO COMPLETA!";

    echo "<h4>Log de Conexão detalhado:</h4>";
    $mail->send();
    echo "<h3>🎉 SUCESSO! O e-mail foi enviado com as credenciais localizadas.</h3>";

} catch (Exception $e) {
    echo "<h3>❌ FALHA NO ENVIO</h3>";
    echo "Log de Erro: " . $mail->ErrorInfo;
}

echo "<br><br><a href='admin/configurar_smtp.php'>Voltar para as Configurações</a>";
?>
