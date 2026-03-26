<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Aumenta o tempo máximo de execução para importações longas
set_time_limit(300);
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Erro desconhecido ou nenhum dado recebido.'];

// Verifica se o método é POST e se a variável 'itens' foi recebida.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['itens'])) {
    try {
        // Validação de segurança da sessão do usuário
        if ($_SESSION['admin_user_perfil'] !== 'admin') {
            throw new Exception("Acesso negado.");
        }

        // Captura dos dados do formulário
        $id_portal = filter_input(INPUT_POST, 'id_portal', FILTER_VALIDATE_INT);
        $itens_para_importar = $_POST['itens']; // Esta variável agora receberá o array numérico correto
        $anexos_enviados = $_FILES['anexos'] ?? [];
        $metadados = $_POST['metadados'] ?? [];
        $tipo_dados = $_POST['tipo_dados'] ?? null;
        
        // Captura dos metadados da publicação
        $exercicio = $metadados['exercicio'] ?? null;
        $unidade_gestora = $metadados['unidade_gestora'] ?? null;
        $periodicidade = $metadados['periodicidade'] ?? null;
        $mes = $metadados['mes'] ?? null;
        $id_tipo_documento = $metadados['id_tipo_documento'] ?? null;
        $id_classificacao = !empty($metadados['id_classificacao']) ? $metadados['id_classificacao'] : null;

        // Validações essenciais para a continuidade do script
        if (!$id_portal) {
            throw new Exception("Seção de destino não especificada.");
        }
        if (empty($itens_para_importar) || !is_array($itens_para_importar)) {
            throw new Exception("Nenhum item válido para importação foi encontrado.");
        }

        // Busca os campos do portal de destino para mapeamento
        $stmt_campos = $pdo->prepare("SELECT nome_campo, id FROM campos_portal WHERE id_portal = ?");
        $stmt_campos->execute([$id_portal]);
        $mapa_campos_bd = $stmt_campos->fetchAll(PDO::FETCH_KEY_PAIR);

        if (empty($mapa_campos_bd)) {
            throw new Exception("A seção de destino não possui campos cadastrados. Verifique as configurações do portal.");
        }
        
        // Mapeamento das tags do XML para os nomes dos campos no banco de dados
        $mapeamento_xml_bd = [];
        if ($tipo_dados === 'contratos') {
            $mapeamento_xml_bd = [
                'numero_contrato'      => 'Número do Contrato',
                'data_assinatura'      => 'Data da Assinatura',
                'nome_contratado'      => 'Nome do Contratado',
                'cnpj_contratado'      => 'CNPJ do Contratado',
                'objeto_contrato'      => 'Objeto do Contrato',
                'valor_total'          => 'Valor Total',
                'data_inicio_vigencia' => 'Início da Vigência',
                'data_fim_vigencia'    => 'Fim da Vigência'
            ];
        } elseif ($tipo_dados === 'folha_pagamento') {
            $mapeamento_xml_bd = [
                'matricula'        => 'Matrícula',
                'nome_servidor'    => 'Nome do Servidor',
                'cargo'            => 'Cargo',
                'tipo_vinculo'     => 'Vínculo',
                'salario_base'     => 'Salário Base',
                'outras_vantagens' => 'Outras Vantagens',
                'salario_bruto'    => 'Salário Bruto'
            ];
        } else {
            throw new Exception("Tipo de dados '" . htmlspecialchars($tipo_dados) . "' não é suportado.");
        }

        $sucessos = 0;
        $falhas = 0;
        $erros_detalhados = [];

        // Loop principal que agora irá iterar sobre o array de itens corretamente
        foreach ($itens_para_importar as $index => $item) {
            try {
                $pdo->beginTransaction();
                
                // 1. Insere o registro principal (a "publicação")
                $stmt_reg = $pdo->prepare("INSERT INTO registros (id_portal, exercicio, mes, periodicidade, unidade_gestora, id_tipo_documento, id_classificacao) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt_reg->execute([$id_portal, $exercicio, $mes, $periodicidade, $unidade_gestora, $id_tipo_documento, $id_classificacao]);
                $id_registro = $pdo->lastInsertId();

                // 2. Prepara a inserção dos valores individuais do item
                $stmt_val = $pdo->prepare("INSERT INTO valores_registros (id_registro, id_campo, valor) VALUES (?, ?, ?)");
                
                // 3. Itera sobre o mapeamento e insere cada valor do item no banco
                foreach ($mapeamento_xml_bd as $tag_xml => $nome_campo_no_bd) {
                    // Verifica se o campo de destino existe no portal configurado
                    if (isset($mapa_campos_bd[$nome_campo_no_bd])) {
                        $id_campo = $mapa_campos_bd[$nome_campo_no_bd];
                        // Verifica se o dado veio do formulário
                        if (isset($item[$tag_xml]) && $item[$tag_xml] !== '') {
                            $stmt_val->execute([$id_registro, $id_campo, trim($item[$tag_xml])]);
                        }
                    }
                }

                // 4. Processa o anexo, se houver um para este item
                if (isset($anexos_enviados['name'][$index]) && $anexos_enviados['error'][$index] === UPLOAD_ERR_OK) {
                    $upload_dir = '../uploads/controladoria/';
                    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
                    
                    $nome_anexo = uniqid() . '-' . basename($anexos_enviados['name'][$index]);
                    $caminho_final = $upload_dir . $nome_anexo;

                    if (move_uploaded_file($anexos_enviados['tmp_name'][$index], $caminho_final)) {
                        // Insere o caminho do anexo no banco, se o campo 'Anexo' existir
                        if (isset($mapa_campos_bd['Anexo'])) {
                            $stmt_val->execute([$id_registro, $mapa_campos_bd['Anexo'], $caminho_final]);
                        }
                    }
                }
                
                $pdo->commit();
                $sucessos++;
            } catch (Exception $e_item) {
                $pdo->rollBack();
                $falhas++;
                $erros_detalhados[] = "Item " . ($index + 1) . ": " . $e_item->getMessage();
            }
        }

        // Prepara a resposta final de sucesso para o JavaScript
        $response = [
            'success' => true,
            'message' => 'Importação concluída.',
            'total' => count($itens_para_importar),
            'sucessos' => $sucessos,
            'falhas' => $falhas,
            'erros' => $erros_detalhados,
            'redirect_url' => 'ver_lancamentos.php?portal_id=' . $id_portal
        ];

    } catch (Exception $e) {
        // Captura qualquer erro geral e prepara uma resposta de falha
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
}

// Envia a resposta como JSON e termina o script
echo json_encode($response);
exit();
