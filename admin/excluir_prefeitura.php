<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Bloqueia se não for superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

$id_prefeitura = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_prefeitura) {
    header("Location: gerenciar_prefeituras.php?erro=ID inválido");
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Apaga valores dos registros dos portais vinculados
    $pdo->prepare("DELETE FROM valores_registros WHERE id_registro IN (SELECT id FROM registros WHERE id_portal IN (SELECT id FROM portais WHERE id_prefeitura = ?))")
        ->execute([$id_prefeitura]);

    // 2. Apaga os registros
    $pdo->prepare("DELETE FROM registros WHERE id_portal IN (SELECT id FROM portais WHERE id_prefeitura = ?)")
        ->execute([$id_prefeitura]);

    // 3. Apaga os campos dos portais
    $pdo->prepare("DELETE FROM campos_portal WHERE id_portal IN (SELECT id FROM portais WHERE id_prefeitura = ?)")
        ->execute([$id_prefeitura]);

    // 4. Apaga os portais (Seções)
    $pdo->prepare("DELETE FROM portais WHERE id_prefeitura = ?")
        ->execute([$id_prefeitura]);

    // 5. Apaga os cards (Atalhos)
    $pdo->prepare("DELETE FROM cards_informativos WHERE id_prefeitura = ?")
        ->execute([$id_prefeitura]);

    // 6. Apaga os usuários admin (Exceto o superadmin global se por acaso ele estivesse vinculado)
    $pdo->prepare("DELETE FROM usuarios_admin WHERE id_prefeitura = ? AND is_superadmin != 1")
        ->execute([$id_prefeitura]);

    // 7. Apaga as configurações
    $pdo->prepare("DELETE FROM configuracoes WHERE id_prefeitura = ?")
        ->execute([$id_prefeitura]);

    // 8. Apaga os Logs
    $pdo->prepare("DELETE FROM logs WHERE id_prefeitura = ?")
        ->execute([$id_prefeitura]);

    // 9. Por fim, apaga a prefeitura
    $pdo->prepare("DELETE FROM prefeituras WHERE id = ?")
        ->execute([$id_prefeitura]);

    $pdo->commit();

    // Registra log da exclusão (Auditória Central)
    registrar_log($pdo, 'EXCLUSÃO-TOTAL', 'prefeituras', "O Superadmin removeu a prefeitura ID: $id_prefeitura e todos os seus dados.");

    header("Location: gerenciar_prefeituras.php?sucesso=Prefeitura e dados vinculados removidos com sucesso!");
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: gerenciar_prefeituras.php?erro=Não foi possível excluir: " . $e->getMessage());
}
?>
