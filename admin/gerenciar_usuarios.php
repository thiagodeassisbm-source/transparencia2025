<?php
// /admin/gerenciar_usuarios.php

// Inicia a sessão e verifica se o usuário está logado.
require_once 'auth_check.php';
require_once '../conexao.php';

// Variáveis de sessão do usuário
$perfil_usuario = $_SESSION['admin_user_perfil'];
$id_usuario_logado = $_SESSION['admin_user_id'];
$nome_usuario_logado = $_SESSION['admin_user_nome'] ?? 'Usuário';

$mensagem = '';
$erro = '';

// Lógica para cadastrar um novo usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_usuario'])) {
    $usuario = trim($_POST['usuario']);
    $nome = trim($_POST['nome']); // Novo campo
    $senha = $_POST['senha'];
    $senha_confirma = $_POST['senha_confirma'];
    $perfil = $_POST['perfil'];

    // Validações
    if (empty($usuario) || empty($nome) || empty($senha)) {
        $erro = "Usuário, Nome e senha são obrigatórios.";
    } elseif ($senha !== $senha_confirma) {
        $erro = "As senhas não coincidem.";
    } elseif (!in_array($perfil, ['admin', 'editor'])) {
        $erro = "Perfil inválido selecionado.";
    } else {
        // Verifica se o usuário já existe
        $stmt_check = $pdo->prepare("SELECT id FROM usuarios_admin WHERE usuario = ?");
        $stmt_check->execute([$usuario]);
        if ($stmt_check->fetch()) {
            $erro = "Este nome de usuário já está em uso.";
        } else {
            // Se tudo estiver OK, criptografa a senha e insere no banco
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt_insert = $pdo->prepare("INSERT INTO usuarios_admin (usuario, nome, senha, perfil) VALUES (?, ?, ?, ?)");
            $stmt_insert->execute([$usuario, $nome, $senha_hash, $perfil]);
            $_SESSION['mensagem_sucesso'] = "Usuário '" . htmlspecialchars($nome) . "' cadastrado com sucesso!";
            header("Location: gerenciar_usuarios.php");
            exit;
        }
    }
}

// Busca os usuários existentes para listar na página
$usuarios = $pdo->query("SELECT id, usuario, nome, perfil FROM usuarios_admin ORDER BY usuario ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Usuários - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">

<?php
// Define as variáveis para o cabeçalho reutilizável
$page_title_for_header = 'Gerenciar Usuários';
$active_breadcrumb = 'Gerenciar Usuários';
$active_nav_link = 'gerenciar_usuarios.php';
include 'admin_header.php';
?>

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
            <div class="row">
                <div class="col-md-5">
                    <div class="card h-100">
                        <div class="card-header">Cadastrar Novo Usuário</div>
                        <div class="card-body">
                            <form method="POST" action="gerenciar_usuarios.php">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome Completo</label>
                                    <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex: Thiago de Assis" required>
                                </div>
                                <div class="mb-3">
                                    <label for="usuario" class="form-label">Nome de Usuário (Login)</label>
                                    <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Ex: thiago.admin" required>
                                </div>
                                <div class="mb-3">
                                    <label for="senha" class="form-label">Senha de Acesso</label>
                                    <input type="password" class="form-control" id="senha" name="senha" required>
                                </div>
                                <div class="mb-3">
                                    <label for="senha_confirma" class="form-label">Confirmar Senha</label>
                                    <input type="password" class="form-control" id="senha_confirma" name="senha_confirma" required>
                                </div>
                                <div class="mb-3">
                                    <label for="perfil" class="form-label">Perfil de Acesso</label>
                                    <select class="form-select" id="perfil" name="perfil">
                                        <option value="editor">Editor</option>
                                        <option value="admin">Administrador</option>
                                    </select>
                                </div>
                                <input type="hidden" name="add_usuario" value="1">
                                <button type="submit" class="btn btn-success">Cadastrar Usuário</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="card h-100">
                        <div class="card-header">Usuários Cadastrados</div>
                        <div class="table-responsive">
                            <table class="table table-striped mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Usuário</th>
                                        <th>Perfil</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($usuario['nome'] ?? 'Sem Nome'); ?></div>
                                            <small class="text-muted">@<?php echo htmlspecialchars($usuario['usuario']); ?></small>
                                            <?php if ($usuario['id'] == $_SESSION['admin_user_id']) echo ' <span class="badge bg-secondary">Você</span>'; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $usuario['perfil'] == 'admin' ? 'bg-primary' : 'bg-info text-dark'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($usuario['perfil'])); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Editar Usuário">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <?php if ($usuario['id'] != $_SESSION['admin_user_id']): ?>
                                                <form method="POST" action="excluir_usuario.php" class="d-inline ms-1" onsubmit="return confirm('Tem certeza que deseja excluir o usuário \'<?php echo addslashes(htmlspecialchars($usuario['usuario'])); ?>\'?');">
                                                    <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" data-bs-toggle="tooltip" title="Excluir Usuário">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl) });
</script>
</body>
</html>