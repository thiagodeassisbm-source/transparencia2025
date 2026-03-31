<?php
// /admin/excluir_perfil.php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Apenas administradores podem excluir perfis
if ($_SESSION['admin_user_perfil'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['perfil_id'])) {
    $perfil_id = filter_input(INPUT_POST, 'perfil_id', FILTER_VALIDATE_INT);

    if ($perfil_id) {
        // Busca info do perfil
        $stmt_perfil = $pdo->prepare("SELECT nome FROM perfis WHERE id = ?");
        $stmt_perfil->execute([$perfil_id]);
        $perfil = $stmt_perfil->fetch();

        if ($perfil) {
            $nome_perfil = $perfil['nome'];

            // Proteção contra exclusão do perfil Administrador raiz
            // Verificamos por ID (normalmente 1) e por nome por segurança
            if ($perfil_id == 1 || strtolower($nome_perfil) === 'administrador') {
                $_SESSION['mensagem_erro'] = "Erro: O perfil principal de Administrador não pode ser excluído.";
            } else {
                // Verifica se há usuários vinculados
                $stmt_users = $pdo->prepare("SELECT COUNT(*) FROM usuarios_admin WHERE id_perfil = ?");
                $stmt_users->execute([$perfil_id]);
                $total_usuarios = $stmt_users->fetchColumn();

                if ($total_usuarios > 0) {
                     $_SESSION['mensagem_erro'] = "Não é possível excluir o perfil '{$nome_perfil}' pois existem {$total_usuarios} usuários vinculados a ele. Altere o perfil desses usuários primeiro.";
                } else {
                    try {
                        $pdo->beginTransaction();

                        // Deleta as permissões primeiro
                        $stmt_del_perms = $pdo->prepare("DELETE FROM permissoes_perfil WHERE id_perfil = ?");
                        $stmt_del_perms->execute([$perfil_id]);

                        // Deleta o perfil
                        $stmt_del_perfil = $pdo->prepare("DELETE FROM perfis WHERE id = ?");
                        $stmt_del_perfil->execute([$perfil_id]);

                        registrar_log($pdo, 'EXCLUSÃO', 'perfis', "Excluiu o perfil de acesso: $nome_perfil (ID: $perfil_id)");
                        
                        $pdo->commit();
                        $_SESSION['mensagem_sucesso'] = "Perfil '$nome_perfil' excluído com sucesso!";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $_SESSION['mensagem_erro'] = "Erro ao excluir perfil: " . $e->getMessage();
                    }
                }
            }
        } else {
            $_SESSION['mensagem_erro'] = "Perfil não encontrado.";
        }
    }
}

header("Location: gerenciar_perfis.php");
exit();
