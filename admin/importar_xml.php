<?php
require_once 'auth_check.php';
require_once '../conexao.php';

if ($_SESSION['admin_user_perfil'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$is_superadmin = isset($_SESSION['is_superadmin']) && (int) $_SESSION['is_superadmin'] === 1;
$pref_id = (int) ($_SESSION['id_prefeitura'] ?? 0);

if (!$is_superadmin && $pref_id <= 0) {
    $_SESSION['mensagem_erro'] = 'Contexto de prefeitura não identificado.';
    header('Location: index.php');
    exit;
}

$sem_contexto_super = $is_superadmin && $pref_id <= 0;

$secoes = [];
$tipos_documento = [];
$categorias = [];

if ($pref_id > 0) {
    $stmt = $pdo->prepare('SELECT id, nome FROM portais WHERE id_prefeitura = ? ORDER BY nome ASC');
    $stmt->execute([$pref_id]);
    $secoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt_t = $pdo->prepare('SELECT id, nome FROM tipos_documento WHERE id_prefeitura = ? OR id_prefeitura IS NULL ORDER BY nome ASC');
    $stmt_t->execute([$pref_id]);
    $tipos_documento = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

    $stmt_c = $pdo->prepare('SELECT id, nome FROM categorias WHERE id_prefeitura = ? ORDER BY ordem ASC');
    $stmt_c->execute([$pref_id]);
    $categorias = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xml_file'])) {
    if ($sem_contexto_super) {
        $erro = 'Entre em uma prefeitura pela Central do Super Admin (Gerenciar Prefeituras → Entrar) antes de importar.';
    } elseif ($_FILES['xml_file']['error'] === UPLOAD_ERR_OK) {
        $id_portal_post = filter_input(INPUT_POST, 'id_portal', FILTER_VALIDATE_INT);
        if (!$id_portal_post) {
            $erro = 'Selecione a seção de destino dos dados.';
        } else {
            $stmt_ok = $pdo->prepare('SELECT id FROM portais WHERE id = ? AND id_prefeitura = ?');
            $stmt_ok->execute([$id_portal_post, $pref_id]);
            if (!$stmt_ok->fetch()) {
                $erro = 'A seção escolhida não pertence à sua prefeitura.';
            } else {
                $file_tmp_path = $_FILES['xml_file']['tmp_name'];
                $xml_content = file_get_contents($file_tmp_path);

                $_SESSION['import_xml'] = [
                    'id_prefeitura' => $pref_id,
                    'id_portal' => $id_portal_post,
                    'metadados' => [
                        'exercicio' => $_POST['exercicio'] ?? '',
                        'unidade_gestora' => $_POST['unidade_gestora'] ?? '',
                        'periodicidade' => $_POST['periodicidade'] ?? '',
                        'mes' => $_POST['mes'] ?? '',
                        'id_tipo_documento' => $_POST['id_tipo_documento'] ?? null,
                        'id_classificacao' => filter_input(INPUT_POST, 'id_classificacao', FILTER_VALIDATE_INT),
                    ],
                    'xml_content' => $xml_content,
                ];

                header('Location: mapear_xml.php');
                exit;
            }
        }
    } else {
        $erro = 'Ocorreu um erro no upload do arquivo.';
    }
}

$page_title_for_header = 'Importar XML';
include 'admin_header.php';
?>

<style>
    .import-xml-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
    }
</style>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-12">

            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <h3 class="fw-bold text-dark mb-1">Importar XML</h3>
                    <p class="text-muted small mb-0"><span class="badge rounded-pill bg-success bg-opacity-10 text-success me-1">Passo 1/3</span> Informações da publicação, upload do XML e mapeamento dos campos.</p>
                </div>
            </div>

            <?php if (!empty($_SESSION['mensagem_erro'])): ?>
                <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show rounded-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($_SESSION['mensagem_erro']); unset($_SESSION['mensagem_erro']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['mensagem_sucesso'])): ?>
                <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show rounded-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($_SESSION['mensagem_sucesso']); unset($_SESSION['mensagem_sucesso']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>
            <?php if ($erro): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-4"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <?php if ($sem_contexto_super): ?>
            <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4">
                <strong>Prefeitura não selecionada.</strong> Na Central do Super Admin, abra <strong>Gerenciar Prefeituras</strong> e use <strong>Entrar</strong> no município desejado; em seguida volte a <strong>Importar XML</strong>.
            </div>
            <?php endif; ?>

            <?php if ($pref_id > 0): ?>
            <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; border-radius: 15px;">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="me-4 d-none d-md-block">
                        <div class="bg-white bg-opacity-20 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                            <i class="bi bi-filetype-xml fs-2"></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1 text-white">Importação por prefeitura</h5>
                        <p class="mb-0 opacity-90 small">Somente seções, tipos de documento e categorias <strong>desta prefeitura</strong> aparecem nas listas. O arquivo será associado à seção que você escolher.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" action="importar_xml.php" enctype="multipart/form-data" class="<?php echo ($sem_contexto_super || empty($secoes)) ? 'opacity-50' : ''; ?>">

                <div class="card import-xml-card mb-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-file-earmark-text me-2 text-success"></i>Informações gerais da publicação</h6>
                    </div>
                    <div class="card-body bg-light bg-opacity-10 px-4 py-4">
                        <?php if ($pref_id > 0): ?>
                        <p class="text-muted small mb-4 mb-md-3">Preencha os metadados que serão gravados em cada registro importado.</p>
                        <?php endif; ?>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="exercicio" class="form-label fw-bold small text-muted">Exercício</label>
                                <select class="form-select border-0 shadow-sm" id="exercicio" name="exercicio" required style="border-radius: 10px;"><?php for ($ano = 2050; $ano >= 2020; $ano--): ?><option value="<?php echo $ano; ?>" <?php echo (date('Y') == $ano) ? 'selected' : ''; ?>><?php echo $ano; ?></option><?php endfor; ?></select>
                            </div>
                            <div class="col-md-3">
                                <label for="unidade_gestora" class="form-label fw-bold small text-muted">Unidade gestora</label>
                                <select class="form-select border-0 shadow-sm" id="unidade_gestora" name="unidade_gestora" required style="border-radius: 10px;"><option>Prefeitura Municipal</option><option>Fundo Municipal de Saúde</option></select>
                            </div>
                            <div class="col-md-3">
                                <label for="periodicidade" class="form-label fw-bold small text-muted">Periodicidade</label>
                                <select class="form-select border-0 shadow-sm" id="periodicidade" name="periodicidade" required style="border-radius: 10px;"><option>Não se Aplica</option><option>Mensal</option><option>Bimestral</option><option>Trimestral</option><option>Quadrimestral</option><option>Semestral</option><option>Anual</option><option>Quadrienal</option></select>
                            </div>
                            <div class="col-md-3">
                                <label for="mes" class="form-label fw-bold small text-muted">Mês de referência</label>
                                <select class="form-select border-0 shadow-sm" id="mes" name="mes" required style="border-radius: 10px;"><option>Não se Aplica</option><option>Janeiro</option><option>Fevereiro</option><option>Março</option><option>Abril</option><option>Maio</option><option>Junho</option><option>Julho</option><option>Agosto</option><option>Setembro</option><option>Outubro</option><option>Novembro</option><option>Dezembro</option></select>
                            </div>
                            <div class="col-md-6">
                                <label for="id_tipo_documento" class="form-label fw-bold small text-muted">Tipo de documento</label>
                                <select class="form-select border-0 shadow-sm" id="id_tipo_documento" name="id_tipo_documento" required style="border-radius: 10px;"><option value="">-- Selecione --</option><?php foreach ($tipos_documento as $tipo): ?><option value="<?php echo (int) $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nome']); ?></option><?php endforeach; ?></select>
                            </div>
                            <div class="col-md-6">
                                <label for="id_classificacao" class="form-label fw-bold small text-muted">Classificação (categoria)</label>
                                <select class="form-select border-0 shadow-sm" id="id_classificacao" name="id_classificacao" style="border-radius: 10px;"><option value="">-- Nenhuma --</option><?php foreach ($categorias as $categoria): ?><option value="<?php echo (int) $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option><?php endforeach; ?></select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card import-xml-card mb-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-cloud-arrow-up me-2 text-success"></i>Arquivos</h6>
                    </div>
                    <div class="card-body bg-light bg-opacity-10 px-4 py-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="id_portal" class="form-label fw-bold small text-muted">Seção de destino dos dados</label>
                                <select class="form-select border-0 shadow-sm" id="id_portal" name="id_portal" required <?php echo ($sem_contexto_super || empty($secoes)) ? 'disabled' : ''; ?> style="border-radius: 10px;"><option value="">-- Escolha uma seção --</option><?php foreach ($secoes as $secao): ?><option value="<?php echo (int) $secao['id']; ?>"><?php echo htmlspecialchars($secao['nome']); ?></option><?php endforeach; ?></select>
                            </div>
                            <div class="col-md-6">
                                <label for="xml_file" class="form-label fw-bold small text-muted">Arquivo de dados (XML)</label>
                                <input class="form-control border-0 shadow-sm" type="file" id="xml_file" name="xml_file" accept=".xml,text/xml" required <?php echo ($sem_contexto_super || empty($secoes)) ? 'disabled' : ''; ?> style="border-radius: 10px;">
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-3 mt-4 pt-2 border-top border-light-subtle">
                            <button type="submit" class="btn btn-primary rounded-3 px-4 shadow-sm" <?php echo ($sem_contexto_super || empty($secoes)) ? 'disabled' : ''; ?>><i class="bi bi-arrow-right-circle-fill me-2"></i>Continuar para mapeamento</button>
                            <?php if ($pref_id > 0 && empty($secoes)): ?>
                                <p class="text-danger small mb-0">Não há seções cadastradas para esta prefeitura. Crie uma em <strong>Criar Seções</strong> antes de importar.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
