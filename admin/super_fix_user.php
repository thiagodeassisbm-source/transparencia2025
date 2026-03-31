<?php
// /admin/super_fix_user.php
// Script de auto-migração e permissão para o usuário logado

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['admin_user_id'])) {
    die("Erro: Você não está logado no painel administrativo.");
}

$user_id = $_SESSION['admin_user_id'];
$pref_id = $_SESSION['id_prefeitura'] ?? 0;

try {
    $pdo->beginTransaction();

    // 1. Busca os dados atuais do usuário
    $stmt_u = $pdo->prepare("SELECT id_perfil, perfil, id_prefeitura FROM usuarios_admin WHERE id = ?");
    $stmt_u->execute([$user_id]);
    $user_data = $stmt_u->fetch();

    if (!$user_data) {
        throw new Exception("Usuário não encontrado na base de dados.");
    }

    $id_perfil = $user_data['id_perfil'];
    $perfil_texto = $user_data['perfil']; // 'admin' ou 'editor'

    // 2. Se não tem ID de perfil, cria ou busca um perfil padrão 'Administrador' para esta prefeitura
    if (empty($id_perfil)) {
        $nome_perfil = ($perfil_texto == 'admin') ? 'Administrador' : 'Editor';
        
        // Verifica se já existe um perfil com esse nome para a prefeitura
        $stmt_p = $pdo->prepare("SELECT id FROM perfis WHERE nome = ? AND (id_prefeitura = ? OR id_prefeitura IS NULL)");
        $stmt_p->execute([$nome_perfil, $pref_id]);
        $id_perfil = $stmt_p->fetchColumn();

        if (!$id_perfil) {
            // Cria o perfil
            $stmt_pi = $pdo->prepare("INSERT INTO perfis (nome, id_prefeitura) VALUES (?, ?)");
            $stmt_pi->execute([$nome_perfil, $pref_id]);
            $id_perfil = $pdo->lastInsertId();
        }

        // Vincula o usuário ao novo perfil
        $stmt_uv = $pdo->prepare("UPDATE usuarios_admin SET id_perfil = ? WHERE id = ?");
        $stmt_uv->execute([$id_perfil, $user_id]);
        
        $_SESSION['admin_user_id_perfil'] = $id_perfil;
    }

    // 3. Garante que o perfil tenha permissão para TODAS as seções da prefeitura atual
    $stmt_s = $pdo->prepare("SELECT id FROM portais WHERE id_prefeitura = ? OR id_prefeitura IS NULL");
    $stmt_s->execute([$pref_id]);
    $secoes = $stmt_s->fetchAll(PDO::FETCH_ASSOC);

    $total_perm = 0;
    foreach ($secoes as $s) {
        $recurso = 'form_' . $s['id'];
        
        // Verifica se já existe permissão (Ver e Lançar)
        $stmt_c = $pdo->prepare("SELECT id FROM permissoes_perfil WHERE id_perfil = ? AND recurso = ?");
        $stmt_c->execute([$id_perfil, $recurso]);
        
        if (!$stmt_c->fetch()) {
            $stmt_ins = $pdo->prepare("INSERT INTO permissoes_perfil (id_perfil, recurso, p_ver, p_lancar, p_editar, p_excluir) VALUES (?, ?, 1, 1, 1, 1)");
            $stmt_ins->execute([$id_perfil, $recurso]);
            $total_perm++;
        }
    }

    // 4. Garante permissão básica para o dashboard e seções globais
    $recursos_obrigatorios = ['dashboard', 'secoes', 'categorias', 'usuarios', 'perfis'];
    foreach ($recursos_obrigatorios as $rob) {
        $stmt_c2 = $pdo->prepare("SELECT id FROM permissoes_perfil WHERE id_perfil = ? AND recurso = ?");
        $stmt_c2->execute([$id_perfil, $rob]);
        if (!$stmt_c2->fetch()) {
            $stmt_ins2 = $pdo->prepare("INSERT INTO permissoes_perfil (id_perfil, recurso, p_ver, p_lancar, p_editar, p_excluir) VALUES (?, ?, 1, 1, 1, 1)");
            $stmt_ins2->execute([$id_perfil, $rob]);
            $total_perm++;
        }
    }

    $pdo->commit();

    // Limpa o cache da sessão
    unset($_SESSION['permissoes_sessao']);
    
    echo "<h3>SUCESSO!</h3>";
    echo "Seu usuário foi migrado com sucesso para o perfil <b>#$id_perfil</b>.<br>";
    echo "Foram concedidas <b>$total_perm</b> permissões de seção e acesso.<br>";
    echo "<br><a href='dashboard.php' style='padding: 10px 20px; background: #22c55e; color: white; text-decoration: none; border-radius: 5px;'>Ir para o Painel</a>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "<h3>ERRO:</h3> " . $e->getMessage();
}
?>
