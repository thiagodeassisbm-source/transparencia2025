<?php
require_once 'auth_check.php';
require_once '../conexao.php';

$usuario_id_para_editar = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$usuario_id_para_editar) { header("Location: index.php"); exit; }

// Pega informações do usuário logado
$perfil_logado = $_SESSION['admin_user_perfil'];
$id_logado = $_SESSION['admin_user_id'];

// TRAVA DE SEGURANÇA: Editor só pode editar a si mesmo
if ($perfil_logado === 'editor' && $usuario_id_para_editar != $id_logado) {
    $_SESSION['mensagem_sucesso'] = "Acesso negado: Editores só podem alterar o próprio perfil.";
    header("Location: index.php");
    exit;
}

$erro = '';

// Processa o formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $senha = $_POST['senha'];
    $senha_confirma = $_POST['senha_confirma'];
    
    // Se o usuário logado for admin, pega o perfil do post. Senão, mantém o perfil como 'editor'.
    $perfil = ($perfil_logado === 'admin') ? ($_POST['perfil'] ?? 'editor') : 'editor';

    if (empty($usuario)) { 
        $erro = "O nome de usuário não pode ser vazio."; 
    } elseif (!in_array($perfil, ['admin', 'editor'])) {
        $erro = "Perfil inválido selecionado.";
    } else {
        // Trava de segurança para impedir que o admin se rebaixe
        if ($usuario_id_para_editar == $id_logado && $perfil_logado === 'admin' && $perfil !== 'admin') {
            $erro = "Você não pode remover seu próprio status de Administrador.";
        } else {
            // Verifica se o novo nome de usuário já está em uso por OUTRO usuário
            $stmt_check = $pdo->prepare("SELECT id FROM usuarios_admin WHERE usuario = ? AND id != ?");
            $stmt_check->execute([$usuario, $usuario_id_para_editar]);
            if ($stmt_check->fetch()) {
                $erro = "Este nome de usuário já está em uso por outra conta.";
            } else {
                // Lógica de atualização
                if (!empty($senha)) {
                    if ($senha !== $senha_confirma) {
                        $erro = "As novas senhas não coincidem.";
                    } else {
                        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                        $stmt_update = $pdo->prepare("UPDATE usuarios_admin SET usuario = ?, senha = ?, perfil = ? WHERE id = ?");
                        $stmt_update->execute([$usuario, $senha_hash, $perfil, $usuario_id_para_editar]);
                        $_SESSION['mensagem_sucesso'] = "Usuário e senha atualizados com sucesso!";
                        header("Location: " . ($perfil_logado === 'admin' ? 'gerenciar_usuarios.php' : 'index.php'));
                        exit;
                    }
                } else {
                    $stmt_update = $pdo->prepare("UPDATE usuarios_admin SET usuario = ?, perfil = ? WHERE id = ?");
                    $stmt_update->execute([$usuario, $perfil, $usuario_id_para_editar]);
                    $_SESSION['mensagem_sucesso'] = "Usuário atualizado com sucesso!";
                    header("Location: " . ($perfil_logado === 'admin' ? 'gerenciar_usuarios.php' : 'index.php'));
                    exit;
                }
            }
        }
    }
}

// Busca os dados atuais do usuário para preencher o formulário
$stmt = $pdo->prepare("SELECT id, usuario, perfil FROM usuarios_admin WHERE id = ?");
$stmt->execute([$usuario_id_para_editar]);
$usuario_atual = $stmt->fetch();

if (!$usuario_atual) { header("Location: index.php"); exit; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuário - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">

<header class="page-header">
    <div class="container-fluid container-custom-padding">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Início</a></li>
                <li class="breadcrumb-item"><a href="index.php">Administração</a></li>
                <?php if ($perfil_logado === 'admin'): ?>
                <li class="breadcrumb-item"><a href="gerenciar_usuarios.php">Gerenciar Usuários</a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page">Editar Perfil</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <h1>Editar Perfil de <?php echo htmlspecialchars($usuario_atual['usuario']); ?></h1>
            <a href="logout.php" class="btn btn-danger"><i class="bi bi-box-arrow-right"></i> Sair</a>
        </div>
    </div>
</header>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-12 pt-4">
            <?php 
                if (isset($_SESSION['mensagem_sucesso'])) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['mensagem_sucesso']) . '</div>';
                    unset($_SESSION['mensagem_sucesso']);
                }
                if ($erro) { 
                    echo '<div class="alert alert-danger">' . $erro . '</div>'; 
                } 
            ?>
            <div class="card">
                <div class="card-header">Formulário de Edição</div>
                <div class="card-body">
                    <form method="POST" action="editar_usuario.php?id=<?php echo $usuario_id_para_editar; ?>">
                        <div class="mb-3">
                            <label for="usuario" class="form-label">Nome de Usuário</label>
                            <input type="text" class="form-control" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuario_atual['usuario']); ?>" required>
                        </div>
                        
                        <?php if ($perfil_logado === 'admin'): ?>
                        <div class="mb-3">
                            <label for="perfil" class="form-label">Perfil de Acesso</label>
                            <select class="form-select" id="perfil" name="perfil" <?php if ($usuario_atual['id'] == $id_logado) echo 'disabled'; ?>>
                                <option value="editor" <?php echo ($usuario_atual['perfil'] == 'editor') ? 'selected' : ''; ?>>Editor</option>
                                <option value="admin" <?php echo ($usuario_atual['perfil'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                            <?php if ($usuario_atual['id'] == $id_logado): ?>
                                <small class="form-text text-muted">Você não pode alterar o seu próprio perfil.</small>
                                <input type="hidden" name="perfil" value="admin" />
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <hr>
                        <p class="text-muted">Preencha os campos abaixo apenas se desejar alterar a senha.</p>
                        <div class="mb-3">
                            <label for="senha" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" id="senha" name="senha">
                        </div>
                        <div class="mb-3">
                            <label for="senha_confirma" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" id="senha_confirma" name="senha_confirma">
                        </div>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        <a href="<?php echo ($perfil_logado === 'admin') ? 'gerenciar_usuarios.php' : 'index.php'; ?>" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center p-3 bg-light mt-4">
    &copy; <?php echo date('Y'); ?> - Todos os direitos reservados.
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>