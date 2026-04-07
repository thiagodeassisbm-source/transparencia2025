<?php
/**
 * Script Atualizador de funções do motor de clone v9 (Agora copia os registros internos e PDFs)
 */

header('Content-Type: text/plain; charset=utf-8');
echo "Iniciando atualização do Motor de Clone...\n\n";

$file = __DIR__ . '/admin/functions_demo.php';
$content = file_get_contents($file);

if (strpos($content, '--- 5. Clona Registros e Valores ---') === false) {
    // Primeiro, precisamos capturar o mapeamento dos campos que são inseridos.
    // Vamos procurar onde `$ins_c->execute(` acontece.
    
    $find = "\$ins_c->execute([\$new_p_id, \$c['nome_campo'], \$c['tipo_campo'], \$c['opcoes_campo'], \$c['obrigatorio'], \$c['pesquisavel'], \$c['detalhes_apenas'], \$c['ordem']]);";
    
    $replace = "\$ins_c->execute([\$new_p_id, \$c['nome_campo'], \$c['tipo_campo'], \$c['opcoes_campo'], \$c['obrigatorio'], \$c['pesquisavel'], \$c['detalhes_apenas'], \$c['ordem']]);\n                \$map_campos_global[\$c['id']] = \$pdo->lastInsertId();";

    // Adiciona inicialização do mapa de campos global no início do portais loop
    $find2 = "\$map_portais = []; // old_id => new_id";
    $replace2 = "\$map_portais = [];\n        \$map_campos_global = [];";

    $content = str_replace($find, $replace, $content);
    $content = str_replace($find2, $replace2, $content);

    // Agora insere o Bloco 5 no final da função (antes do commit)
    $find_commit = "if (\$should_manage_transaction) {\n            \$pdo->commit();";
    
    $bloco5 = <<<'PHP'
        // --- 5. Clona Registros e Valores (Os dados internos e formulários) ---
        $cols_reg = demo_colunas_tabela($pdo, 'registros');
        $has_pref_reg = in_array('id_prefeitura', $cols_reg);

        foreach ($map_portais as $old_p_id => $new_p_id) {
            $stmt_reg = $pdo->prepare("SELECT * FROM registros WHERE id_portal = ?");
            $stmt_reg->execute([$old_p_id]);
            
            $ins_reg_sql = $has_pref_reg 
                ? "INSERT INTO registros (id_portal, id_responsavel, data_inclusao, id_prefeitura) VALUES (?, ?, ?, ?)"
                : "INSERT INTO registros (id_portal, id_responsavel, data_inclusao) VALUES (?, ?, ?)";
            $ins_reg = $pdo->prepare($ins_reg_sql);
            
            $ins_val = $pdo->prepare("INSERT INTO valores_registros (id_registro, id_campo, valor) VALUES (?, ?, ?)");
            $stmt_val = $pdo->prepare("SELECT id_campo, valor FROM valores_registros WHERE id_registro = ?");
            
            foreach ($stmt_reg->fetchAll() as $r) {
                if ($has_pref_reg) {
                    $ins_reg->execute([$new_p_id, $r['id_responsavel'], $r['data_inclusao'], $id_destino]);
                } else {
                    $ins_reg->execute([$new_p_id, $r['id_responsavel'], $r['data_inclusao']]);
                }
                $new_r_id = $pdo->lastInsertId();

                $stmt_val->execute([$r['id']]);
                foreach ($stmt_val->fetchAll() as $v) {
                    $new_c_id = $map_campos_global[$v['id_campo']] ?? null;
                    if ($new_c_id !== null) {
                        $ins_val->execute([$new_r_id, $new_c_id, $v['valor']]);
                    }
                }
            }
        }
        
        PHP;

    $content = str_replace($find_commit, $bloco5 . $find_commit, $content);
    
    if (file_put_contents($file, $content)) {
        echo "✅ functions_demo.php atualizado! O bloco de clonagem de REGISTROS E PDFS foi injetado.\n";
    } else {
        echo "❌ Erro ao salvar!\n";
    }
} else {
    echo "✅ Bloco de registros já existente em functions_demo.php\n";
}

echo "Procedimento Concluído! Para testar, CRIE uma NOVA Prefeitura e verifique se as tabelas como Folha de Pagamento vêm populadas.";
