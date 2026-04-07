<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Contexto: admins de prefeitura e super admin
$id_prefeitura_alvo = $_SESSION['id_prefeitura'] ?? 0;

// Chaves que serão salvas na tabela configuracoes
$chaves = [
    // E-mail de confirmação de protocolo
    'email_tpl_confirmacao_titulo'      => 'Título do cabeçalho',
    'email_tpl_confirmacao_assunto'     => 'Assunto do e-mail',
    'email_tpl_confirmacao_intro'       => 'Texto de introdução',
    'email_tpl_confirmacao_label_prot'  => 'Label do protocolo',
    'email_tpl_confirmacao_instrucao_titulo' => 'Título "Como acompanhar?"',
    'email_tpl_confirmacao_instrucao'   => 'Texto de instrução',
    'email_tpl_confirmacao_rodape'      => 'Mensagem de rodapé',
    // E-mail de aviso de resposta
    'email_tpl_resposta_titulo'         => 'Título do cabeçalho',
    'email_tpl_resposta_assunto'        => 'Assunto do e-mail',
    'email_tpl_resposta_intro'          => 'Texto de introdução',
    'email_tpl_resposta_label_prot'     => 'Label do protocolo',
    'email_tpl_resposta_instrucao_titulo' => 'Título "Como visualizar?"',
    'email_tpl_resposta_instrucao'      => 'Texto de instrução',
    'email_tpl_resposta_rodape'         => 'Mensagem de rodapé',
    'email_tpl_resposta_btn'            => 'Texto do botão',
];

// Salvar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($chaves as $chave => $label) {
        $valor = $_POST[$chave] ?? '';
        $stmt = $pdo->prepare("
            INSERT INTO configuracoes (chave, valor, id_prefeitura)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)
        ");
        $stmt->execute([$chave, $valor, $id_prefeitura_alvo]);
    }
    registrar_log($pdo, 'CONFIG', 'TEMPLATES_EMAIL', 'Atualizou os textos dos templates de e-mail.');
    $_SESSION['flash_templates'] = 'success';
    header("Location: configurar_templates_email.php");
    exit;
}

// Carregar valores atuais
$stmt = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE id_prefeitura = ? AND chave LIKE 'email_tpl_%'");
$stmt->execute([$id_prefeitura_alvo]);
$cfg = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Defaults
$defaults = [
    'email_tpl_confirmacao_titulo'           => 'Confirmação de Solicitação',
    'email_tpl_confirmacao_assunto'          => 'Protocolo de Atendimento - {SERVICO}',
    'email_tpl_confirmacao_intro'            => 'Sua solicitação no {SERVICO} da Prefeitura foi registrada com sucesso.',
    'email_tpl_confirmacao_label_prot'       => 'Número do Protocolo:',
    'email_tpl_confirmacao_instrucao_titulo' => 'Como acompanhar?',
    'email_tpl_confirmacao_instrucao'        => 'Você pode utilizar este número de protocolo para acompanhar o andamento da sua solicitação diretamente em nosso portal da transparência, na seção correspondente.',
    'email_tpl_confirmacao_rodape'           => 'Esta é uma mensagem automática, por favor não responda a este e-mail.',
    'email_tpl_resposta_titulo'              => 'Sua solicitação foi respondida!',
    'email_tpl_resposta_assunto'             => 'Resposta Disponível - Protocolo {PROTOCOLO}',
    'email_tpl_resposta_intro'              => 'Informamos que a Prefeitura enviou uma resposta oficial para a sua solicitação de {SERVICO}.',
    'email_tpl_resposta_label_prot'          => 'Número do Protocolo:',
    'email_tpl_resposta_instrucao_titulo'    => 'Como visualizar a resposta?',
    'email_tpl_resposta_instrucao'           => 'Para ler o conteúdo completo da resposta, acesse o portal da transparência e utilize a ferramenta de Consulta de Protocolo com o número acima.',
    'email_tpl_resposta_rodape'              => 'Esta é uma mensagem automática, por favor não responda a este e-mail.',
    'email_tpl_resposta_btn'                 => 'Acessar o Portal',
];

function tpl_val(array $cfg, array $defaults, string $key): string {
    return htmlspecialchars($cfg[$key] ?? $defaults[$key] ?? '');
}

$flash = $_SESSION['flash_templates'] ?? null;
unset($_SESSION['flash_templates']);

