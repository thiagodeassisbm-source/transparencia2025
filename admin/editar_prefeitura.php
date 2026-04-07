<?php
/** ARQUIVO ATUALIZADO EM 07/04/2026 - PADRONIZAÇÃO COM CADASTRO **/
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
    header("Location: gerenciar_prefeituras.php");
    exit;
}

// Busca dados da prefeitura
$stmt = $pdo->prepare("SELECT * FROM prefeituras WHERE id = ?");
$stmt->execute([$id]);
$prefeitura = $stmt->fetch();

if (!$prefeitura) {
    header("Location: gerenciar_prefeituras.php");
    exit;
}

// Processamento do Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    $nome = $_POST['nome'];
    $slug = $_POST['slug'];
    $dominio = $_POST['dominio_customizado'];
    $status = $_POST['status'];
    $resp_nome = $_POST['responsavel_nome'] ?? '';
    $resp_contato = $_POST['responsavel_contato'] ?? '';
    $dia_venc = (int)($_POST['dia_vencimento'] ?? 10);
    $valor_mensal = $_POST['valor_mensalidade'] ?? 0;
    $data_contratacao = $_POST['data_contratacao'] ?? date('Y-m-d');

    try {
        $stmt_update = $pdo->prepare("
            UPDATE prefeituras 
            SET nome = ?, slug = ?, dominio_customizado = ?, status = ?, 
                responsavel_nome = ?, responsavel_contato = ?, dia_vencimento = ?, 
                valor_mensalidade = ?, data_contratacao = ?
            WHERE id = ?
        ");
        $stmt_update->execute([$nome, $slug, $dominio, $status, $resp_nome, $resp_contato, $dia_venc, $valor_mensal, $data_contratacao, $id]);

        registrar_log($pdo, 'SUPERADMIN', 'EDICAO_PREFEITURA', "Atualizou prefeitura $nome (ID: $id)");
        
        $sucesso = "Dados atualizados com sucesso!";
        // Atualiza os dados locais para exibir no form
        $prefeitura = array_merge($prefeitura, [
            'nome' => $nome, 'slug' => $slug, 'dominio_customizado' => $dominio, 
            'status' => $status, 'responsavel_nome' => $resp_nome, 
            'responsavel_contato' => $resp_contato, 'dia_vencimento' => $dia_venc,
            'valor_mensalidade' => $valor_mensal, 'data_contratacao' => $data_contratacao
        ]);
    } catch (Exception $e) {
        $erro = "Erro ao atualizar: " . $e->getMessage();
    }
}

