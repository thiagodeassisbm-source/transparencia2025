<?php
// /admin/gerenciar_usuarios.php
require_once 'auth_check.php';
require_once '../conexao.php';

// Apenas administradores podem gerenciar usuários ou perfis (dependendo da logica)
// No entanto, vamos permitir que quem tem permissão de 'usuarios' veja esta tela
if (!tem_permissao('usuarios', 'ver')) {
    header("Location: dashboard.php");
    exit;
}

$mensagem = '';
$erro = '';

// Lógica para cadastrar novo usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_usuario']) && tem_permissao('usuarios', 'lancar')) {
    $usuario = trim($_POST['usuario']);
    $nome = trim($_POST['nome']);
    $senha = $_POST['senha'];
    $senha_confirma = $_POST['senha_confirma'];
    $id_perfil = filter_input(INPUT_POST, 'id_perfil', FILTER_VALIDATE_INT);

    if (empty($usuario) || empty($nome) || empty($senha) || !$id_perfil) {
        $erro = "Todos os campos são obrigatórios.";
    } elseif ($senha !== $senha_confirma) {
        $erro = "As senhas não coincidem.";
    } else {
        $stmt_check = $pdo->prepare("SELECT id FROM usuarios_admin WHERE usuario = ?");
        $stmt_check->execute([$usuario]);
        if ($stmt_check->fetch()) {
            $erro = "Este nome de usuário já está em uso.";
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt_insert = $pdo->prepare("INSERT INTO usuarios_admin (usuario, nome, senha, id_perfil) VALUES (?, ?, ?, ?)");
            $stmt_insert->execute([$usuario, $nome, $senha_hash, $id_perfil]);
            $_SESSION['mensagem_sucesso'] = "Usuário cadastrado com sucesso!";
            header("Location: gerenciar_usuarios.php");
            exit;
        }
    }
}

// Busca os perfis para o select
$perfis_select = $pdo->query("SELECT id, nome FROM perfis ORDER BY nome ASC")->fetchAll();

// Busca os usuários existentes com os nomes dos perfis
$usuarios = $pdo->query("
    SELECT u.id, u.usuario, u.nome, p.nome as nome_perfil, u.id_perfil 
    FROM usuarios_admin u 
    JOIN perfis p ON u.id_perfil = p.id 
    ORDER BY u.usuario ASC
")->fetchAll();

$page_title_for_header = 'Gerenciar Usuários';
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-12 pt-4">
            <?php
            if (isset($_SESSION['mensagem_sucesso'])) {
                echo '<div class="alert alert-success alert-dismissible fade show">' . htmlspecialchars($_SESSION['mensagem_sucesso']) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                unset($_SESSION['mensagem_sucesso']);
            }
            if ($erro) echo '<div class="alert alert-danger">' . $erro . '</div>';
            ?>
            <div class="row">
                <?php if (tem_permissao('usuarios', 'lancar')): ?>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Novo Usuário</h5></div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nome Completo</label>
                                    <input type="text" class="form-control" name="nome" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Login</label>
                                    <input type="text" class="form-control" name="usuario" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Senha</label>
                                    <input type="password" class="form-control" name="senha" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Repetir Senha</label>
                                    <input type="password" class="form-control" name="senha_confirma" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Perfil de Acesso</label>
                                    <select class="form-select" name="id_perfil">
                                        <?php foreach ($perfis_select as $perf): ?>
                                            <option value="<?php echo $perf['id']; ?>"><?php echo htmlspecialchars($perf['nome']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <input type="hidden" name="add_usuario" value="1">
                                <button type="submit" class="btn btn-success w-100">Cadastrar Usuário</button>
                            </form>
                        </div>
                    </div>
                    <?php if ($_SESSION['admin_user_perfil'] === 'admin'): ?>
                        <div class="mt-3">
                            <a href="gerenciar_perfis.php" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-shield-lock me-1"></i> Gerenciar Perfis e Permissões</a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="<?php echo tem_permissao('usuarios', 'lancar') ? 'col-md-8' : 'col-md-12'; ?>">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Usuários Cadastrados</h5></div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nome / Login</th>
                                        <th>Perfil</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($u['nome']); ?></div>
                                            <small class="text-muted">@<?php echo htmlspecialchars($u['usuario']); ?></small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($u['nome_perfil']); ?></span></td>
                                        <td class="text-end">
                                            <?php if (tem_permissao('usuarios', 'editar')): ?>
                                                <a href="editar_usuario.php?id=<?php echo $u['id']; ?>" class="btn btn-primary btn-sm me-1"><i class="bi bi-pencil"></i></a>
                                            <?php endif; ?>
                                            <?php if (tem_permissao('usuarios', 'excluir') && $u['id'] != $_SESSION['admin_user_id']): ?>
                                                <form method="POST" action="excluir_usuario.php" class="d-inline" onsubmit="return confirm('Excluir este usuário?');">
                                                    <input type="hidden" name="usuario_id" value="<?php echo $u['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
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