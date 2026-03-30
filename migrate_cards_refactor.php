<?php
// migrate_cards_refactor.php
require 'conexao.php';

try {
    $pdo->beginTransaction();

    // 1. Adiciona a coluna id_prefeitura se não existir
    $stmt_check = $pdo->query("SHOW COLUMNS FROM cards_informativos LIKE 'id_prefeitura'");
    if (!$stmt_check->fetch()) {
        $pdo->exec("ALTER TABLE cards_informativos ADD COLUMN id_prefeitura INT AFTER id, ADD INDEX (id_prefeitura)");
        echo "Coluna id_prefeitura adicionada em cards_informativos.<br>";
    }

    // 2. Preenche id_prefeitura baseado no relacionamento atual com portais
    $pdo->exec("
        UPDATE cards_informativos c
        JOIN portais p ON c.id_secao = p.id
        SET c.id_prefeitura = p.id_prefeitura
        WHERE c.id_prefeitura IS NULL OR c.id_prefeitura = 0
    ");
    echo "Cards existentes vinculados a prefeituras via seção.<br>";

    // 3. (Opcional) Tentar vincular cards via categoria se portais falhar e categoria tiver prefeitura?
    // Mas categorias parecem globais neste sistema.
    
    $pdo->commit();
    echo "Refatoração de propriedade de cards concluída com sucesso!";
} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo "Erro na migração: " . $e->getMessage();
}
?>
