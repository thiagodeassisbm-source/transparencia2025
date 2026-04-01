<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

if ($_SESSION['admin_user_perfil'] !== 'admin' && (int)($_SESSION['is_superadmin'] ?? 0) !== 1) {
    header("Location: index.php");
    exit;
}

$pref_id = (int)($_SESSION['id_prefeitura'] ?? 0);
if ($pref_id === 0 && (int)($_SESSION['is_superadmin'] ?? 0) !== 1) {
    $_SESSION['mensagem_erro'] = "Contexto de prefeitura não identificado.";
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['config'])) {
    $configuracoes = $_POST['config'];

    foreach ($configuracoes as $chave => $valor) {
        $valor_limpo = is_string($valor) ? trim($valor) : $valor;

        $stmt_check = $pdo->prepare("SELECT id FROM configuracoes WHERE id_prefeitura = ? AND chave = ?");
        $stmt_check->execute([$pref_id, $chave]);
        $exists = $stmt_check->fetchColumn();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE id_prefeitura = ? AND chave = ?");
            $stmt->execute([$valor_limpo, $pref_id, $chave]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor, id_prefeitura) VALUES (?, ?, ?)");
            $stmt->execute([$chave, $valor_limpo, $pref_id]);
        }
    }

    registrar_log($pdo, 'EDIÇÃO', 'configuracoes', "Atualizou as configurações da Ouvidoria (todas as abas) para a prefeitura ID: $pref_id");

    $_SESSION['mensagem_sucesso'] = "Configurações da Ouvidoria salvas com sucesso!";
    header("Location: config_ouvidoria.php");
    exit;
}

$stmt = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE id_prefeitura = ? AND chave LIKE 'ouvidoria_%'");
$stmt->execute([$pref_id]);
$config_atuais = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$config_atuais['ouvidoria_setor'] = $config_atuais['ouvidoria_setor'] ?? '';
$config_atuais['ouvidoria_endereco'] = $config_atuais['ouvidoria_endereco'] ?? '';
$config_atuais['ouvidoria_email'] = $config_atuais['ouvidoria_email'] ?? '';
$config_atuais['ouvidoria_telefone'] = $config_atuais['ouvidoria_telefone'] ?? '';
$config_atuais['ouvidoria_pagina_intro'] = $config_atuais['ouvidoria_pagina_intro'] ?? 'A ouvidoria é o seu canal direto com a gestão municipal. Aqui você pode registrar elogios, sugestões, reclamações, solicitações ou denúncias de forma segura e transparente.';
$config_atuais['ouvidoria_manifestar_descricao'] = $config_atuais['ouvidoria_manifestar_descricao'] ?? '';
$config_atuais['ouvidoria_consultar_descricao'] = $config_atuais['ouvidoria_consultar_descricao'] ?? 'Acompanhe sua manifestação digitando o número do protocolo abaixo.';
$config_atuais['ouvidoria_consultar_botao_label'] = $config_atuais['ouvidoria_consultar_botao_label'] ?? 'Acompanhar Manifestação';
$config_atuais['ouvidoria_relatorio_descricao'] = $config_atuais['ouvidoria_relatorio_descricao'] ?? '';
$config_atuais['ouvidoria_relatorio_ver_mais_texto'] = $config_atuais['ouvidoria_relatorio_ver_mais_texto'] ?? 'Ver Relatório Completo Detalhado';
$config_atuais['ouvidoria_relatorio_ver_mais_link'] = $config_atuais['ouvidoria_relatorio_ver_mais_link'] ?? '';

$tipos_manifestar = [
    ['slug' => 'sugestao', 'tipo' => 'Sugestão', 'titulo' => 'Sugestão'],
    ['slug' => 'elogio', 'tipo' => 'Elogio', 'titulo' => 'Elogio'],
    ['slug' => 'solicitacao', 'tipo' => 'Solicitação', 'titulo' => 'Solicitação'],
    ['slug' => 'reclamacao', 'tipo' => 'Reclamação', 'titulo' => 'Reclamação'],
    ['slug' => 'denuncia', 'tipo' => 'Denúncia', 'titulo' => 'Denúncia'],
];

