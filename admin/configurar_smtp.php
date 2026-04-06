<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Determina se estamos no contexto de Super Admin (Global) ou Admin de Prefeitura
$super_pages = ['super_dashboard.php', 'super_logs.php', 'cadastrar_prefeitura.php', 'editar_prefeitura.php', 'gerenciar_prefeituras.php', 'switch_pref.php', 'alterar_status_pref.php', 'gerenciar_landing_recursos.php', 'editar_landing_recurso.php', 'gerenciar_mensagens.php', 'enviar_mensagem.php', 'configurar_copyright.php', 'configurar_smtp.php', 'gerenciar_superadmins.php', 'editar_usuario.php'];
$is_super_context = in_array(basename($_SERVER['PHP_SELF']), $super_pages) && isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] === 1;

// Se não for superadmin e tentar acessar contexto global, bloqueia
// Mas aqui queremos que AMBOS acessem a mesma página física, apenas o ALVO muda.
$id_prefeitura_alvo = $is_super_context ? 0 : ($_SESSION['id_prefeitura'] ?? 0);

// Se for um admin comum tentando salvar configuração global (id 0), bloqueia
if (!$is_super_context && $id_prefeitura_alvo === 0) {
    die("Acesso negado. Esta configuração é restrita ao administrador da prefeitura.");
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

    if ($id_prefeitura_alvo === 0) {
        // Salva na config_global (Fallback para todas as prefeituras)
        foreach ($configs as $chave => $valor) {
            $stmt = $pdo->prepare("INSERT INTO config_global (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?");
            $stmt->execute([$chave, $valor, $valor]);
        }
    } else {
        // Salva na tabela configuracoes (Específico da prefeitura)
        foreach ($configs as $chave => $valor) {
            $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor, id_prefeitura) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            $stmt->execute([$chave, $valor, $id_prefeitura_alvo]);
        }
    }

    registrar_log($pdo, 'CONFIG', 'SMTP', "Atualizou as configurações de SMTP para " . ($id_prefeitura_alvo === 0 ? "Global/Fallback" : "Prefeitura ID: $id_prefeitura_alvo"));
    $mensagem_sucesso = "Configurações de SMTP salvas com sucesso!";
}

// Busca configurações atuais
if ($id_prefeitura_alvo === 0) {
    $stmt = $pdo->query("SELECT chave, valor FROM config_global WHERE chave LIKE 'smtp_%'");
} else {
    $stmt = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE id_prefeitura = ? AND chave LIKE 'smtp_%'");
    $stmt->execute([$id_prefeitura_alvo]);
}
$config_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Valores padrão caso não existam
$smtp_host = $config_raw['smtp_host'] ?? '';
$smtp_port = $config_raw['smtp_port'] ?? '587';
$smtp_user = $config_raw['smtp_user'] ?? '';
$smtp_pass = $config_raw['smtp_pass'] ?? '';
$smtp_secure = $config_raw['smtp_secure'] ?? 'tls';
$smtp_from_email = $config_raw['smtp_from_email'] ?? '';
$smtp_from_name = $config_raw['smtp_from_name'] ?? 'Prefeitura Municipal';

