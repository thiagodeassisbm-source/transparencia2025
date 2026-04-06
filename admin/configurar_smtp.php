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

$page_title_for_header = 'Configuração SMTP Global';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Card de Orientação (IMPORTANTE) -->
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-primary bg-gradient text-white">
                <div class="card-body p-4 p-md-5">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="fw-bold mb-3"><i class="bi bi-info-circle-fill me-2"></i> Como configurar o SMTP?</h2>
                            <p class="lead mb-4 opacity-75">O SMTP é responsável por enviar e-mails em nome da prefeitura. Configure corretamente abaixo para que os protocolos sejam enviados aos cidadãos.</p>
                            
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="p-3 bg-white bg-opacity-10 rounded-4 border border-white border-opacity-10 h-100">
                                        <h6 class="fw-bold"><i class="bi bi-google me-1"></i> Para Gmail / Google</h6>
                                        <ul class="small mb-0 opacity-75">
                                            <li>Use Host: <strong>smtp.gmail.com</strong></li>
                                            <li>Porta: <strong>587 (TLS)</strong></li>
                                            <li>Ative a <strong>"Senha de Aplicativo"</strong> em sua conta Google.</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 bg-white bg-opacity-10 rounded-4 border border-white border-opacity-10 h-100">
                                        <h6 class="fw-bold"><i class="bi bi-shield-check me-1"></i> Remetente Válido</h6>
                                        <p class="small mb-0 opacity-75">O e-mail configurado no campo "Usuário" deve ser o mesmo do campo "Email Remetente" para evitar que caia no SPAM.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 d-none d-md-block text-center">
                            <i class="bi bi-envelope-check display-1 opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <?php if ($mensagem_sucesso): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo $mensagem_sucesso; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <!-- Coluna do Formulário -->
                        <div class="col-lg-12">
                            <div class="p-4 p-md-5">
                                <div class="d-flex align-items-center mb-5">
                                    <div class="bg-dark p-3 rounded-4 me-3">
                                        <i class="bi bi-gear-fill text-white fs-4"></i>
                                    </div>
                                    <div>
                                        <h4 class="fw-bold mb-0">Credenciais de Autenticação</h4>
                                        <p class="text-muted mb-0 small">Campos obrigatórios para o funcionamento do motor de e-mail.</p>
                                    </div>
                                </div>

                                <form method="POST">
                                    <div class="row g-4">
                                        <!-- Servidor e Porta -->
                                        <div class="col-md-8">
                                            <div class="form-floating">
                                                <input type="text" name="smtp_host" id="smtp_host" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($smtp_host); ?>" placeholder="ex: smtp.gmail.com" required>
                                                <label for="smtp_host" class="text-muted fw-bold">Servidor SMTP (Host)</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <input type="number" name="smtp_port" id="smtp_port" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($smtp_port); ?>" placeholder="587" required>
                                                <label for="smtp_port" class="text-muted fw-bold">Porta</label>
                                            </div>
                                        </div>

                                        <!-- Usuário e Senha -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" name="smtp_user" id="smtp_user" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($smtp_user); ?>" placeholder="usuario@dominio.com" required>
                                                <label for="smtp_user" class="text-muted fw-bold">Usuário / E-mail</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="password" name="smtp_pass" id="smtp_pass" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($smtp_pass); ?>" placeholder="••••••••" required>
                                                <label for="smtp_pass" class="text-muted fw-bold">Senha</label>
                                            </div>
                                        </div>

                                        <!-- Segurança -->
                                        <div class="col-md-12">
                                            <div class="form-floating">
                                                <select name="smtp_secure" id="smtp_secure" class="form-select bg-light border-0">
                                                    <option value="tls" <?php echo $smtp_secure === 'tls' ? 'selected' : ''; ?>>TLS (Segurança Padrão / Recomendado)</option>
                                                    <option value="ssl" <?php echo $smtp_secure === 'ssl' ? 'selected' : ''; ?>>SSL (Portas Legacy)</option>
                                                    <option value="" <?php echo $smtp_secure === '' ? 'selected' : ''; ?>>Nenhuma / Apenas Conexão Aberta</option>
                                                </select>
                                                <label for="smtp_secure" class="text-muted fw-bold">Protocolo de Segurança</label>
                                            </div>
                                        </div>

                                        <!-- Identidade do Remetente -->
                                        <div class="col-12"><hr class="my-3 opacity-10"></div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="email" name="smtp_from_email" id="smtp_from_email" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($smtp_from_email); ?>" placeholder="nao-responda@dominio.com" required>
                                                <label for="smtp_from_email" class="text-muted fw-bold">E-mail Remetente (Exibido ao Cidadão)</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" name="smtp_from_name" id="smtp_from_name" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($smtp_from_name); ?>" placeholder="Prefeitura de ..." required>
                                                <label for="smtp_from_name" class="text-muted fw-bold">Nome do Remetente (Ex: Prefeitura de Orizona)</label>
                                            </div>
                                        </div>

                                        <div class="col-12 mt-5 text-end">
                                            <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 py-3 fw-bold shadow transition-all hover-lift w-100 w-md-auto">
                                                <i class="bi bi-save2 me-2"></i> Aplicar e Guardar Configurações
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-lift:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important; }
.transition-all { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.form-floating > .form-control:focus ~ label, .form-floating > .form-control:not(:placeholder-shown) ~ label {
    font-weight: 700 !important;
    text-transform: uppercase;
    font-size: 0.75rem;
    color: var(--bs-primary) !important;
}
.form-floating > .form-control {
    border-radius: 0.75rem !important;
}
.form-floating > .form-select {
    border-radius: 0.75rem !important;
}
</style>

<?php include 'admin_footer.php'; ?>
