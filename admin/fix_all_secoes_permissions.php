<?php
require_once 'auth_check.php';
require_once '../conexao.php';

$id_perfil_atual = $_SESSION['admin_user_id_perfil'] ?? 0;
$id_prefeitura = $_SESSION['id_prefeitura'] ?? 0;

if ($id_perfil_atual > 0 && $id_prefeitura > 0) {
    // Busca todas as seções (portais) desta prefeitura
    $stmt = $pdo->prepare("SELECT id FROM portais WHERE id_prefeitura = ?");
    $stmt->execute([$id_prefeitura]);
    $secoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total = 0;
    foreach ($secoes as $s) {
        $recurso = 'form_' . $s['id'];
        
        // Verifica se já existe permissão
        $check = $pdo->prepare("SELECT id FROM permissoes_perfil WHERE id_perfil = ? AND recurso = ?");
        $check->execute([$id_perfil_atual, $recurso]);
        
        if (!$check->fetch()) {
            $stmt_perm = $pdo->prepare("INSERT INTO permissoes_perfil (id_perfil, recurso, p_ver, p_lancar, p_editar, p_excluir) VALUES (?, ?, 1, 1, 1, 1)");
            $stmt_perm->execute([$id_perfil_atual, $recurso]);
            $total++;
        }
    }
    
    // Recarrega as permissões na sessão
    unset($_SESSION['permissoes_sessao']);
    
    echo "Sucesso! $total novas permissões de seções foram concedidas ao seu perfil ($id_perfil_atual). Sua sessão foi atualizada.";
} else {
    echo "Erro: Perfil ou Prefeitura não identificados na sessão.";
}
?>