$page_title_for_header = 'Configuração SMTP ' . ($id_prefeitura_alvo === 0 ? '(Global)' : '');
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Card de Orientação -->
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden <?php echo $id_prefeitura_alvo === 0 ? 'bg-dark' : 'bg-primary'; ?> bg-gradient text-white">
                <div class="card-body p-4 p-md-5">
                    <div class="row align-items-center">
                        <div class="col-md-<?php echo $id_prefeitura_alvo === 0 ? '12' : '8'; ?>">
                            <div class="badge bg-white bg-opacity-20 mb-3 px-3 py-2 rounded-pill">
                                <i class="bi bi-shield-check me-1"></i> <?php echo $id_prefeitura_alvo === 0 ? 'Configuração Global (Fallback)' : 'Configuração da Prefeitura Local'; ?>
                            </div>
                            <h2 class="fw-bold mb-3"><i class="bi bi-envelope-paper-heart me-2"></i> Como configurar o seu E-mail?</h2>
                            <p class="lead mb-4 opacity-75">
                                <?php if ($id_prefeitura_alvo === 0): ?>
                                    Estas são as credenciais <strong>globais</strong>. Elas serão usadas por qualquer prefeitura que NÃO configurar seu próprio SMTP.
                                <?php else: ?>
                                    Ao configurar o SMTP da sua prefeitura, todas as notificações de Ouvidoria e SIC serão enviadas usando o seu domínio oficial, aumentando a credibilidade e evitando que as mensagens caiam no lixo eletrônico.
                                <?php endif; ?>
                            </p>
                            
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="p-3 bg-white bg-opacity-10 rounded-4 border border-white border-opacity-10 h-100">
                                        <h6 class="fw-bold mb-2"><i class="bi bi-lightning-fill me-1 text-warning"></i> Ativação do Motor</h6>
                                        <p class="small mb-0 opacity-75">Após salvar, o sistema passará a usar automaticamente estas credenciais para novas manifestações.</p>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 bg-white bg-opacity-10 rounded-4 border border-white border-opacity-10 h-100">
                                        <h6 class="fw-bold mb-2"><i class="bi bi-key-fill me-1 text-info"></i> Senha de App</h6>
                                        <p class="small mb-0 opacity-75">Para Gmail/Outlook, nunca use sua senha pessoal. Gere uma <strong>"Senha de Aplicativo"</strong> nas configurações da conta.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if ($id_prefeitura_alvo !== 0): ?>
                        <div class="col-md-4 d-none d-md-block text-center">
                            <i class="bi bi-send-check display-1 opacity-25"></i>
                        </div>
                        <?php endif; ?>
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
                <div class="card-body p-4 p-md-5">
                    <form method="POST">
                        <div class="row g-4">
                            <!-- Título da Seção -->
                            <div class="col-12 mb-2">
                                <h5 class="fw-bold d-flex align-items-center">
                                    <span class="bg-light p-2 rounded-3 me-3 text-primary"><i class="bi bi-hdd-network-fill"></i></span>
                                    Servidor de Saída
                                </h5>
                                <hr class="opacity-10">
                            </div>

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

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="smtp_user" id="smtp_user" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($smtp_user); ?>" placeholder="usuario@dominio.com" required>
                                    <label for="smtp_user" class="text-muted fw-bold">E-mail de Autenticação</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="password" name="smtp_pass" id="smtp_pass" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($smtp_pass); ?>" placeholder="••••••••" required>
                                    <label for="smtp_pass" class="text-muted fw-bold">Senha de Aplicativo</label>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-floating">
                                    <select name="smtp_secure" id="smtp_secure" class="form-select bg-light border-0">
                                        <option value="tls" <?php echo $smtp_secure === 'tls' ? 'selected' : ''; ?>>TLS (Segurança Padrão)</option>
                                        <option value="ssl" <?php echo $smtp_secure === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                        <option value="" <?php echo $smtp_secure === '' ? 'selected' : ''; ?>>Nenhuma</option>
                                    </select>
                                    <label for="smtp_secure" class="text-muted fw-bold">Segurança da Conexão</label>
                                </div>
                            </div>

                            <!-- Identidade -->
                            <div class="col-12 mt-4 mb-2">
                                <h5 class="fw-bold d-flex align-items-center">
                                    <span class="bg-light p-2 rounded-3 me-3 text-success"><i class="bi bi-person-bounding-box"></i></span>
                                    Exibição para o Cidadão
                                </h5>
                                <hr class="opacity-10">
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" name="smtp_from_email" id="smtp_from_email" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($smtp_from_email); ?>" placeholder="nao-responda@dominio.com" required>
                                    <label for="smtp_from_email" class="text-muted fw-bold">E-mail de Resposta</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="smtp_from_name" id="smtp_from_name" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($smtp_from_name); ?>" placeholder="Prefeitura de ..." required>
                                    <label for="smtp_from_name" class="text-muted fw-bold">Nome Exibido (Ex: Prefeitura de Orizona)</label>
                                </div>
                            </div>

                            <div class="col-12 mt-5 text-end">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 py-3 fw-bold shadow transition-all hover-lift w-100 w-md-auto">
                                    <i class="bi bi-save2 me-2"></i> Salvar Minhas Configurações
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-lift:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important; }
.transition-all { transition: all 0.3s ease; }
.form-floating > .form-control:focus ~ label, .form-floating > .form-control:not(:placeholder-shown) ~ label {
    font-weight: 700 !important;
    text-transform: uppercase;
    font-size: 0.75rem;
    color: var(--bs-primary) !important;
}
.form-floating > .form-control, .form-floating > .form-select {
    border-radius: 12px !important;
}
</style>

<?php include 'admin_footer.php'; ?>
