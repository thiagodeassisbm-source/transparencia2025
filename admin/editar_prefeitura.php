<?php
/** ARQUIVO ATUALIZADO EM 06/04/2026 - LARGURA TOTAL **/
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
    $contato_resp = $_POST['nome_responsavel'] ?? '';
    $contato_whats = $_POST['contato_whatsapp'] ?? '';
    $valor_mensal = $_POST['valor_mensalidade'] ?? 0;

    try {
        $stmt_update = $pdo->prepare("
            UPDATE prefeituras 
            SET nome = ?, slug = ?, dominio_customizado = ?, status = ?, 
                nome_responsavel = ?, contato_whatsapp = ?, valor_mensalidade = ?
            WHERE id = ?
        ");
        $stmt_update->execute([$nome, $slug, $dominio, $status, $contato_resp, $contato_whats, $valor_mensal, $id]);

        registrar_log($pdo, $_SESSION['id_usuario'], $_SESSION['nome_usuario'], 'EDIÇÃO', 'PREFEITURAS', "Atualizou dados da prefeitura ID $id: $nome", 0);
        
        $sucesso = "Dados atualizados com sucesso!";
        // Atualiza os dados locais para exibir no form
        $prefeitura = array_merge($prefeitura, [
            'nome' => $nome, 'slug' => $slug, 'dominio_customizado' => $dominio, 
            'status' => $status, 'nome_responsavel' => $contato_resp, 
            'contato_whatsapp' => $contato_whats, 'valor_mensalidade' => $valor_mensal
        ]);
    } catch (Exception $e) {
        $erro = "Erro ao atualizar: " . $e->getMessage();
    }
}

$page_title_for_header = 'Gestão de Prefeitura SaaS';
include 'admin_header.php';
?>

