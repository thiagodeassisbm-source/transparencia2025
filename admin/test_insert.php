<?php
// /admin/test_insert.php
require_once '../conexao.php';

echo "<h2>Test Insert: sic_solicitacoes</h2>";

try {
    $stmt = $pdo->prepare("INSERT INTO sic_solicitacoes (protocolo, id_prefeitura, nome_solicitante, descricao_pedido, status) VALUES ('TEST123', 1, 'Teste Sistema', 'Teste de insercao', 'Recebido')");
    $stmt->execute();
    echo "<p style='color:green'>Sucesso no INSERT!</p>";
    
    // Limpa o teste
    $pdo->exec("DELETE FROM sic_solicitacoes WHERE protocolo = 'TEST123'");
    echo "<p>Registro de teste removido.</p>";

} catch (Exception $e) {
    echo "<h3 style='color:red'>Erro no INSERT: " . $e->getMessage() . "</h3>";
}
