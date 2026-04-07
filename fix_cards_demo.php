<?php
/**
 * Script de Injeção de Correção v7 - Sincronização de Cards Demo
 */

require_once 'conexao.php';

header('Content-Type: text/plain; charset=utf-8');
echo "Iniciando correção dos dados de demonstração (Cards)...\n\n";

try {
    // Quando adicionamos a coluna id_prefeitura, os cards ficaram com valor 0 por padrão
    // A Prefeitura Principal de demonstração é o ID 1
    // Isso atualiza os cards originais para pertencerem à Prefeitura Principal
    $linhas = $pdo->exec("UPDATE cards_informativos SET id_prefeitura = 1 WHERE id_prefeitura = 0 OR id_prefeitura IS NULL");
    
    echo "✅ $linhas cards de demonstração atualizados para a Prefeitura Principal (ID 1).\n";
    echo "\nAgora o sistema poderá clonar os cards perfeitamente para as novas cidades!\n";

} catch (Exception $e) {
    echo "❌ Erro ao atualizar cards: " . $e->getMessage() . "\n";
}

echo "\nPronto! Para testar, por favor, clique na LIXEIRA para apagar os testes anteriores (zeca, mariana) e crie um novo para ver tudo funcionando com os cards!";
