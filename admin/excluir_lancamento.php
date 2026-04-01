<?php
require_once 'auth_check.php';

require_once '../conexao.php';
require_once 'functions_logs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registro_id'])) {
    $registro_id = filter_input(INPUT_POST, 'registro_id', FILTER_VALIDATE_INT);
    $portal_id = filter_input(INPUT_POST, 'portal_id', FILTER_VALIDATE_INT);

    // Trava de Segurança Granular
    if (!tem_permissao('form_' . $portal_id, 'excluir')) {
        die("Acesso negado. Você não tem permissão para excluir dados desta seção.");
    }

    if ($registro_id && $portal_id) {
        try {
            $pdo->beginTransaction();

            // 1. Encontrar e apagar possíveis arquivos físicos
            $stmt_files = $pdo->prepare(
                "SELECT vr.valor FROM valores_registros vr
                 JOIN campos_portal cp ON vr.id_campo = cp.id
                 WHERE vr.id_registro = ? AND cp.tipo_campo = 'anexo'"
            );
            $stmt_files->execute([$registro_id]);
            $arquivos = $stmt_files->fetchAll(PDO::FETCH_COLUMN);

            foreach ($arquivos as $arquivo) {
                // O caminho no banco é relativo ao admin (ex: ../uploads/arquivo.png)
                if (!empty($arquivo) && file_exists($arquivo)) {
                    // Tenta apagar o arquivo e lança um erro se não conseguir
                    if (!unlink($arquivo)) {
                        throw new Exception("Falha ao apagar o arquivo físico: " . $arquivo . ". Verifique as permissões da pasta uploads.");
                    }
                }
            }

            // 2. Apagar o registro do banco (ON DELETE CASCADE apaga os valores)
            $stmt_delete = $pdo->prepare("DELETE FROM registros WHERE id = ?");
            $stmt_delete->execute([$registro_id]);
            
            $pdo->commit();
            
            registrar_log(
                $pdo,
                'EXCLUSÃO',
                modulo_log_lancamento($pdo, $portal_id),
                "Excluiu lançamento ID $registro_id (seção portal_id $portal_id)."
            );
            
            $_SESSION['mensagem_sucesso'] = "Lançamento excluído com sucesso!";

        } catch (Exception $e) {
            $pdo->rollBack();
            // Mostra o erro exato na tela
            die("<strong>Ocorreu um erro:</strong> " . $e->getMessage());
        }
    } else {
        $_SESSION['mensagem_sucesso'] = "Erro: IDs inválidos para exclusão.";
    }
    
    header("Location: ver_lancamentos.php?portal_id=" . $portal_id);
    exit();
}

// Se o script for acessado sem POST, redireciona para o início
header("Location: index.php");
exit();