$page_title_for_header = 'Editar Cliente SaaS';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <!-- Card de Cabeçalho Superior -->
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="d-flex flex-column flex-md-row">
                        <div class="bg-dark p-4 d-flex align-items-center justify-content-center text-white" style="min-width: 110px;">
                            <i class="bi bi-building-gear display-5"></i>
                        </div>
                        <div class="p-4 flex-grow-1 bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="fw-bold mb-1 text-dark">Edição de Instância SaaS</h5>
                                    <p class="text-muted small mb-0">Gestão global de <strong><?php echo htmlspecialchars((string)($prefeitura['nome'] ?? '')); ?></strong> (ID: <?php echo $id; ?>)</p>
                                </div>
                                <a href="gerenciar_prefeituras.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4 fw-bold">
                                    <i class="bi bi-arrow-left me-2"></i> Voltar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($sucesso)): ?>
                <div class="alert alert-success shadow-sm rounded-4 border-0 mb-4 px-4 py-3"><i class="bi bi-check-circle-fill me-2"></i> <?php echo $sucesso; ?></div>
            <?php endif; ?>
            <?php if (isset($erro)): ?>
                <div class="alert alert-danger shadow-sm rounded-4 border-0 mb-4 px-4 py-3"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $erro; ?></div>
            <?php endif; ?>

            <form method="POST" class="row g-4">
                <div class="col-lg-8">
                    <!-- SEÇÃO 1: DADOS MUNICIPAIS -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-0 py-3 ps-4">
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-building me-2 text-primary"></i> 1. Informações da Prefeitura</h6>
                        </div>
                        <div class="card-body p-4 pt-0">
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label small fw-bold text-muted">Nome da Prefeitura</label>
                                    <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars((string)($prefeitura['nome'] ?? '')); ?>" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-muted">Slug (URL)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">/portal/</span>
                                        <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars((string)($prefeitura['slug'] ?? '')); ?>" required>
                                    </div>
                                </div>
                                <div class="col-12 mt-4">
                                    <label class="form-label small fw-bold text-muted">Domínio Whitelabel (Customizado)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white text-primary border-end-0"><i class="bi bi-globe2"></i></span>
                                        <input type="text" name="dominio_customizado" class="form-control border-start-0 py-2" value="<?php echo htmlspecialchars((string)($prefeitura['dominio_customizado'] ?? '')); ?>" placeholder="ex: portaldatransparencia.suacidade.go.gov.br">
                                    </div>
                                </div>
                                <!-- Guia de Configuração Whitelabel -->
                                <div class="col-12 mt-4">
                                    <div class="card border-primary-subtle shadow-sm">
                                        <div class="card-header bg-primary-subtle py-2">
                                            <h6 class="mb-0 text-primary fw-bold small"><i class="bi bi-magic me-2"></i>Como Funciona o Domínio Próprio (Whitelabel)</h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="row g-3 small">
                                                <div class="col-md-4">
                                                    <p class="fw-bold text-dark mb-1"><span class="badge bg-primary me-1 text-white">1</span> O que escrever?</p>
                                                    <p class="text-muted mb-0">Digite o endereço final que o cliente quer usar. ex: <code>transparencia.cidade.gov.br</code></p>
                                                </div>
                                                <div class="col-md-4">
                                                    <p class="fw-bold text-dark mb-1"><span class="badge bg-primary me-1 text-white">2</span> No DNS (Eles fazem)</p>
                                                    <p class="text-muted mb-0">Crie um <strong>CNAME</strong> apontando para o endereço principal deste sistema.</p>
                                                </div>
                                                <div class="col-md-4">
                                                    <p class="fw-bold text-dark mb-1"><span class="badge bg-primary me-1 text-white">3</span> No Servidor (Você faz)</p>
                                                    <p class="text-muted mb-0">Adicione o domínio como <strong>Alias</strong> ou <strong>Estacionado</strong> nesta conta de hospedagem.</p>
                                                </div>
                                            </div>
                                            <div class="mt-3 text-center">
                                                <a href="tutorial_dns.php" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-4 fw-bold shadow-sm" style="border-width: 2px;">
                                                    <i class="bi bi-book-half me-2"></i> Ver Tutorial Completo (Guia Definitivo)
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEÇAÕ 2: DADOS FINANCEIROS -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-0 py-3 ps-4">
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-currency-dollar me-2 text-primary"></i> 2. Dados Financeiros & Contratuais</h6>
                        </div>
                        <div class="card-body p-4 pt-0">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Responsável Contratual</label>
                                    <input type="text" name="responsavel_nome" class="form-control" value="<?php echo htmlspecialchars((string)($prefeitura['responsavel_nome'] ?? '')); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">WhatsApp / Contato</label>
                                    <input type="text" name="responsavel_contato" class="form-control" value="<?php echo htmlspecialchars((string)($prefeitura['responsavel_contato'] ?? '')); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Fee Mensal (R$)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">R$</span>
                                        <input type="number" step="0.01" name="valor_mensalidade" class="form-control" value="<?php echo htmlspecialchars((string)($prefeitura['valor_mensalidade'] ?? 0)); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Dia de Vencimento</label>
                                    <select name="dia_vencimento" class="form-select">
                                        <?php for($i=5; $i<=25; $i+=5): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($prefeitura['dia_vencimento'] ?? 10) == $i ? 'selected' : ''; ?>>Dia <?php echo sprintf('%02d', $i); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Data de Contratação</label>
                                    <input type="date" name="data_contratacao" class="form-control" value="<?php echo htmlspecialchars((string)($prefeitura['data_contratacao'] ?? date('Y-m-d'))); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- STATUS -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-0 py-3 ps-4">
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-activity me-2 text-primary"></i> Status do Portal</h6>
                        </div>
                        <div class="card-body p-4 pt-0 text-start">
                            <label class="form-label small fw-bold text-muted">Disponibilidade</label>
                            <select name="status" class="form-select rounded-3 py-2">
                                <option value="ativo" <?php echo ($prefeitura['status'] ?? 'ativo') == 'ativo' ? 'selected' : ''; ?>>Ativo (Visível)</option>
                                <option value="inativo" <?php echo ($prefeitura['status'] ?? '') == 'inativo' ? 'selected' : ''; ?>>Suspenso / Offline</option>
                                <option value="manutencao" <?php echo ($prefeitura['status'] ?? '') == 'manutencao' ? 'selected' : ''; ?>>Manutenção</option>
                            </select>
                            <p class="text-muted small mt-3 mb-0" style="line-height: 1.4;">
                                <i class="bi bi-info-circle-fill me-1 small"></i> Ao suspender, tanto o portal público quanto o painel administrativo desta prefeitura ficarão inacessíveis.
                            </p>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4 text-center">
                            <button type="submit" name="salvar" class="btn btn-primary rounded-pill px-5 py-3 w-100 fw-bold shadow border-0" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                                <i class="bi bi-cloud-check me-2"></i> SALVAR ALTERAÇÕES
                            </button>
                            <a href="gerenciar_prefeituras.php" class="btn btn-link btn-sm text-decoration-none text-muted mt-3">Cancelar e Sair</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.form-control:focus, .form-select:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1); }
.badge { font-weight: 600; padding: 0.35rem 0.65rem; }
</style>

<?php include 'admin_footer.php'; ?>
