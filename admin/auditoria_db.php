<?php
// /admin/auditoria_db.php
require_once '../conexao.php';

echo "<h2>Auditoria e Reparo de Banco de Dados</h2>";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $stmt_cols = $pdo->query("SHOW COLUMNS FROM $table");
        $cols = $stmt_cols->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('id_prefeitura', $cols)) {
            // Tabelas críticas que PRECISAM da coluna
            $criticas = ['sic_solicitacoes', 'ouvidoria_manifestacoes', 'categorias', 'secretarias', 'cargos', 'agentes_politicos', 'configuracoes'];
            
            if (in_array($table, $criticas)) {
                echo "<p style='color:orange'>Reparando tabela <b>$table</b>...</p>";
                try {
                    $pdo->exec("ALTER TABLE $table ADD COLUMN id_prefeitura INT DEFAULT 0 AFTER id");
                    echo "<p style='color:green'>-> Tabela $table reparada com sucesso.</p>";
                } catch (Exception $ex) {
                    echo "<p style='color:red'>-> Falha ao reparar $table: " . $ex->getMessage() . "</p>";
                }
            }
        }
    }

    echo "<h3>Auditoria finalizada.</h3>";

} catch (Exception $e) {
    echo "<h3 style='color:red'>Erro na auditoria: " . $e->getMessage() . "</h3>";
}
