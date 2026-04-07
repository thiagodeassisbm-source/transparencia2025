<?php
/**
 * Script de Verificação e Sincronização Total Demo (V8)
 */

require_once 'conexao.php';

header('Content-Type: text/plain; charset=utf-8');
echo "Sincronizando todas as dependências da Prefeitura Demo (ID 1)...\n\n";

try {
    // 1. Categorias
    $c1 = $pdo->exec("UPDATE categorias SET id_prefeitura = 1 WHERE id_prefeitura = 0 OR id_prefeitura IS NULL");
    echo "Categorias vinculadas à Prefeitura 1: $c1\n";

    // 2. Portais (Seções)
    $c2 = $pdo->exec("UPDATE portais SET id_prefeitura = 1 WHERE id_prefeitura = 0 OR id_prefeitura IS NULL");
    echo "Portais vinculados à Prefeitura 1: $c2\n";

    // 3. Cards Informativos
    $c3 = $pdo->exec("UPDATE cards_informativos SET id_prefeitura = 1 WHERE id_prefeitura = 0 OR id_prefeitura IS NULL");
    echo "Cards vinculados à Prefeitura 1: $c3\n";

    // 4. Vamos inspecionar um Card Demo original para ver se os mapeamentos batem
    echo "\n--- Inspeção de um Card Demo (Origem = 1) ---\n";
    $stmt = $pdo->query("SELECT id, titulo, id_secao, id_categoria FROM cards_informativos WHERE id_prefeitura = 1 LIMIT 5");
    $cards_demo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cards_demo as $card) {
        $tem_sec = $pdo->query("SELECT id FROM portais WHERE id = " . ($card['id_secao']?:0))->fetchColumn();
        $tem_cat = $pdo->query("SELECT id FROM categorias WHERE id = " . ($card['id_categoria']?:0))->fetchColumn();
        
        echo "Card ID {$card['id']} ({$card['titulo']}): \n";
        echo "  - id_secao={$card['id_secao']} (Encontrado na tabela portais? " . ($tem_sec ? 'SIM' : 'NÃO') . ")\n";
        echo "  - id_categoria={$card['id_categoria']} (Encontrado na tabela categorias? " . ($tem_cat ? 'SIM' : 'NÃO') . ")\n";
    }

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\nFavor executar este arquivo se precisar sincronizar a base, ou poste a saída aqui!";
