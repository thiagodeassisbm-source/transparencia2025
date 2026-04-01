<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';
require_once __DIR__ . '/includes/xml_import_helpers.php';
set_time_limit(300); // Aumenta o limite de tempo de execução para 5 minutos

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($_SESSION['admin_user_perfil'] !== 'admin') {
            throw new Exception('Acesso negado.');
        }

        $pref_id_sess = (int) ($_SESSION['id_prefeitura'] ?? 0);

        // Carrega os dados recebidos do formulário
        $id_portal = filter_input(INPUT_POST, 'id_portal', FILTER_VALIDATE_INT);
        $metadados = unserialize(base64_decode($_POST['metadados_serializados']));
        $mapeamento = unserialize(base64_decode($_POST['mapeamento_serializado']));
        $xml = simplexml_load_string(base64_decode($_POST['xml_content']));
        $anexos = $_FILES['anexos'] ?? [];
        $tag_registro_importacao = isset($_POST['tag_registro_importacao'])
            ? xml_import_sanitize_tag((string) $_POST['tag_registro_importacao'])
            : '';

        if (!$id_portal || !$metadados || !$mapeamento || !$xml) {
            throw new Exception('Dados essenciais para a importação estão faltando.');
        }

        if ($pref_id_sess <= 0) {
            throw new Exception('Contexto de prefeitura não identificado.');
        }

        $stmt_pref = $pdo->prepare('SELECT id FROM portais WHERE id = ? AND id_prefeitura = ?');
        $stmt_pref->execute([$id_portal, $pref_id_sess]);
        if (!$stmt_pref->fetch()) {
            throw new Exception('A seção não pertence à prefeitura atual ou foi alterada durante a importação.');
        }

        $stmt_campos_ok = $pdo->prepare('SELECT id FROM campos_portal WHERE id_portal = ?');
        $stmt_campos_ok->execute([$id_portal]);
        $campos_permitidos = array_map('intval', $stmt_campos_ok->fetchAll(PDO::FETCH_COLUMN));
        foreach ($mapeamento as $tag => $id_campo) {
            if ($id_campo === '' || $id_campo === null) {
                continue;
            }
            if (!in_array((int) $id_campo, $campos_permitidos, true)) {
                throw new Exception('Mapeamento inválido para esta seção.');
            }
        }

        // Primeiro campo do tipo anexo desta seção (Download, Anexo, etc. — mesmo nome da listagem)
        $stmt_campo_anexo = $pdo->prepare(
            'SELECT id FROM campos_portal WHERE id_portal = ? AND tipo_campo = ? ORDER BY ordem ASC, id ASC LIMIT 1'
        );
        $stmt_campo_anexo->execute([$id_portal, 'anexo']);
        $id_campo_anexo = $stmt_campo_anexo->fetchColumn();


        // --- INÍCIO DO NOVO CÓDIGO DINÂMICO ---
        $stmt_tags = $pdo->query('SELECT tag_registro FROM tipos_xml WHERE ativo = 1 ORDER BY id ASC');
        $tags_validas = $stmt_tags->fetchAll(PDO::FETCH_COLUMN);

        $dados_xml = xml_import_listar_registros(
            $xml,
            $tags_validas,
            $tag_registro_importacao !== '' ? $tag_registro_importacao : null
        );
        // --- FIM DO NOVO CÓDIGO DINÂMICO ---

        
        $sucessos = 0;

        // Prepara as consultas de inserção no banco
        $stmt_reg = $pdo->prepare("INSERT INTO registros (id_portal, exercicio, mes, periodicidade, unidade_gestora, id_tipo_documento, id_classificacao) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_val = $pdo->prepare("INSERT INTO valores_registros (id_registro, id_campo, valor) VALUES (?, ?, ?)");

        // Inicia o loop para processar cada linha do XML
        foreach ($dados_xml as $index => $item_xml) {
            $pdo->beginTransaction();
            
            // Insere o registro principal com os metadados
            $stmt_reg->execute([
                $id_portal, $metadados['exercicio'], $metadados['mes'], $metadados['periodicidade'],
                $metadados['unidade_gestora'], $metadados['id_tipo_documento'],
                !empty($metadados['id_classificacao']) ? $metadados['id_classificacao'] : null
            ]);
            $id_registro = $pdo->lastInsertId();

            // Insere os valores de cada campo mapeado
            foreach($mapeamento as $tag => $id_campo) {
                if (!empty($id_campo) && isset($item_xml->{$tag})) {
                    $valor = (string) $item_xml->{$tag};
                    $stmt_val->execute([$id_registro, $id_campo, $valor]);
                }
            }

            // Anexo por linha (Passo 3): mesmo formato de caminho que lancar_dados / editar_lancamento
            if (
                $id_campo_anexo
                && !empty($anexos['name'][$index])
                && isset($anexos['error'][$index])
                && (int) $anexos['error'][$index] === UPLOAD_ERR_OK
                && !empty($anexos['tmp_name'][$index])
            ) {
                $upload_dir = '../uploads/importados/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $nome_anexo = uniqid() . '-' . basename((string) $anexos['name'][$index]);
                $caminho_final = $upload_dir . $nome_anexo;

                if (move_uploaded_file($anexos['tmp_name'][$index], $caminho_final)) {
                    $valor_db = str_replace('../', '', $caminho_final);
                    $stmt_val->execute([$id_registro, $id_campo_anexo, $valor_db]);
                }
            }
            
            $pdo->commit();
            $sucessos++;
        }

        $stmt_nome_sec = $pdo->prepare('SELECT nome FROM portais WHERE id = ?');
        $stmt_nome_sec->execute([$id_portal]);
        $nome_secao_import = $stmt_nome_sec->fetchColumn() ?: ('ID ' . $id_portal);
        registrar_log(
            $pdo,
            'ADIÇÃO',
            modulo_log_lancamento($pdo, $id_portal),
            "Importação XML: $sucessos lançamento(s) (seção \"" . $nome_secao_import . "\", portal_id $id_portal)."
        );

        // Redireciona para a página de lançamentos com mensagem de sucesso
        $_SESSION['mensagem_sucesso'] = "$sucessos registro(s) importado(s) com sucesso!";
        header("Location: ver_lancamentos.php?portal_id=" . $id_portal);
        exit;

    } catch (Exception $e) {
        // Em caso de erro, desfaz a transação e exibe a mensagem
        if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
        $_SESSION['mensagem_erro'] = "ERRO NA IMPORTAÇÃO: " . $e->getMessage();
        header("Location: importar_xml.php");
        exit;
    }
} else {
    // Se o arquivo for acessado diretamente sem método POST
    header("Location: importar_xml.php");
    exit;
}