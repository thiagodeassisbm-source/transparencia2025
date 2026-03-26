<?php
// /admin/gerenciar_perfis.php
require_once 'auth_check.php';
require_once '../conexao.php';

// Apenas administradores podem acessar a gestão de perfis
if ($_SESSION['admin_user_perfil'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$mensagem = '';
$erro = '';

// Lógica para cadastrar novo perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_perfil'])) {
    $nome = trim($_POST['nome']);
    if (empty($nome)) {
        $erro = "O nome do perfil é obrigatório.";
    } else {
        $stmt_check = $pdo->prepare("SELECT id FROM perfis WHERE nome = ?");
        $stmt_check->execute([$nome]);
        if ($stmt_check->fetch()) {
            $erro = "Este perfil já existe.";
        } else {
            $stmt_insert = $pdo->prepare("INSERT INTO perfis (nome) VALUES (?)");
            $stmt_insert->execute([$nome]);
            $_SESSION['mensagem_sucesso'] = "Perfil '$nome' criado! Agora configure as permissões.";
            header("Location: gerenciar_perfis.php");
            exit;
        }
    }
}

// Busca os perfis existentes
$perfis = $pdo->query("SELECT id, nome, (SELECT COUNT(*) FROM usuarios_admin WHERE id_perfil = perfis.id) as total_usuarios FROM perfis ORDER BY nome ASC")->fetchAll();

$page_title_for_header = 'Gerenciar Perfis';
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-12 pt-4">
            <?php
            if (isset($_SESSION['mensagem_sucesso'])) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . 
                     htmlspecialchars($_SESSION['mensagem_sucesso']) . 
                     '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                unset($_SESSION['mensagem_sucesso']);
            }
            if ($erro) {
                echo '<div class="alert alert-danger">' . $erro . '</div>';
            }
            ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Novo Perfil de Acesso</h5></div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nome do Perfil</label>
                                    <input type="text" name="nome" class="form-control" placeholder="Ex: Moderador, Consulta" required>
                                </div>
                                <button type="submit" name="add_perfil" class="btn btn-primary w-100">Criar Perfil</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Perfis Cadastrados</h5></div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nome do Perfil</th>
                                        <th class="text-center">Usuários</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($perfis as $perfil): ?>
                                    <tr>
                                        <td><span class="fw-bold fs-5"><?php echo htmlspecialchars($perfil['nome']); ?></span></td>
                                        <td class="text-center"><span class="badge bg-light text-dark border"><?php echo $perfil['total_usuarios']; ?></span></td>
                                        <td class="text-end">
                                            <a href="editar_permissoes_perfil.php?id=<?php echo $perfil['id']; ?>" class="btn btn-outline-primary btn-sm me-1"><i class="bi bi-shield-lock me-1"></i> Configurar Permissões</a>
                                            <?php if ($perfil['nome'] !== 'Administrador'): ?>
                                                <!-- Lógica de exclusão se necessário -->
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
