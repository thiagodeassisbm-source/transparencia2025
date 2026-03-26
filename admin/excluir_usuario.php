<?php
require_once 'auth_check.php';
require_once '../conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_id'])) {
    $usuario_id_para_excluir = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
    $usuario_logado_id = $_SESSION['admin_user_id'];

    // TRAVA DE SEGURANÇA: Impede que o usuário se auto-exclua
    if ($usuario_id_para_excluir == $usuario_logado_id) {
        $_SESSION['mensagem_sucesso'] = "Erro: Você não pode excluir seu próprio usuário.";
    } elseif ($usuario_id_para_excluir) {
        $stmt = $pdo->prepare("DELETE FROM usuarios_admin WHERE id = ?");
        $stmt->execute([$usuario_id_para_excluir]);
        $_SESSION['mensagem_sucesso'] = "Usuário excluído com sucesso!";
    }
}

header("Location: gerenciar_usuarios.php");
exit();