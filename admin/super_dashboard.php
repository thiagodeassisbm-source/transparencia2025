<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Bloqueia se não for superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
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

    header('Location: super_dashboard.php');
    exit;
}

// 1. Métricas Gerais
$total_clientes = $pdo->query("SELECT COUNT(*) FROM prefeituras")->fetchColumn();
$receita_mensal = $pdo->query("SELECT SUM(valor_mensalidade) FROM prefeituras WHERE status = 'ativo'")->fetchColumn() ?: 0;

// 2. Recebíveis do mês (todos os vencimentos das prefeituras ativas)
$stmt_venc = $pdo->query("SELECT nome, dia_vencimento, valor_mensalidade FROM prefeituras WHERE status = 'ativo' ORDER BY dia_vencimento ASC, nome ASC");
$vencimentos = $stmt_venc->fetchAll();

// 3. Volume de Dados por Prefeitura (Contagem de lançamentos/registros)
$stmt_dados = $pdo->query("
    SELECT p.nome, COUNT(r.id) as total_arquivos 
    FROM prefeituras p 
    LEFT JOIN portais pt ON p.id = pt.id_prefeitura 
    LEFT JOIN registros r ON pt.id = r.id_portal 
    GROUP BY p.id 
    ORDER BY total_arquivos DESC 
    LIMIT 5
");
$volume_dados = $stmt_dados->fetchAll();

$superadmins = $pdo->query("SELECT id, nome, usuario, email FROM usuarios_admin WHERE is_superadmin = 1 ORDER BY id DESC")->fetchAll();

$page_title_for_header = 'Dashboard SaaS';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <h4 class="fw-bold text-dark mt-2">Dashboard de Gestão de Prefeituras</h4>
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

    <!-- Row de Stats Principais -->
    <div class="row g-4 mb-5">
        <div class="col-md-6 col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-4 me-3">
                        <i class="bi bi-cash-stack fs-3 text-primary"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small text-uppercase mb-1 fw-bold">Receita (MRR)</h6>
                        <h3 class="mb-0 fw-bold">R$ <?php echo number_format($receita_mensal, 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-info bg-opacity-10 p-3 rounded-4 me-3">
                        <i class="bi bi-buildings-fill fs-3 text-info"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small text-uppercase mb-1 fw-bold">Prefeituras</h6>
                        <h3 class="mb-0 fw-bold"><?php echo $total_clientes; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
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

    <div class="row g-4">
        <!-- Recebíveis do Mês -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-calendar-check me-2 text-warning"></i> Recebíveis do Mês (todas as prefeituras)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small">
                                <tr>
                                    <th class="ps-4">Prefeitura</th>
                                    <th>Dia</th>
                                    <th class="pe-4 text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vencimentos as $venc): ?>
                                <tr>
                                    <td class="ps-4 py-3 fw-bold"><?php echo htmlspecialchars($venc['nome']); ?></td>
                                    <td><span class="badge bg-warning-subtle text-warning">Dia <?php echo $venc['dia_vencimento']; ?></span></td>
                                    <td class="pe-4 text-end fw-bold">R$ <?php echo number_format($venc['valor_mensalidade'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($vencimentos)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted small">Nenhuma prefeitura ativa com vencimento cadastrado.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Volume de Dados -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-graph-up-arrow me-2 text-success"></i> Volume de Dados Cadastrados</h6>
                </div>
                <div class="card-body">
                    <?php foreach ($volume_dados as $dado): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="fw-bold"><?php echo htmlspecialchars($dado['nome']); ?></span>
                            <span class="text-muted"><?php echo $dado['total_arquivos']; ?> itens</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <?php 
                                $max = $volume_dados[0]['total_arquivos'] ?: 1;
                                $percent = ($dado['total_arquivos'] / $max) * 100;
                            ?>
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
