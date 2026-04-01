<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Bloqueia acesso se não for admin
if ($_SESSION['admin_user_perfil'] !== 'admin' && (int)$_SESSION['is_superadmin'] !== 1) {
    header("Location: index.php");
    exit;
}

$pref_id = $_SESSION['id_prefeitura'] ?? 0;

// Se for SuperAdmin e estiver sem contexto de prefeitura, bloqueia ou redireciona
if ($pref_id === 0 && (int)$_SESSION['is_superadmin'] !== 1) {
    $_SESSION['mensagem_erro'] = "Contexto de prefeitura não identificado.";
    header("Location: index.php");
    exit;
}

// Lógica para salvar as configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Salvar campos de texto comuns
    if (isset($_POST['config'])) {
        $configuracoes = $_POST['config'];
        foreach ($configuracoes as $chave => $valor) {
            $valor_limpo = trim($valor);
            
            $stmt_check = $pdo->prepare("SELECT id FROM configuracoes WHERE id_prefeitura = ? AND chave = ?");
            $stmt_check->execute([$pref_id, $chave]);
            $id_existente = $stmt_check->fetchColumn();

            if ($id_existente) {
                $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE id = ?");
                $stmt->execute([$valor_limpo, $id_existente]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO configuracoes (id_prefeitura, chave, valor) VALUES (?, ?, ?)");
                $stmt->execute([$pref_id, $chave, $valor_limpo]);
            }
        }
    }

    // 2. Lógica de Upload de Arquivos (PDFs)
    $diretorio_uploads = "../uploads/sic/pref_$pref_id/";
    if (!is_dir($diretorio_uploads)) {
        mkdir($diretorio_uploads, 0755, true);
    }

    $arquivos_perm = ['sic_formulario_pedido_pdf', 'sic_legislacao_federal_pdf', 'sic_legislacao_municipal_pdf'];
    foreach ($arquivos_perm as $chave_file) {
        if (isset($_FILES[$chave_file]) && $_FILES[$chave_file]['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES[$chave_file]['name'], PATHINFO_EXTENSION);
            if (strtolower($ext) === 'pdf') {
                $nome_arquivo = $chave_file . "_" . time() . ".pdf";
                $caminho_final = $diretorio_uploads . $nome_arquivo;
                
                if (move_uploaded_file($_FILES[$chave_file]['tmp_name'], $caminho_final)) {
                    // Salva o caminho no banco (caminho relativo para o front)
                    $caminho_banco = "uploads/sic/pref_$pref_id/" . $nome_arquivo;
                    
                    $stmt_check = $pdo->prepare("SELECT id FROM configuracoes WHERE id_prefeitura = ? AND chave = ?");
                    $stmt_check->execute([$pref_id, $chave_file]);
                    $id_existente = $stmt_check->fetchColumn();

                    if ($id_existente) {
                        $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE id = ?");
                        $stmt->execute([$caminho_banco, $id_existente]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO configuracoes (id_prefeitura, chave, valor) VALUES (?, ?, ?)");
                        $stmt->execute([$pref_id, $chave_file, $caminho_banco]);
                    }
                }
            }
        }
    }

    registrar_log($pdo, 'EDIÇÃO', 'configuracoes', "Atualizou as configurações completas do e-SIC para a prefeitura ID: $pref_id");

    $_SESSION['mensagem_sucesso'] = "Configurações do e-SIC atualizadas com sucesso!";
    header("Location: configuracoes_sic.php");
    exit;
}

// Busca as configurações atuais
$stmt = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE id_prefeitura = ? AND chave LIKE 'sic_%'");
$stmt->execute([$pref_id]);
$config_atuais = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Fallbacks
$config_atuais['sic_setor'] = $config_atuais['sic_setor'] ?? '';
$config_atuais['sic_solicitacoes_descricao'] = $config_atuais['sic_solicitacoes_descricao'] ?? 'Encaminhe aqui suas solicitações de acesso à informação e acompanhe pedidos em andamento.';
$config_atuais['sic_legislacao_descricao'] = $config_atuais['sic_legislacao_descricao'] ?? 'Conheça as leis que garantem ao cidadão o direito constitucional de acesso às informações públicas.';
$config_atuais['sic_legislacao_federal_titulo'] = $config_atuais['sic_legislacao_federal_titulo'] ?? 'Lei Federal nº 12.527/2011';
$config_atuais['sic_legislacao_municipal_titulo'] = $config_atuais['sic_legislacao_municipal_titulo'] ?? 'Decreto Municipal nº 44.385/2019';

$page_title_for_header = 'Configurações do e-SIC';
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-12">
            
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Configurações do e-SIC</h3>
                    <p class="text-muted small mb-0"><i class="bi bi-gear-fill me-1"></i> Gerencie o conteúdo de todos os cards da página pública do SIC.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show rounded-4 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['mensagem_sucesso']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['mensagem_sucesso']); ?>
            <?php endif; ?>

            <!-- Navegação por Abas -->
            <ul class="nav nav-pills mb-4 bg-white p-2 rounded-4 shadow-sm d-inline-flex" id="sicTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-3 fw-bold px-4" id="fisico-tab" data-bs-toggle="pill" data-bs-target="#fisico" type="button" role="tab">1. SIC Físico</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-3 fw-bold px-4" id="solicitacoes-tab" data-bs-toggle="pill" data-bs-target="#solicitacoes" type="button" role="tab">2. Solicitações</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-3 fw-bold px-4" id="legislacao-tab" data-bs-toggle="pill" data-bs-target="#legislacao" type="button" role="tab">3. Legislação</button>
                </li>
            </ul>

            <form method="POST" action="configuracoes_sic.php" enctype="multipart/form-data">
                <div class="tab-content" id="sicTabsContent">
                    
                    <!-- Aba 1: SIC Físico -->
                    <div class="tab-pane fade show active" id="fisico" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-bottom-0">
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-geo-alt-fill text-primary me-2"></i>Informações do SIC Físico</h6>
                            </div>
                            <div class="card-body p-4 bg-light bg-opacity-10">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Setor Responsável</label>
                                        <input type="text" class="form-control border-0 shadow-sm p-3 rounded-4" name="config[sic_setor]" value="<?php echo htmlspecialchars($config_atuais['sic_setor']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Nome do Responsável</label>
                                        <input type="text" class="form-control border-0 shadow-sm p-3 rounded-4" name="config[sic_responsavel]" value="<?php echo htmlspecialchars($config_atuais['sic_responsavel']); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Endereço Completo</label>
                                        <input type="text" class="form-control border-0 shadow-sm p-3 rounded-4" name="config[sic_endereco]" value="<?php echo htmlspecialchars($config_atuais['sic_endereco']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-muted text-uppercase">E-mail de Contato</label>
                                        <input type="email" class="form-control border-0 shadow-sm p-3 rounded-4" name="config[sic_email]" value="<?php echo htmlspecialchars($config_atuais['sic_email']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Telefone / Ramal</label>
                                        <input type="text" class="form-control border-0 shadow-sm p-3 rounded-4" name="config[sic_telefone]" value="<?php echo htmlspecialchars($config_atuais['sic_telefone']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Horário de Atendimento</label>
                                        <input type="text" class="form-control border-0 shadow-sm p-3 rounded-4" name="config[sic_horario]" value="<?php echo htmlspecialchars($config_atuais['sic_horario']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aba 2: Solicitações -->
                    <div class="tab-pane fade" id="solicitacoes" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-bottom-0">
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-pencil-square text-success me-2"></i>Conteúdo do Card de Solicitações</h6>
                            </div>
                            <div class="card-body p-4 bg-light bg-opacity-10">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Texto Descritivo (Topo)</label>
                                        <textarea class="form-control border-0 shadow-sm p-3 rounded-4" name="config[sic_solicitacoes_descricao]" rows="3"><?php echo htmlspecialchars($config_atuais['sic_solicitacoes_descricao']); ?></textarea>
                                    </div>
                                    <div class="col-md-6 border-top pt-4">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Formulário para Pedido Físico (PDF)</label>
                                        <input type="file" class="form-control border-0 shadow-sm p-3 rounded-4 mb-2" name="sic_formulario_pedido_pdf" accept=".pdf">
                                        <?php if(!empty($config_atuais['sic_formulario_pedido_pdf'])): ?>
                                            <div class="badge bg-success-subtle text-success p-2 rounded-3">
                                                <i class="bi bi-file-earmark-check me-1"></i> Arquivo atual: <?php echo basename($config_atuais['sic_formulario_pedido_pdf']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Aba 3: Legislação -->
                    <div class="tab-pane fade" id="legislacao" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-bottom-0">
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-journal-text text-warning me-2"></i>Conteúdo do Card de Legislação</h6>
                            </div>
                            <div class="card-body p-4 bg-light bg-opacity-10">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Texto Descritivo (Topo)</label>
                                        <textarea class="form-control border-0 shadow-sm p-3 rounded-4" name="config[sic_legislacao_descricao]" rows="3"><?php echo htmlspecialchars($config_atuais['sic_legislacao_descricao']); ?></textarea>
                                    </div>
                                    
                                    <!-- Bloco Lei Federal -->
                                    <div class="col-md-6 border-top pt-4">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Título: Legislação Federal</label>
                                        <input type="text" class="form-control border-0 shadow-sm p-3 rounded-4 mb-3" name="config[sic_legislacao_federal_titulo]" value="<?php echo htmlspecialchars($config_atuais['sic_legislacao_federal_titulo']); ?>">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Arquivo PDF (Opcional)</label>
                                        <input type="file" class="form-control border-0 shadow-sm p-3 rounded-4 mb-2" name="sic_legislacao_federal_pdf" accept=".pdf">
                                        <?php if(!empty($config_atuais['sic_legislacao_federal_pdf'])): ?>
                                            <div class="badge bg-success-subtle text-success p-2 rounded-3">
                                                <i class="bi bi-file-earmark-check me-1"></i> Arquivo: <?php echo basename($config_atuais['sic_legislacao_federal_pdf']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Bloco Lei Municipal -->
                                    <div class="col-md-6 border-top pt-4">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Título: Regulamentação Municipal</label>
                                        <input type="text" class="form-control border-0 shadow-sm p-3 rounded-4 mb-3" name="config[sic_legislacao_municipal_titulo]" value="<?php echo htmlspecialchars($config_atuais['sic_legislacao_municipal_titulo']); ?>">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Arquivo PDF (Opcional)</label>
                                        <input type="file" class="form-control border-0 shadow-sm p-3 rounded-4 mb-2" name="sic_legislacao_municipal_pdf" accept=".pdf">
                                        <?php if(!empty($config_atuais['sic_legislacao_municipal_pdf'])): ?>
                                            <div class="badge bg-success-subtle text-success p-2 rounded-3">
                                                <i class="bi bi-file-earmark-check me-1"></i> Arquivo: <?php echo basename($config_atuais['sic_legislacao_municipal_pdf']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-primary btn-lg shadow px-5 py-3 rounded-4 fw-bold">
                        <i class="bi bi-save me-2"></i>Salvar Todas as Configurações
                    </button>
                    <p class="text-muted small mt-2">Clique para salvar as alterações de todas as abas simultaneamente.</p>
                </div>
            </form>

        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
<style>
    .nav-pills .nav-link { color: #666; transition: all 0.3s; }
    .nav-pills .nav-link.active { background-color: var(--cor-principal, #0d6efd) !important; color: #fff !important; box-shadow: 0 4px 10px rgba(var(--cor-principal-rgb, 13, 110, 253), 0.3); }
    .nav-pills .nav-link:hover:not(.active) { background-color: #f8f9fa; color: #333; }
</style>
</body>
</html>