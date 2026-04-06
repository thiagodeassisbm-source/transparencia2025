<?php
/**
 * Script de Reparo de Banco de Dados - Transparência 2026
 * Finalidade: Garantir que a tabela cards_informativos possua as colunas necessárias para o funcionamento do SaaS.
 */
require_once 'conexao.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Iniciando verificação do banco de dados...\n\n";

try {
    // 1. Verificar colunas da tabela cards_informativos
    $stmt = $pdo->query("SHOW COLUMNS FROM cards_informativos");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tabela 'cards_informativos' colunas encontradas: " . implode(', ', $cols) . "\n";

    // a) Verificar caminho_icone vs icone
    if (!in_array('caminho_icone', $cols)) {
        if (in_array('icone', $cols)) {
            echo "-> Renomeando coluna 'icone' para 'caminho_icone'...\n";
            $pdo->exec("ALTER TABLE cards_informativos CHANGE icone caminho_icone VARCHAR(255) NOT NULL");
            echo "✅ Coluna renomeada com sucesso.\n";
        } else {
            echo "-> Criando coluna 'caminho_icone'...\n";
            $pdo->exec("ALTER TABLE cards_informativos ADD COLUMN caminho_icone VARCHAR(255) NOT NULL AFTER subtitulo");
            echo "✅ Coluna criada com sucesso.\n";
        }
    } else {
        echo "✅ Coluna 'caminho_icone' já existe.\n";
    }

    // b) Verificar tipo_icone
    if (!in_array('tipo_icone', $cols)) {
        echo "-> Criando coluna 'tipo_icone'...\n";
        $pdo->exec("ALTER TABLE cards_informativos ADD COLUMN tipo_icone ENUM('imagem', 'bootstrap') DEFAULT 'imagem' AFTER caminho_icone");
        echo "✅ Coluna 'tipo_icone' criada com sucesso.\n";
    } else {
        echo "✅ Coluna 'tipo_icone' já existe.\n";
    }

    // c) Verificar id_prefeitura
    if (!in_array('id_prefeitura', $cols)) {
        echo "-> Criando coluna 'id_prefeitura'...\n";
        $pdo->exec("ALTER TABLE cards_informativos ADD COLUMN id_prefeitura INT DEFAULT 0 AFTER id");
        $pdo->exec("CREATE INDEX idx_pref_cards ON cards_informativos(id_prefeitura)");
        echo "✅ Coluna 'id_prefeitura' criada com sucesso.\n";
        
        // Tentar preencher id_prefeitura baseando-se na seção
        echo "-> Tentando associar prefeituras aos cards existentes...\n";
        $pdo->exec("UPDATE cards_informativos c INNER JOIN portais p ON c.id_secao = p.id SET c.id_prefeitura = p.id_prefeitura WHERE c.id_prefeitura = 0");
        echo "✅ Sincronização inicial concluída.\n";
    } else {
        echo "✅ Coluna 'id_prefeitura' já existe.\n";
    }

    echo "\n--- VERIFICAÇÃO DE OUTRAS TABELAS CRÍTICAS ---\n";
    $tabelas_criticas = ['categorias', 'configuracoes', 'ouvidoria_manifestacoes', 'sic_solicitacoes', 'secretarias', 'cargos', 'agentes_politicos'];
    foreach ($tabelas_criticas as $t) {
        $st_check = $pdo->prepare("SHOW TABLES LIKE ?");
        $st_check->execute([$t]);
        if ($st_check->fetch()) {
            $st_cols = $pdo->query("SHOW COLUMNS FROM $t");
            $c_list = $st_cols->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('id_prefeitura', $c_list)) {
                echo "-> Reparando tabela '$t' (adicionando id_prefeitura)...\n";
                $pdo->exec("ALTER TABLE $t ADD COLUMN id_prefeitura INT DEFAULT 0 AFTER id");
                echo "✅ Tabela '$t' reparada.\n";
            } else {
                echo "✅ Tabela '$t' está OK.\n";
            }
        }
    }

    echo "\nPROCESSO CONCLUÍDO COM SUCESSO!\n";
    echo "O site deve voltar a carregar normalmente agora.";

} catch (Exception $e) {
    echo "\n❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Isso pode ocorrer se as permissões do usuário do banco de dados forem insuficientes para ALTER TABLE.";
}
