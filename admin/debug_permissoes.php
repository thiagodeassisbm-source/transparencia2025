<?php
require_once 'auth_check.php';
require_once '../conexao.php';

echo "<h3>Diagnóstico de Permissões</h3>";
echo "ID Usuário: " . ($_SESSION['admin_user_id'] ?? 'Não definido') . "<br>";
echo "ID Perfil na Sessão: " . ($_SESSION['admin_user_id_perfil'] ?? 'Não definido') . "<br>";
echo "Perfil (Texto): " . ($_SESSION['admin_user_perfil'] ?? 'Não definido') . "<br>";
echo "É Superadmin: " . ($_SESSION['is_superadmin'] ?? 'Não') . "<br>";

$id_perfil = $_SESSION['admin_user_id_perfil'] ?? 0;
if ($id_perfil > 0) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM permissoes_perfil WHERE id_perfil = ?");
    $stmt->execute([$id_perfil]);
    echo "Total de permissões cadastradas para este perfil: " . $stmt->fetchColumn() . "<br>";
    
    $stmt2 = $pdo->prepare("SELECT * FROM permissoes_perfil WHERE id_perfil = ? AND recurso = 'form_121'");
    $stmt2->execute([$id_perfil]);
    $p121 = $stmt2->fetch();
    if ($p121) {
        echo "Permissão para 'form_121' ENCONTRADA: Ver=" . $p121['p_ver'] . ", Lançar=" . $p121['p_lancar'] . "<br>";
    } else {
        echo "Permissão para 'form_121' NÃO encontrada no banco para este perfil.<br>";
    }
} else {
    echo "ALERTA: Você está logado mas seu usuário não possui um ID de Perfil vinculado. Isso acontece com usuários antigos que não foram migrados.<br>";
}
?>
