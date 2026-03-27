<?php
require_once 'auth_check.php';
require_once 'conexao.php';
require_once 'functions_logs.php';
require_once 'functions_demo.php'; // Adiciona motor de clonagem demo

// Bloqueia se não for superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

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
    
    // Dados do Usuário Admin da Prefeitura
    $admin_user = filter_input(INPUT_POST, 'admin_user', FILTER_SANITIZE_SPECIAL_CHARS);
    $admin_pass = $_POST['admin_pass'];

    try {
        // 1. Validar se o usuário já existe no sistema global (Multi-tenant)
        $stmt_check = $pdo->prepare("SELECT id FROM usuarios_admin WHERE usuario = ?");
        $stmt_check->execute([$admin_user]);
        if ($stmt_check->fetch()) {
            throw new Exception("O nome de usuário '<strong>$admin_user</strong>' já está em uso em outra prefeitura. Escolha um nome exclusivo (ex: admin_".str_replace(' ', '', strtolower($nome)).").");
        }

        $pdo->beginTransaction();

        // 1. Cria a Prefeitura
        $stmt = $pdo->prepare("INSERT INTO prefeituras (nome, slug, responsavel_nome, responsavel_contato, dia_vencimento, valor_mensalidade, data_contratacao) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $slug, $responsavel_nome, $responsavel_contato, $dia_vencimento, $valor_mensalidade, $data_contratacao]);
        $id_prefeitura = $pdo->lastInsertId();

        // 2. Cria o Usuário Admin Local
        $senha_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
        $stmt_user = $pdo->prepare("INSERT INTO usuarios_admin (usuario, senha, nome, perfil, is_superadmin, id_prefeitura, id_perfil) VALUES (?, ?, ?, 'admin', 0, ?, 1)");
        $stmt_user->execute([$admin_user, $senha_hash, "Admin $nome", $id_prefeitura]);

        // 3. Inicializa configurações básicas (opcional, mas recomendado)
        $pdo->prepare("INSERT INTO configuracoes (chave, valor, id_prefeitura) VALUES ('prefeitura_titulo', ?, ?)")->execute([$nome, $id_prefeitura]);

        registrar_log($pdo, 'SUPERADMIN', 'NOVA_PREFEITURA', "Prefeitura $nome cadastrada com sucesso (ID: $id_prefeitura)");
        
        // 4. Carrega conteúdo de demonstração se solicitado
        $carregar_demo = filter_input(INPUT_POST, 'carregar_demo', FILTER_VALIDATE_INT);
        $demo_status = "";
        if ($carregar_demo === 1) {
            if (clonar_dados_demonstrativos($pdo, 1, $id_prefeitura)) {
                $demo_status = " e conteúdo de demonstração inicializado";
            }
        }

        $pdo->commit();
        $sucesso = "Cliente cadastrado com sucesso$demo_status! O acesso já está liberado.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $erro = "Erro ao cadastrar: " . $e->getMessage();
    }
}

$page_title_for_header = 'Cadastrar Novo Cliente';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-dark text-white p-4 rounded-top-4">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-building-add me-2"></i> Onboarding de Novo Cliente</h5>
                    <p class="mb-0 text-white-50 opacity-75">Configure os dados contratuais e o acesso administrativo inicial</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($erro): ?><div class="alert alert-danger"><?php echo $erro; ?></div><?php endif; ?>
                    <?php if ($sucesso): ?><div class="alert alert-success"><?php echo $sucesso; ?></div><?php endif; ?>

                    <form method="POST" class="row g-4">
                        <!-- SEÇÃO 1: DADOS MUNICIPAIS -->
                        <div class="col-12 py-2 border-bottom mb-2">
                            <h6 class="text-primary fw-bold mb-0">1. Informações da Prefeitura</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Nome da Prefeitura</label>
                            <input type="text" name="nome" class="form-control" placeholder="Ex: Prefeitura de Goiânia" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Slug (URL Amigável)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">/portal/</span>
                                <input type="text" name="slug" class="form-control" placeholder="goiania" required>
                            </div>
                        </div>

                        <!-- SEÇÃO 2: DADOS DE COBRANÇA -->
                        <div class="col-12 py-2 border-bottom mt-4 mb-2">
                            <h6 class="text-primary fw-bold mb-0">2. Dados Financeiros & Responsável</h6>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Nome do Responsável</label>
                            <input type="text" name="responsavel_nome" class="form-control" placeholder="Nome completo" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Contato (WhatsApp)</label>
                            <input type="text" name="responsavel_contato" class="form-control" placeholder="(62) 99999-9999" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Data de Contratação</label>
                            <input type="date" name="data_contratacao" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Valor da Mensalidade (R$)</label>
                            <input type="number" step="0.01" name="valor_mensalidade" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Dia de Vencimento</label>
                            <select name="dia_vencimento" class="form-select">
                                <option value="5">Dia 05</option>
                                <option value="10" selected>Dia 10</option>
                                <option value="15">Dia 15</option>
                                <option value="20">Dia 20</option>
                                <option value="25">Dia 25</option>
                            </select>
                        </div>

                        <!-- SEÇÃO 3: ACESSO ADMIN -->
                        <div class="col-12 py-2 border-bottom mt-4 mb-2">
                            <h6 class="text-primary fw-bold mb-0">3. Acesso Administrativo da Prefeitura</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Usuário Admin Local</label>
                            <input type="text" name="admin_user" class="form-control" placeholder="ex: admin_pref" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Senha Inicial</label>
                            <input type="password" name="admin_pass" class="form-control" placeholder="********" required>
                        </div>

                        <!-- SEÇÃO 4: CONFIGURAÇÕES DE BOAS-VINDAS -->
                        <div class="col-12 py-2 border-bottom mt-4 mb-2">
                            <h6 class="text-primary fw-bold mb-0">4. Configurações de Boas-vindas</h6>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch p-3 bg-light rounded-3 border">
                                <input class="form-check-input ms-0 me-3" type="checkbox" name="carregar_demo" value="1" id="switchDemo" checked>
                                <label class="form-check-label fw-bold" for="switchDemo">
                                    <i class="bi bi-magic text-warning me-2"></i> Carregar conteúdo demonstrativo de exemplo
                                    <small class="d-block text-muted fw-normal mt-1">Isso preencherá o portal com seções e dados de teste para facilitar o onboarding do cliente.</small>
                                </label>
                            </div>
                        </div>

                        <div class="col-12 mt-5 text-end">
                            <a href="super_dashboard.php" class="btn btn-light rounded-pill px-4 me-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow">
                                <i class="bi bi-cloud-check me-2"></i> Ativar Cliente Agora
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
