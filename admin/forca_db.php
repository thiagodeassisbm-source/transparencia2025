<?php
require 'conexao.php';
try {
    $rows = $pdo->exec("
        DELETE c1 FROM cards_informativos c1
        INNER JOIN cards_informativos c2 
        WHERE c1.id > c2.id 
          AND c1.id_categoria = c2.id_categoria 
          AND c1.titulo = c2.titulo 
          AND c1.id_prefeitura = c2.id_prefeitura
    ");
    echo "Deletados c1: $rows \n";
    $rows2 = $pdo->exec("
        DELETE FROM cards_informativos 
        WHERE titulo IN ('Teste Lista de Creche', 'Acesso Link', 'Testes', 'Vacinação da Covid-19', 'Informações Institucionais')
          AND id_secao IS NULL
    ");
    echo "Deletados c2: $rows2 \n";
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