$page_title_for_header = 'Templates de E-mail';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row g-4">

        <!-- Banner informativo -->
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-primary bg-gradient text-white">
                <div class="card-body p-4 p-md-5">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="badge bg-white bg-opacity-20 mb-3 px-3 py-2 rounded-pill">
                                <i class="bi bi-envelope-open-heart me-1"></i> Personalização de E-mails
                            </div>
                            <h2 class="fw-bold mb-3">
                                <i class="bi bi-pencil-square me-2"></i> Templates de E-mail
                            </h2>
                            <p class="lead mb-0 opacity-75">
                                Personalize os textos enviados automaticamente aos cidadãos. Use as variáveis disponíveis para incluir dados dinâmicos em cada mensagem.
                            </p>
                        </div>
                        <div class="col-md-4 d-none d-md-flex justify-content-end">
                            <div class="row g-2 text-center">
                                <div class="col-12">
                                    <div class="bg-white bg-opacity-10 rounded-4 p-3">
                                        <p class="small mb-1 opacity-75 fw-bold">Variáveis disponíveis</p>
                                        <code class="text-warning d-block">{NOME} — nome do cidadão</code>
                                        <code class="text-warning d-block">{PROTOCOLO} — nº protocolo</code>
                                        <code class="text-warning d-block">{SERVICO} — tipo (E-SIC, Ouvidoria...)</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($flash === 'success'): ?>
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> Templates salvos com sucesso!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php endif; ?>

        <div class="col-12">
            <form method="POST" id="formTemplates">
                <!-- ABAS -->
                <ul class="nav nav-pills mb-4 gap-2" id="emailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active px-4 rounded-pill fw-bold" id="tab-confirmacao-btn" data-bs-toggle="pill" data-bs-target="#tab-confirmacao" type="button">
                            <i class="bi bi-envelope-check me-2"></i>Confirmação de Protocolo
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link px-4 rounded-pill fw-bold" id="tab-resposta-btn" data-bs-toggle="pill" data-bs-target="#tab-resposta" type="button">
                            <i class="bi bi-reply-fill me-2"></i>Aviso de Resposta
                        </button>
                    </li>
                </ul>

                <div class="tab-content">

                    <!-- ==================== ABA 1: CONFIRMAÇÃO ==================== -->
                    <div class="tab-pane fade show active" id="tab-confirmacao" role="tabpanel">
                        <div class="row g-4">

                            <!-- Prévia -->
                            <div class="col-lg-5 order-lg-2">
                                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 80px;">
                                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                                        <h6 class="fw-bold mb-0"><i class="bi bi-eye me-2"></i>Prévia do E-mail</h6>
                                        <p class="small text-muted mb-0">Atualiza ao digitar nos campos</p>
                                    </div>
                                    <div class="card-body p-3">
                                        <div id="preview-confirmacao" style="border:1px solid #eee; border-radius:10px; overflow:hidden; font-family:Arial,sans-serif; font-size:13px; color:#333;">
                                            <div id="prev-conf-header" style="background:#004a99;color:#fff;padding:16px;text-align:center;">
                                                <strong id="prev-conf-titulo">...</strong>
                                            </div>
                                            <div style="padding:20px;">
                                                <p>Olá, <strong>João da Silva</strong>,</p>
                                                <p id="prev-conf-intro">...</p>
                                                <div style="background:#f9f9f9;border-left:4px solid #004a99;padding:14px;margin:16px 0;">
                                                    <small id="prev-conf-label-prot" style="text-transform:uppercase;font-size:11px;color:#777;font-weight:bold;">...</small><br>
                                                    <strong style="font-size:18px;color:#004a99;">SIC202600001</strong>
                                                </div>
                                                <p><strong id="prev-conf-instrucao-titulo">...</strong></p>
                                                <p id="prev-conf-instrucao">...</p>
                                                <p style="font-size:12px;color:#555;" id="prev-conf-rodape">...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Formulário -->
                            <div class="col-lg-7 order-lg-1">
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-body p-4">
                                        <h5 class="fw-bold mb-4 text-primary"><i class="bi bi-envelope-check me-2"></i>E-mail: Confirmação de Protocolo</h5>

                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-bold small text-muted text-uppercase">Assunto do e-mail</label>
                                                <input type="text" name="email_tpl_confirmacao_assunto" class="form-control bg-light border-0 rounded-3"
                                                    value="<?php echo tpl_val($cfg, $defaults, 'email_tpl_confirmacao_assunto'); ?>"
                                                    placeholder="Protocolo de Atendimento - {SERVICO}">
                                                <div class="form-text">Use <code>{SERVICO}</code> para incluir o tipo da solicitação.</div>
                                            </div>
                                            <div class="col-12">
                                                <label for="conf_titulo" class="form-label fw-bold small text-muted text-uppercase">Título do cabeçalho (fundo azul)</label>
                                                <input type="text" name="email_tpl_confirmacao_titulo" id="conf_titulo" class="form-control bg-light border-0 rounded-3 tpl-live"
                                                    data-target="prev-conf-titulo"
                                                    value="<?php echo tpl_val($cfg, $defaults, 'email_tpl_confirmacao_titulo'); ?>">
                                            </div>
                                            <div class="col-12">
                                                <label for="conf_intro" class="form-label fw-bold small text-muted text-uppercase">Texto de introdução</label>
                                                <textarea name="email_tpl_confirmacao_intro" id="conf_intro" rows="2" class="form-control bg-light border-0 rounded-3 tpl-live"
                                                    data-target="prev-conf-intro"><?php echo tpl_val($cfg, $defaults, 'email_tpl_confirmacao_intro'); ?></textarea>
                                                <div class="form-text">Use <code>{SERVICO}</code></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="conf_label_prot" class="form-label fw-bold small text-muted text-uppercase">Label do número de protocolo</label>
                                                <input type="text" name="email_tpl_confirmacao_label_prot" id="conf_label_prot" class="form-control bg-light border-0 rounded-3 tpl-live"
                                                    data-target="prev-conf-label-prot"
                                                    value="<?php echo tpl_val($cfg, $defaults, 'email_tpl_confirmacao_label_prot'); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="conf_instrucao_titulo" class="form-label fw-bold small text-muted text-uppercase">Título "Como acompanhar?"</label>
                                                <input type="text" name="email_tpl_confirmacao_instrucao_titulo" id="conf_instrucao_titulo" class="form-control bg-light border-0 rounded-3 tpl-live"
                                                    data-target="prev-conf-instrucao-titulo"
                                                    value="<?php echo tpl_val($cfg, $defaults, 'email_tpl_confirmacao_instrucao_titulo'); ?>">
                                            </div>
                                            <div class="col-12">
                                                <label for="conf_instrucao" class="form-label fw-bold small text-muted text-uppercase">Texto de instrução</label>
                                                <textarea name="email_tpl_confirmacao_instrucao" id="conf_instrucao" rows="3" class="form-control bg-light border-0 rounded-3 tpl-live"
                                                    data-target="prev-conf-instrucao"><?php echo tpl_val($cfg, $defaults, 'email_tpl_confirmacao_instrucao'); ?></textarea>
                                            </div>
                                            <div class="col-12">
                                                <label for="conf_rodape" class="form-label fw-bold small text-muted text-uppercase">Mensagem de rodapé / aviso automático</label>
                                                <input type="text" name="email_tpl_confirmacao_rodape" id="conf_rodape" class="form-control bg-light border-0 rounded-3 tpl-live"
                                                    data-target="prev-conf-rodape"
                                                    value="<?php echo tpl_val($cfg, $defaults, 'email_tpl_confirmacao_rodape'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== ABA 2: RESPOSTA ==================== -->
                    <div class="tab-pane fade" id="tab-resposta" role="tabpanel">
                        <div class="row g-4">

                            <!-- Prévia -->
                            <div class="col-lg-5 order-lg-2">
                                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 80px;">
                                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                                        <h6 class="fw-bold mb-0"><i class="bi bi-eye me-2"></i>Prévia do E-mail</h6>
                                        <p class="small text-muted mb-0">Atualiza ao digitar</p>
                                    </div>
                                    <div class="card-body p-3">
                                        <div id="preview-resposta" style="border:1px solid #eee; border-radius:10px; overflow:hidden; font-family:Arial,sans-serif; font-size:13px; color:#333;">
                                            <div style="background:#10b981;color:#fff;padding:16px;text-align:center;">
                                                <strong id="prev-resp-titulo">...</strong>
                                            </div>
                                            <div style="padding:20px;">
                                                <p>Olá, <strong>João da Silva</strong>,</p>
                                                <p id="prev-resp-intro">...</p>
                                                <div style="background:#f0fdf4;border-left:4px solid #10b981;padding:14px;margin:16px 0;">
                                                    <small id="prev-resp-label-prot" style="font-size:11px;color:#777;">...</small><br>
                                                    <strong style="font-size:18px;color:#065f46;">SIC202600001</strong>
                                                </div>
                                                <p><strong id="prev-resp-instrucao-titulo">...</strong></p>
                                                <p id="prev-resp-instrucao">...</p>
                                                <div style="text-align:center;margin:20px 0;">
                                                    <span id="prev-resp-btn" style="background:#10b981;color:#fff;padding:10px 22px;border-radius:6px;font-weight:bold;display:inline-block;">...</span>
                                                </div>
                                                <p style="font-size:12px;color:#555;" id="prev-resp-rodape">...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Formulário -->
                            <div class="col-lg-7 order-lg-1">
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-body p-4">
                                        <h5 class="fw-bold mb-4 text-success"><i class="bi bi-reply-fill me-2"></i>E-mail: Aviso de Resposta</h5>

                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-bold small text-muted text-uppercase">Assunto do e-mail</label>
                                                <input type="text" name="email_tpl_resposta_assunto" class="form-control bg-light border-0 rounded-3"
                                                    value="<?php echo tpl_val($cfg, $defaults, 'email_tpl_resposta_assunto'); ?>"
                                                    placeholder="Resposta Disponível - Protocolo {PROTOCOLO}">
                                                <div class="form-text">Use <code>{PROTOCOLO}</code> para incluir o número.</div>
                                            </div>
                                            <div class="col-12">
                                                <label for="resp_titulo" class="form-label fw-bold small text-muted text-uppercase">Título do cabeçalho (fundo verde)</label>
                                                <input type="text" name="email_tpl_resposta_titulo" id="resp_titulo" class="form-control bg-light border-0 rounded-3 tpl-live"
                                                    data-target="prev-resp-titulo"
                                                    value="<?php echo tpl_val($cfg, $defaults, 'email_tpl_resposta_titulo'); ?>">
                                            </div>
                                            <div class="col-12">
                                                <label for="resp_intro" class="form-label fw-bold small text-muted text-uppercase">Texto de introdução</label>
                                                <textarea name="email_tpl_resposta_intro" id="resp_intro" rows="2" class="form-control bg-light border-0 rounded-3 tpl-live"
                                                    data-target="prev-resp-intro"><?php echo tpl_val($cfg, $defaults, 'email_tpl_resposta_intro'); ?></textarea>
                                                <div class="form-text">Use <code>{SERVICO}</code></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="resp_label_prot" class="form-label fw-bold small text-muted text-uppercase">Label do protocolo</label>
                                                <input type="text" name="email_tpl_resposta_label_prot" id="resp_label_prot" class="form-control bg-light border-0 rounded-3 tpl-live"
                                                    data-target="prev-resp-label-prot"
                                                    value="<?php echo tpl_val($cfg, $defaults, 'email_tpl_resposta_label_prot'); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="resp_btn" class="form-label fw-bold small text-muted text-uppercase">Texto do botão</label>
                                                <input type="text" name="email_tpl_resposta_btn" id="resp_btn" class="form-control bg-light border-0 rounded-3 tpl-live"
                                                    data-target="prev-resp-btn"
                                                    value="<?php echo tpl_val($cfg, $defaults, 'email_tpl_resposta_btn'); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="resp_instrucao_titulo" class="form-label fw-bold small text-muted text-uppercase">Título "Como visualizar?"</label>
                                                <input type="text" name="email_tpl_resposta_instrucao_titulo" id="resp_instrucao_titulo" class="form-control bg-light border-0 rounded-3 tpl-live"
                                                    data-target="prev-resp-instrucao-titulo"
                                                    value="<?php echo tpl_val($cfg, $defaults, 'email_tpl_resposta_instrucao_titulo'); ?>">
                                            </div>
                                            <div class="col-12">
                                                <label for="resp_instrucao" class="form-label fw-bold small text-muted text-uppercase">Texto de instrução</label>
                                                <textarea name="email_tpl_resposta_instrucao" id="resp_instrucao" rows="3" class="form-control bg-light border-0 rounded-3 tpl-live"
                                                    data-target="prev-resp-instrucao"><?php echo tpl_val($cfg, $defaults, 'email_tpl_resposta_instrucao'); ?></textarea>
                                            </div>
                                            <div class="col-12">
                                                <label for="resp_rodape" class="form-label fw-bold small text-muted text-uppercase">Mensagem de rodapé</label>
                                                <input type="text" name="email_tpl_resposta_rodape" id="resp_rodape" class="form-control bg-light border-0 rounded-3 tpl-live"
                                                    data-target="prev-resp-rodape"
                                                    value="<?php echo tpl_val($cfg, $defaults, 'email_tpl_resposta_rodape'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Botão salvar global -->
                <div class="mt-5 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 py-3 fw-bold shadow">
                        <i class="bi bi-save2 me-2"></i> Salvar Todos os Templates
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Preview ao vivo
document.querySelectorAll('.tpl-live').forEach(function(el) {
    function atualizar() {
        var targetId = el.getAttribute('data-target');
        var target = document.getElementById(targetId);
        if (target) target.textContent = el.value || '...';
    }
    el.addEventListener('input', atualizar);
    atualizar(); // inicia ao carregar
});
</script>

<style>
.nav-pills .nav-link { color: var(--bs-dark); background: #f0f0f0; }
.nav-pills .nav-link.active { background: var(--bs-primary); color: #fff; }
.form-control:focus, .form-select:focus { border-color: var(--bs-primary) !important; box-shadow: 0 0 0 3px rgba(13,110,253,.15) !important; }
</style>

<?php include 'admin_footer.php'; ?>
