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

                $transferir_id = filter_input(INPUT_POST, 'transferir_para_id', FILTER_VALIDATE_INT);

                if ($total_usuarios > 0 && (!$transferir_id || $transferir_id == $perfil_id)) {
                     $_SESSION['mensagem_erro'] = "Não é possível excluir o perfil '{$nome_perfil}' pois existem {$total_usuarios} usuários vinculados. Selecione um perfil de destino válido.";
                } else {
                    try {
                        $pdo->beginTransaction();

                        // Se houver transferência, move os usuários primeiro
                        if ($total_usuarios > 0 && $transferir_id) {
                            $stmt_transfer = $pdo->prepare("UPDATE usuarios_admin SET id_perfil = ? WHERE id_perfil = ?");
                            $stmt_transfer->execute([$transferir_id, $perfil_id]);
                            
                            $stmt_target_name = $pdo->prepare("SELECT nome FROM perfis WHERE id = ?");
                            $stmt_target_name->execute([$transferir_id]);
                            $nome_target = $stmt_target_name->fetchColumn();
                            
                            registrar_log($pdo, 'EDIÇÃO', 'usuarios_admin', "Transferiu $total_usuarios usuários do perfil $nome_perfil para $nome_target devido à exclusão do perfil original.");
                        }

                        // Deleta as permissões primeiro
                        $stmt_del_perms = $pdo->prepare("DELETE FROM permissoes_perfil WHERE id_perfil = ?");
                        $stmt_del_perms->execute([$perfil_id]);

                        // Deleta o perfil
                        $stmt_del_perfil = $pdo->prepare("DELETE FROM perfis WHERE id = ?");
                        $stmt_del_perfil->execute([$perfil_id]);

                        registrar_log($pdo, 'EXCLUSÃO', 'perfis', "Excluiu o perfil de acesso: $nome_perfil (ID: $perfil_id)");
                        
                        $pdo->commit();
                        $_SESSION['mensagem_sucesso'] = "Perfil '$nome_perfil' excluído com sucesso!" . ($total_usuarios > 0 ? " (Os usuários foram migrados para outro perfil)" : "");
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
