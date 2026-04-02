<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_superadmin'])) {
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $senha_confirmacao = (string) ($_POST['senha_confirmacao'] ?? '');

    try {
        if ($nome === '' || $usuario === '' || $email === '' || $senha === '' || $senha_confirmacao === '') {
            throw new Exception('Preencha todos os campos para criar o superadmin.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('E-mail inválido.');
        }
        if (strlen($senha) < 8) {
            throw new Exception('A senha precisa ter pelo menos 8 caracteres.');
        }
        if ($senha !== $senha_confirmacao) {
            throw new Exception('A confirmação de senha não confere.');
        }

        $stmt_user = $pdo->prepare('SELECT id FROM usuarios_admin WHERE usuario = ?');
        $stmt_user->execute([$usuario]);
        if ($stmt_user->fetchColumn()) {
            throw new Exception('Já existe um usuário com esse login.');
        }

        $stmt_email = $pdo->prepare('SELECT id FROM usuarios_admin WHERE email = ?');
        $stmt_email->execute([$email]);
        if ($stmt_email->fetchColumn()) {
            throw new Exception('Já existe um usuário com esse e-mail.');
        }

        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt_ins = $pdo->prepare(
            "INSERT INTO usuarios_admin (usuario, email, senha, is_superadmin, id_prefeitura, nome, perfil, id_perfil)
             VALUES (?, ?, ?, 1, NULL, ?, 'admin', 1)"
        );
        $stmt_ins->execute([$usuario, $email, $senha_hash, $nome]);
        $novo_id = (int) $pdo->lastInsertId();

        registrar_log($pdo, 'SUPERADMIN', 'usuarios_admin', "Criou usuário superadmin: $usuario (ID: $novo_id).");
        $_SESSION['mensagem_sucesso'] = "Superadmin criado com sucesso: $usuario.";
    } catch (Exception $e) {
        $_SESSION['mensagem_erro'] = $e->getMessage();
    }

    header('Location: gerenciar_superadmins.php');
    exit;
}

$superadmins = $pdo->query("SELECT id, nome, usuario, email FROM usuarios_admin WHERE is_superadmin = 1 ORDER BY id DESC")->fetchAll();

$page_title_for_header = 'Usuários Superadmin';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <h4 class="fw-bold text-dark mt-2">Configurações de Superadmin</h4>
        <p class="text-muted mb-0">Cadastre e acompanhe usuários com acesso total ao sistema.</p>
    </div>

    <?php if (!empty($_SESSION['mensagem_sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4" role="alert">
            <?php echo htmlspecialchars($_SESSION['mensagem_sucesso']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php unset($_SESSION['mensagem_sucesso']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['mensagem_erro'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4" role="alert">
            <?php echo htmlspecialchars($_SESSION['mensagem_erro']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php unset($_SESSION['mensagem_erro']); ?>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-person-plus-fill me-2 text-primary"></i> Novo Usuário Superadmin</h6>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="criar_superadmin" value="1">
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-muted">Nome completo</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Usuário (login)</label>
                            <input type="text" name="usuario" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">E-mail</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Senha</label>
                            <input type="password" name="senha" class="form-control" minlength="8" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Confirmar senha</label>
                            <input type="password" name="senha_confirmacao" class="form-control" minlength="8" required>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i class="bi bi-person-check me-1"></i> Criar superadmin
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-people-fill me-2 text-info"></i> Superadmins Cadastrados</h6>
                    <span class="badge bg-light text-dark border rounded-pill px-3"><?php echo count($superadmins); ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="ps-3">Nome</th>
                                <th>Usuário</th>
                                <th class="pe-3">E-mail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($superadmins as $su): ?>
                                <tr>
                                    <td class="ps-3 fw-semibold"><?php echo htmlspecialchars((string) $su['nome']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $su['usuario']); ?></td>
                                    <td class="pe-3"><?php echo htmlspecialchars((string) $su['email']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($superadmins)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted small">Nenhum superadmin cadastrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
