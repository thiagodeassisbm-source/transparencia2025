<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Bloqueia se não for superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

$mensagem_sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configs = [
        'smtp_host' => $_POST['smtp_host'] ?? '',
        'smtp_port' => $_POST['smtp_port'] ?? '',
        'smtp_user' => $_POST['smtp_user'] ?? '',
        'smtp_pass' => $_POST['smtp_pass'] ?? '',
        'smtp_secure' => $_POST['smtp_secure'] ?? '',
        'smtp_from_email' => $_POST['smtp_from_email'] ?? '',
        'smtp_from_name' => $_POST['smtp_from_name'] ?? ''
    ];

    foreach ($configs as $chave => $valor) {
        $stmt = $pdo->prepare("INSERT INTO config_global (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?");
        $stmt->execute([$chave, $valor, $valor]);
    }

    registrar_log($pdo, 'SUPERADMIN', 'CONFIG_SMTP', "Atualizou as configurações de SMTP global");
    $mensagem_sucesso = "Configurações de SMTP atualizadas com sucesso!";
}

// Busca configurações atuais
$stmt = $pdo->query("SELECT chave, valor FROM config_global WHERE chave LIKE 'smtp_%'");
$config_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Valores padrão caso não existam
$smtp_host = $config_raw['smtp_host'] ?? '';
$smtp_port = $config_raw['smtp_port'] ?? '587';
$smtp_user = $config_raw['smtp_user'] ?? '';
$smtp_pass = $config_raw['smtp_pass'] ?? '';
$smtp_secure = $config_raw['smtp_secure'] ?? 'tls';
$smtp_from_email = $config_raw['smtp_from_email'] ?? '';
$smtp_from_name = $config_raw['smtp_from_name'] ?? 'Prefeitura Municipal';

$page_title_for_header = 'Configurar SMTP Global';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <?php if ($mensagem_sucesso): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo $mensagem_sucesso; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white p-4 pt-5 pb-5 text-center position-relative">
                    <div class="bg-primary bg-opacity-25 p-3 rounded-circle d-inline-flex mb-3 shadow-sm" style="backdrop-filter: blur(5px);">
                        <i class="bi bi-envelope-at fs-3 text-white"></i>
                    </div>
                    <h4 class="fw-bold mb-1">Configuração de E-mail (SMTP)</h4>
                    <p class="text-white-50 small mb-0">Configure as credenciais de envio de e-mails automáticos do sistema.</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form method="POST">
                        <div class="row g-4">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold text-muted text-uppercase">Servidor SMTP (Host)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="bi bi-server text-muted"></i></span>
                                    <input type="text" name="smtp_host" class="form-control form-control-lg bg-light border-0" value="<?php echo htmlspecialchars($smtp_host); ?>" placeholder="ex: smtp.gmail.com" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">Porta</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="bi bi-door-open text-muted"></i></span>
                                    <input type="number" name="smtp_port" class="form-control form-control-lg bg-light border-0" value="<?php echo htmlspecialchars($smtp_port); ?>" placeholder="587" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Usuário / E-mail</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="bi bi-person-fill text-muted"></i></span>
                                    <input type="text" name="smtp_user" class="form-control form-control-lg bg-light border-0" value="<?php echo htmlspecialchars($smtp_user); ?>" placeholder="usuario@dominio.com" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Senha</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="bi bi-key-fill text-muted"></i></span>
                                    <input type="password" name="smtp_pass" class="form-control form-control-lg bg-light border-0" value="<?php echo htmlspecialchars($smtp_pass); ?>" placeholder="••••••••" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Segurança</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="bi bi-shield-lock-fill text-muted"></i></span>
                                    <select name="smtp_secure" class="form-select form-select-lg bg-light border-0">
                                        <option value="tls" <?php echo $smtp_secure === 'tls' ? 'selected' : ''; ?>>TLS (Recomendado)</option>
                                        <option value="ssl" <?php echo $smtp_secure === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                        <option value="" <?php echo $smtp_secure === '' ? 'selected' : ''; ?>>Nenhuma</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12"><hr class="my-3 opacity-10"></div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Email Remetente</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="bi bi-send-fill text-muted"></i></span>
                                    <input type="email" name="smtp_from_email" class="form-control form-control-lg bg-light border-0" value="<?php echo htmlspecialchars($smtp_from_email); ?>" placeholder="nao-responda@dominio.com" required>
                                </div>
                                <div class="form-text mt-2 small">Este é o e-mail que o cidadão verá como remetente.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Nome do Remetente</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="bi bi-building text-muted"></i></span>
                                    <input type="text" name="smtp_from_name" class="form-control form-control-lg bg-light border-0" value="<?php echo htmlspecialchars($smtp_from_name); ?>" placeholder="Prefeitura de ..." required>
                                </div>
                            </div>

                            <div class="col-12 mt-5 text-center">
                                <button type="submit" class="btn btn-primary rounded-pill px-5 py-3 fw-bold shadow-lg transition-all hover-lift">
                                    <i class="bi bi-check2-circle mb-0 me-2"></i> Salvar e Validar Configurações
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Ajuda / Dicas -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Como configurar?</h6>
                            <p class="small text-muted mb-0">Para Gmail, use a porta 587 (TLS) e crie uma "Senha de Aplicativo" nas configurações de segurança da sua conta Google. Para outros provedores, consulte o suporte técnico do seu e-mail.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-lightning text-warning me-2"></i>Ação do Sistema</h6>
                            <p class="small text-muted mb-0">Após configurado, o sistema enviará automaticamente o protocolo e as orientações de acesso para o e-mail informado pelo cidadão ao abrir solicitações no E-SIC e Ouvidoria.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-lift:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
.transition-all { transition: all 0.3s ease; }
</style>

<?php include 'admin_footer.php'; ?>
