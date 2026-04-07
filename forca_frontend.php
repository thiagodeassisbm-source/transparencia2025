<?php
/**
 * Script Atualizador Automático do Frontend v1
 */

header('Content-Type: text/plain; charset=utf-8');
echo "Iniciando correção automática dos links e rotas frontend...\n\n";

// 1. Array de Diffs para categoria.php
$cat_file = __DIR__ . '/categoria.php';
$cat_content = file_get_contents($cat_file);
if (strpos($cat_content, 'AND id_prefeitura') === false) {
    $cat_content = str_replace(
        'SELECT id FROM categorias WHERE slug = ? LIMIT 1',
        'SELECT id FROM categorias WHERE slug = ? AND id_prefeitura = ? LIMIT 1',
        $cat_content
    );
    $cat_content = str_replace(
        '$stmt_slug->execute([$categoria_slug]);',
        '$stmt_slug->execute([$categoria_slug, $id_prefeitura_ativa]);',
        $cat_content
    );
    if (file_put_contents($cat_file, $cat_content)) {
        echo "✅ categoria.php atualizado! O filtro de rotas duplicadas foi corrigido.\n";
    } else {
        echo "❌ Erro de permissão ao salvar categoria.php\n";
    }
} else {
    echo "✅ categoria.php já possui a correção de rotas.\n";
}

// 2. Array de Diffs para index.php
$index_file = __DIR__ . '/index.php';
$index_content = file_get_contents($index_file);
if (strpos($index_content, 'AND id_prefeitura') === false) {
    $index_content = str_replace(
        'SELECT id FROM categorias WHERE slug = ? LIMIT 1',
        'SELECT id FROM categorias WHERE slug = ? AND id_prefeitura = ? LIMIT 1',
        $index_content
    );
    $index_content = str_replace(
        '$stmt_slug->execute([$categoria_slug]);',
        '$stmt_slug->execute([$categoria_slug, $id_prefeitura_ativa]);',
        $index_content
    );
    // Também corrige bug de fallback para slug vazio para não cair em '#'
    $index_content = preg_replace(
        '/\$link = \'#\';/m',
        '$link = \'#erro-de-url-vazia\';',
        $index_content
    );
    
    if (file_put_contents($index_file, $index_content)) {
        echo "✅ index.php atualizado! Links da homepage adaptados para multitenant.\n";
    } else {
        echo "❌ Erro de permissão ao salvar index.php\n";
    }
} else {
    echo "✅ index.php já possui a correção de rotas.\n";
}

echo "\n✨ CORREÇÃO E ATUALIZAÇÃO DO SISTEMA CONCLUÍDAS!\n";
echo "Por favor, visite novamente as páginas da prefeitura Miguel e verifique se os cards aparecem.";
