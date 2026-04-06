<?php
require_once 'conexao.php';
require_once 'includes/functions_email.php';

// Dados de teste
$email_teste = 'contato@upgyn.com.br'; // Usando o e-mail que você acabou de configurar
$nome_teste = 'Teste do Sistema';
$protocolo_teste = 'TESTE-' . date('Ymd-His');
$id_prefeitura = 0; // Testando a configuração global (Sua última configuração foi global se você salvou no Super Admin)

echo "<h3>Iniciando teste de envio de e-mail...</h3>";
echo "Destinatário: $email_teste<br>";
echo "Protocolo: $protocolo_teste<br><br>";

$resultado = enviar_email_protocolo($pdo, $email_teste, $nome_teste, $protocolo_teste, 'Teste de Configuração', $id_prefeitura);

if ($resultado) {
    echo "<div style='color: green; font-weight: bold;'>🎉 Sucesso! O e-mail foi enviado corretamente via SMTP.</div>";
} else {
    echo "<div style='color: red; font-weight: bold;'>❌ Falha no envio.</div>";
    echo "<p>Verifique se você clicou em 'Salvar' no painel antes de rodar este teste, e se as credenciais (Host, Porta e Senha) estão corretas.</p>";
}
echo "<br><a href='admin/configurar_smtp.php'>Voltar para as Configurações</a>";
?>