<div class="container-fluid py-4" style="padding-left: 30px; padding-right: 30px;">
    <!-- Card Informativo Premium (Padrão e-SIC/Ouvidoria) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="d-flex flex-column flex-md-row">
                <div class="bg-primary p-4 d-flex align-items-center justify-content-center text-white" style="min-width: 110px;">
                    <i class="bi bi-shield-lock display-5"></i>
                </div>
                <div class="p-4 flex-grow-1 bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1 text-primary">Configurações de Instância SaaS</h5>
                            <p class="text-muted small mb-0">Gestão global de <strong><?php echo htmlspecialchars($prefeitura['nome']); ?></strong> (ID: <?php echo $id; ?>).</p>
                        </div>
                        <a href="gerenciar_prefeituras.php" class="btn btn-outline-secondary border-2 btn-sm rounded-pill px-4 fw-bold">
                            <i class="bi bi-arrow-left me-2"></i> Voltar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($sucesso)): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 px-4 py-3">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $sucesso; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($erro)): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 px-4 py-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $erro; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="row g-4 text-start">
            <!-- Coluna Principal (Formulário) -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 py-3 ps-4">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-card-list me-2 text-primary"></i> 1. Dados Principais do Cadastro</h6>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Nome da Prefeitura (Exibição)</label>
                                <input type="text" name="nome" class="form-control rounded-3 py-2" value="<?php echo htmlspecialchars($prefeitura['nome']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Slug (URL do Portal)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted small">/portal/</span>
                                    <input type="text" name="slug" class="form-control rounded-end-3 py-2" value="<?php echo htmlspecialchars($prefeitura['slug']); ?>" required>
                                </div>
                            </div>
                            <div class="col-12 mt-4">
                                <label class="form-label small fw-bold text-secondary">Domínio Whitelabel (Customizado)</label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text bg-white text-primary border-end-0"><i class="bi bi-globe2"></i></span>
                                    <input type="text" name="dominio_customizado" class="form-control border-start-0 py-2" value="<?php echo htmlspecialchars($prefeitura['dominio_customizado']); ?>" placeholder="ex: portaldatransparencia.suacidade.go.gov.br">
                                </div>
                                
                                <div class="bg-light border-0 rounded-4 p-4 mt-3">
                                    <h6 class="small fw-bold text-dark mb-4 border-bottom pb-2"><i class="bi bi-info-circle me-2 text-primary"></i>Guia de Configuração de Domínio Próprio</h6>
                                    <div class="row g-4 text-center">
                                        <div class="col-md-4">
                                            <div class="badge bg-primary text-white rounded-circle shadow-sm mb-2" style="width: 32px; height: 32px; line-height: 24px;">1</div>
                                            <p class="mb-1 small fw-bold text-dark">Inserir Domínio</p>
                                            <p class="text-muted small mb-0" style="font-size: 0.7rem;">Informe o URL final que o cliente deseja utilizar.</p>
                                        </div>
                                        <div class="col-md-4 border-start">
                                            <div class="badge bg-primary text-white rounded-circle shadow-sm mb-2" style="width: 32px; height: 32px; line-height: 24px;">2</div>
                                            <p class="mb-1 small fw-bold text-dark">Apontamento CNAME</p>
                                            <p class="text-muted small mb-0" style="font-size: 0.7rem;">O técnico da cidade aponta o DNS para o nosso IP.</p>
                                        </div>
                                        <div class="col-md-4 border-start">
                                            <div class="badge bg-primary text-white rounded-circle shadow-sm mb-2" style="width: 32px; height: 32px; line-height: 24px;">3</div>
                                            <p class="mb-1 small fw-bold text-dark">Add no Servidor</p>
                                            <p class="text-muted small mb-0" style="font-size: 0.7rem;">Adicione como 'Alias' ou 'Domínio Estacionado'.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna Lateral (Informações de Gestão) -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 py-3 ps-4">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-activity me-2 text-primary"></i> 2. Status do Canal</h6>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <label class="form-label small fw-bold text-secondary">Disponibilidade</label>
                        <select name="status" class="form-select rounded-3 py-2 mb-3">
                            <option value="ativo" <?php echo $prefeitura['status'] == 'ativo' ? 'selected' : ''; ?>>Ativo (Visível)</option>
                            <option value="inativo" <?php echo $prefeitura['status'] == 'inativo' ? 'selected' : ''; ?>>Suspenso / Inativo</option>
                            <option value="manutencao" <?php echo $prefeitura['status'] == 'manutencao' ? 'selected' : ''; ?>>Manutenção Interna</option>
                        </select>
                        <p class="text-muted" style="font-size: 0.75rem; line-height: 1.4;">
                            <i class="bi bi-info-circle-fill me-1 small"></i> Ao inativar, a prefeitura perde acesso ao painel e o portal público fica fora do ar.
                        </p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-0 py-3 ps-4">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-briefcase me-2 text-primary"></i> 3. Gestão & Comercial</h6>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <div class="mb-3 text-start">
                            <label class="form-label small fw-bold text-secondary">Responsável Contratual</label>
                            <input type="text" name="nome_responsavel" class="form-control rounded-3 py-2 text-start" value="<?php echo htmlspecialchars($prefeitura['nome_responsavel'] ?? ''); ?>">
                        </div>
                        <div class="mb-3 text-start">
                            <label class="form-label small fw-bold text-secondary">WhatsApp / Contato</label>
                            <input type="text" name="contato_whatsapp" class="form-control rounded-3 py-2 text-start" value="<?php echo htmlspecialchars($prefeitura['contato_whatsapp'] ?? ''); ?>">
                        </div>
                        <div class="mb-0 text-start">
                            <label class="form-label small fw-bold text-secondary">Fee Mensal (R$)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light small">R$</span>
                                <input type="number" step="0.01" name="valor_mensalidade" class="form-control rounded-end-3 py-2 text-start" value="<?php echo htmlspecialchars($prefeitura['valor_mensalidade'] ?? 0); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="col-12 mt-2 text-end pb-5">
                <hr class="my-4 opacity-50">
                <button type="submit" name="salvar" class="btn btn-primary rounded-pill px-5 py-3 shadow fw-bold border-0" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <i class="bi bi-save2 me-2"></i> SALVAR ALTERAÇÕES
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.form-control:focus, .form-select:focus {
    border-color: #36c0d3;
    box-shadow: 0 0 0 0.25rem rgba(54, 192, 211, 0.1);
}
.input-group-text { border-radius: 8px 0 0 8px !important; }
.form-control { border-radius: 8px !important; }
.input-group > .form-control { border-radius: 0 8px 8px 0 !important; }
</style>

<?php include 'admin_footer.php'; ?>
