<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Bloqueia se não for superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: super_dashboard.php");
    exit;
}

// Busca dados atuais
$stmt = $pdo->prepare("SELECT * FROM prefeituras WHERE id = ?");
$stmt->execute([$id]);
$pref = $stmt->fetch();

if (!$pref) {
    die("Prefeitura não encontrada.");
}

// Busca o usuário admin da prefeitura
$stmt_user = $pdo->prepare("SELECT id, usuario FROM usuarios_admin WHERE id_prefeitura = ? AND is_superadmin = 0 LIMIT 1");
$stmt_user->execute([$id]);
$admin_data = $stmt_user->fetch();

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $slug = filter_input(INPUT_POST, 'slug', FILTER_SANITIZE_SPECIAL_CHARS);
    $responsavel_nome = filter_input(INPUT_POST, 'responsavel_nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $responsavel_contato = filter_input(INPUT_POST, 'responsavel_contato', FILTER_SANITIZE_SPECIAL_CHARS);
    $dia_vencimento = filter_input(INPUT_POST, 'dia_vencimento', FILTER_VALIDATE_INT);
    $valor_mensalidade = filter_input(INPUT_POST, 'valor_mensalidade', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $data_contratacao = filter_input(INPUT_POST, 'data_contratacao', FILTER_SANITIZE_SPECIAL_CHARS);
    $data_contratacao = filter_input(INPUT_POST, 'data_contratacao', FILTER_SANITIZE_SPECIAL_CHARS);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
    $dominio_customizado = filter_input(INPUT_POST, 'dominio_customizado', FILTER_SANITIZE_URL);

    try {
        $stmt_upd = $pdo->prepare("UPDATE prefeituras SET nome = ?, slug = ?, responsavel_nome = ?, responsavel_contato = ?, dia_vencimento = ?, valor_mensalidade = ?, data_contratacao = ?, status = ?, dominio_customizado = ? WHERE id = ?");
        $stmt_upd->execute([$nome, $slug, $responsavel_nome, $responsavel_contato, $dia_vencimento, $valor_mensalidade, $data_contratacao, $status, $dominio_customizado, $id]);

        // Atualizar Usuário Admin se necessário
        $new_admin_user = filter_input(INPUT_POST, 'admin_user', FILTER_SANITIZE_SPECIAL_CHARS);
        $new_admin_pass = $_POST['admin_pass'] ?? '';

        if ($admin_data) {
            // Atualiza Username
            if ($new_admin_user && $new_admin_user !== $admin_data['usuario']) {
                $stmt_upd_user = $pdo->prepare("UPDATE usuarios_admin SET usuario = ? WHERE id = ?");
                $stmt_upd_user->execute([$new_admin_user, $admin_data['id']]);
            }
            // Atualiza Senha (se preenchida)
            if (!empty($new_admin_pass)) {
                $hash = password_hash($new_admin_pass, PASSWORD_DEFAULT);
                $stmt_upd_pass = $pdo->prepare("UPDATE usuarios_admin SET senha = ? WHERE id = ?");
                $stmt_upd_pass->execute([$hash, $admin_data['id']]);
            }
        }

        registrar_log($pdo, 'SUPERADMIN', 'EDITAR_PREFEITURA', "Dados da prefeitura $nome e acesso admin atualizados.");
        $sucesso = "Dados atualizados com sucesso!";
        
        // Recarrega dados
        $stmt->execute([$id]);
        $pref = $stmt->fetch();
    } catch (Exception $e) {
        $erro = "Erro ao atualizar: " . $e->getMessage();
    }
}

$page_title_for_header = 'Editar Prefeitura';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-dark text-white p-4 rounded-top-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i> Editar Dados de Gestão</h5>
                        <p class="mb-0 text-white-50 opacity-75"><?php echo htmlspecialchars($pref['nome']); ?></p>
                    </div>
                    <a href="super_dashboard.php" class="btn btn-outline-light btn-sm rounded-pill">Voltar</a>
                </div>
                <div class="card-body p-4">
                    <?php if ($erro): ?><div class="alert alert-danger px-4 rounded-3"><?php echo $erro; ?></div><?php endif; ?>
                    <?php if ($sucesso): ?><div class="alert alert-success px-4 rounded-3"><?php echo $sucesso; ?></div><?php endif; ?>

                    <form method="POST" class="row g-4">
                        <div class="col-12 py-2 border-bottom mb-2">
                            <h6 class="text-primary fw-bold mb-0">1. Informações Cadastrais</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Nome de Identificação (SaaS)</label>
                            <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($pref['nome']); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Slug (URL Amigável)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">/portal/</span>
                                <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($pref['slug']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Domínio Customizado</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-globe"></i></span>
                                <input type="text" name="dominio_customizado" class="form-control" value="<?php echo htmlspecialchars($pref['dominio_customizado'] ?? ''); ?>">
                            </div>
                            <small class="text-muted" style="font-size: 0.65rem;">Ex: transparencia.goiania.go.gov.br</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Status de Acesso</label>
                            <select name="status" class="form-select">
                                <option value="ativo" <?php echo $pref['status'] == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                <option value="suspenso" <?php echo $pref['status'] == 'suspenso' ? 'selected' : ''; ?>>Suspenso (Bloqueado)</option>
                                <option value="pendente_pagamento" <?php echo $pref['status'] == 'pendente_pagamento' ? 'selected' : ''; ?>>Aguardando Pagamento</option>
                            </select>
                        </div>

                        <div class="col-12 py-2 border-bottom mt-4 mb-2">
                            <h6 class="text-primary fw-bold mb-0">2. Financeiro & Contrato</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Nome do Responsável</label>
                            <input type="text" name="responsavel_nome" class="form-control" value="<?php echo htmlspecialchars($pref['responsavel_nome']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Contato (WhatsApp)</label>
                            <input type="text" name="responsavel_contato" class="form-control" value="<?php echo htmlspecialchars($pref['responsavel_contato']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Valor Mensalidade (R$)</label>
                            <input type="number" step="0.01" name="valor_mensalidade" class="form-control" value="<?php echo $pref['valor_mensalidade']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Dia de Vencimento</label>
                            <select name="dia_vencimento" class="form-select">
                                <?php for($i=5; $i<=25; $i+=5): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $pref['dia_vencimento'] == $i ? 'selected' : ''; ?>>Dia <?php echo sprintf('%02d', $i); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Data de Contratação</label>
                            <input type="date" name="data_contratacao" class="form-control" value="<?php echo $pref['data_contratacao']; ?>">
                        </div>

                        <!-- SEÇÃO 3: ACESSO ADMIN -->
                        <div class="col-12 py-2 border-bottom mt-4 mb-2">
                            <h6 class="text-primary fw-bold mb-0">3. Acesso Administrativo da Prefeitura</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Usuário Admin Local</label>
                            <input type="text" name="admin_user" class="form-control" value="<?php echo htmlspecialchars($admin_data['usuario'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Nova Senha (deixe em branco para manter)</label>
                            <input type="password" name="admin_pass" class="form-control" placeholder="********">
                        </div>

                        <div class="col-12 mt-5 text-end">
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow">
                                <i class="bi bi-save me-2"></i> Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
