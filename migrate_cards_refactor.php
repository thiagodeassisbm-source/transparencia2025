<?php
// migrate_cards_refactor.php
require 'conexao.php';

// 1. Adiciona a coluna id_prefeitura se não existir (ALTER TABLE causa commit implícito no MySQL)
$stmt_check = $pdo->query("SHOW COLUMNS FROM cards_informativos LIKE 'id_prefeitura'");
if (!$stmt_check->fetch()) {
    $pdo->exec("ALTER TABLE cards_informativos ADD COLUMN id_prefeitura INT AFTER id, ADD INDEX (id_prefeitura)");
    echo "Coluna id_prefeitura adicionada em cards_informativos.<br>";
}

try {
    $pdo->beginTransaction();

    // 2. Preenche id_prefeitura baseado no relacionamento atual com portais
    // Esta consulta é a chave para os cards voltarem a aparecer
    $pdo->exec("
        UPDATE cards_informativos c
        INNER JOIN portais p ON c.id_secao = p.id
        SET c.id_prefeitura = p.id_prefeitura
        WHERE c.id_prefeitura IS NULL OR c.id_prefeitura = 0
    ");
    echo "Sincronização de propriedade (Cards -> Seções) concluída.<br>";

    $pdo->commit();
    echo "<strong>Refatoração concluída com sucesso!</strong> Agora todos os seus cards devem estar visíveis novamente.";
} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo "Erro na migração: " . $e->getMessage();
}
?>
