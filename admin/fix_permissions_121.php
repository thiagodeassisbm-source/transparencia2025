<?php
require_once 'auth_check.php';
require_once '../conexao.php';

$id_perfil_atual = $_SESSION['admin_user_id_perfil'] ?? 0;
$id_secao = 121; // secao teste da imagem

if ($id_perfil_atual > 0) {
    // Tenta inserir, se já existir não faz nada (ignore ou replace)
    $stmt = $pdo->prepare("INSERT IGNORE INTO permissoes_perfil (id_perfil, recurso, p_ver, p_lancar, p_editar, p_excluir) VALUES (?, ?, 1, 1, 1, 1)");
    $stmt->execute([$id_perfil_atual, 'form_' . $id_secao]);
    
    // Recarrega as permissões na sessão
    unset($_SESSION['permissoes_sessao']);
    
    echo "Permissões concedidas para a seção $id_secao ao perfil $id_perfil_atual. Sua sessão foi atualizada.";
} else {
    echo "Erro: Perfil não identificado.";
}
?>