foreach ($tipos_manifestar as $tm) {
    $s = $tm['slug'];
    $config_atuais["ouvidoria_btn_{$s}_label"] = $config_atuais["ouvidoria_btn_{$s}_label"] ?? '';
    $config_atuais["ouvidoria_btn_{$s}_link"] = $config_atuais["ouvidoria_btn_{$s}_link"] ?? '';
}

$page_title_for_header = 'Configurações da Ouvidoria';
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-12">

            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Configurações da Ouvidoria</h3>
                    <p class="text-muted small mb-0"><i class="bi bi-gear-fill me-1"></i> Gerencie o conteúdo de todos os cards da página pública da Ouvidoria.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show rounded-4 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['mensagem_sucesso']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['mensagem_sucesso']); ?>
            <?php endif; ?>

            <ul class="nav nav-pills mb-4 bg-white p-2 rounded-4 shadow-sm d-inline-flex flex-wrap" id="ouvidoriaTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-3 fw-bold px-3 px-md-4" id="atendimento-tab" data-bs-toggle="pill" data-bs-target="#pane-atendimento" type="button" role="tab">1. Atendimento</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-3 fw-bold px-3 px-md-4" id="manifestar-tab" data-bs-toggle="pill" data-bs-target="#pane-manifestar" type="button" role="tab">2. Manifestar</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-3 fw-bold px-3 px-md-4" id="consultar-tab" data-bs-toggle="pill" data-bs-target="#pane-consultar" type="button" role="tab">3. Consultar</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-3 fw-bold px-3 px-md-4" id="relatorio-tab" data-bs-toggle="pill" data-bs-target="#pane-relatorio" type="button" role="tab">4. Relatório</button>
                </li>
            </ul>

            <form method="POST" action="config_ouvidoria.php" id="form-ouvidoria-config">
                <div class="tab-content" id="ouvidoriaTabsContent">

                    <div class="tab-pane fade show active" id="pane-atendimento" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-bottom-0">
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-headset text-primary me-2"></i>Atendimento e texto da página</h6>
                            </div>
                            <div class="card-body p-4 bg-light bg-opacity-10">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Texto introdutório (abaixo do título “Ouvidoria Municipal”)</label>
                                        <textarea class="form-control border-0 shadow-sm p-3 rounded-4" name="config[ouvidoria_pagina_intro]" rows="3"><?php echo htmlspecialchars($config_atuais['ouvidoria_pagina_intro']); ?></textarea>
                                    </div>
                                    <div class="col-12 border-top pt-4">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Setor / Responsável</label>
                                        <input type="text" class="form-control border-0 shadow-sm p-3 rounded-4" name="config[ouvidoria_setor]" value="<?php echo htmlspecialchars($config_atuais['ouvidoria_setor']); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Endereço</label>
                                        <input type="text" class="form-control border-0 shadow-sm p-3 rounded-4" name="config[ouvidoria_endereco]" value="<?php echo htmlspecialchars($config_atuais['ouvidoria_endereco']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted text-uppercase">E-mail</label>
                                        <input type="email" class="form-control border-0 shadow-sm p-3 rounded-4" name="config[ouvidoria_email]" value="<?php echo htmlspecialchars($config_atuais['ouvidoria_email']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Telefone</label>
                                        <input type="text" class="form-control border-0 shadow-sm p-3 rounded-4" name="config[ouvidoria_telefone]" value="<?php echo htmlspecialchars($config_atuais['ouvidoria_telefone']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pane-manifestar" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-bottom-0">
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-pencil-square text-success me-2"></i>Manifestar</h6>
                            </div>
                            <div class="card-body p-4 bg-light bg-opacity-10">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Texto opcional (acima dos botões)</label>
                                        <textarea class="form-control border-0 shadow-sm p-3 rounded-4" name="config[ouvidoria_manifestar_descricao]" rows="3" placeholder="Deixe em branco para não exibir texto extra."><?php echo htmlspecialchars($config_atuais['ouvidoria_manifestar_descricao']); ?></textarea>
                                    </div>
                                    <div class="col-12 border-top pt-4">
                                        <p class="fw-bold small text-dark mb-3">Botões por tipo de manifestação</p>
                                        <p class="text-muted small mb-3">Defina o <strong>texto</strong> do botão e, se quiser, um <strong>link personalizado</strong>. Se o link ficar vazio, o sistema usa o formulário padrão deste portal.</p>
                                        <div class="table-responsive">
                                            <table class="table align-middle mb-0 bg-white rounded-3 overflow-hidden shadow-sm">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="small text-muted text-uppercase">Tipo</th>
                                                        <th class="small text-muted text-uppercase">Texto do botão</th>
                                                        <th class="small text-muted text-uppercase">Link (opcional)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($tipos_manifestar as $tm):
                                                        $s = $tm['slug'];
                                                        $lk = "ouvidoria_btn_{$s}_label";
                                                        $ln = "ouvidoria_btn_{$s}_link";
                                                    ?>
                                                    <tr>
                                                        <td class="fw-semibold text-nowrap"><?php echo htmlspecialchars($tm['titulo']); ?></td>
                                                        <td>
                                                            <input type="text" class="form-control form-control-sm rounded-3" name="config[<?php echo $lk; ?>]" value="<?php echo htmlspecialchars($config_atuais[$lk]); ?>" placeholder="<?php echo htmlspecialchars($tm['titulo']); ?>">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control form-control-sm rounded-3" name="config[<?php echo $ln; ?>]" value="<?php echo htmlspecialchars($config_atuais[$ln]); ?>" placeholder="https://... ou portal/.../abrir_manifestacao.php">
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pane-consultar" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-bottom-0">
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-search text-info me-2"></i>Consultar protocolo</h6>
                            </div>
                            <div class="card-body p-4 bg-light bg-opacity-10">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Texto instrucional (acima do campo de protocolo)</label>
                                        <textarea class="form-control border-0 shadow-sm p-3 rounded-4" name="config[ouvidoria_consultar_descricao]" rows="3"><?php echo htmlspecialchars($config_atuais['ouvidoria_consultar_descricao']); ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Texto do botão enviar</label>
                                        <input type="text" class="form-control border-0 shadow-sm p-3 rounded-4" name="config[ouvidoria_consultar_botao_label]" value="<?php echo htmlspecialchars($config_atuais['ouvidoria_consultar_botao_label']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="pane-relatorio" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-bottom-0">
                                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-bar-chart-fill text-dark me-2"></i>Relatório em tempo real</h6>
                            </div>
                            <div class="card-body p-4 bg-light bg-opacity-10">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Texto opcional (acima das barras)</label>
                                        <textarea class="form-control border-0 shadow-sm p-3 rounded-4" name="config[ouvidoria_relatorio_descricao]" rows="3" placeholder="Deixe em branco para não exibir."><?php echo htmlspecialchars($config_atuais['ouvidoria_relatorio_descricao']); ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Texto do link “Ver mais”</label>
                                        <input type="text" class="form-control border-0 shadow-sm p-3 rounded-4" name="config[ouvidoria_relatorio_ver_mais_texto]" value="<?php echo htmlspecialchars($config_atuais['ouvidoria_relatorio_ver_mais_texto']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Link “Ver mais” (opcional)</label>
                                        <input type="text" class="form-control border-0 shadow-sm p-3 rounded-4" name="config[ouvidoria_relatorio_ver_mais_link]" value="<?php echo htmlspecialchars($config_atuais['ouvidoria_relatorio_ver_mais_link']); ?>" placeholder="Vazio = relatório estatístico do portal">
                                        <p class="text-muted small mt-2 mb-0">URL completa ou caminho a partir da raiz do site. Vazio mantém o relatório interno.</p>
                                    </div>
                                    <div class="col-12">
                                        <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i> Os totais nas barras são calculados automaticamente.</p>
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
                    <p class="text-muted small mt-2 mb-0">Clique para salvar as alterações de todas as abas simultaneamente.</p>
                </div>
            </form>

        </div>
    </div>
</div>

<style>
    .nav-pills .nav-link { color: #666; transition: all 0.3s; }
    .nav-pills .nav-link.active { background-color: var(--primary-color, #36c0d3) !important; color: #fff !important; box-shadow: 0 4px 10px rgba(54, 192, 211, 0.35); }
    .nav-pills .nav-link:hover:not(.active) { background-color: #f8f9fa; color: #333; }
</style>
<?php include 'admin_footer.php'; ?>
