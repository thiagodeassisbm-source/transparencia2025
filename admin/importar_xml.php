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
    .import-xml-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 10050;
        background: rgba(15, 23, 42, 0.48);
        align-items: center;
        justify-content: center;
        padding: 1rem;
        backdrop-filter: blur(2px);
    }
    .import-xml-overlay.is-visible {
        display: flex;
    }
    .import-xml-overlay-card {
        width: 100%;
        max-width: 420px;
        background: linear-gradient(180deg, #f8fafc 0%, #fff 45%);
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        padding: 1.75rem;
        text-align: center;
    }
    .import-xml-overlay .progress {
        border-radius: 999px;
        overflow: hidden;
        background: #e2e8f0;
    }
    .import-xml-overlay .progress-bar {
        transition: width 0.2s ease-out;
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

            <form id="form-importar-xml" method="POST" action="importar_xml.php" enctype="multipart/form-data" class="<?php echo ($sem_contexto_super || empty($secoes)) ? 'opacity-50' : ''; ?>">

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

            <div id="importXmlOverlay" class="import-xml-overlay" role="dialog" aria-modal="true" aria-labelledby="importXmlOverlayTitle" aria-busy="true">
                <div class="import-xml-overlay-card">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary" style="width: 56px; height: 56px;">
                            <i class="bi bi-cloud-arrow-up fs-3"></i>
                        </span>
                    </div>
                    <h5 class="fw-bold mb-1" id="importXmlOverlayTitle">Importando XML</h5>
                    <p class="text-muted small mb-3 mb-md-4" id="importXmlStatusMsg">Preparando envio...</p>
                    <div class="progress mb-2" style="height: 14px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" id="importXmlProgressBar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p class="mb-0 fw-semibold" style="font-size: 1.1rem; color: #0f172a;" id="importXmlProgressPct">0%</p>
                </div>
            </div>

        </div>
    </div>
</div>

<?php if (!$sem_contexto_super && !empty($secoes)): ?>
<script>
(function () {
    var form = document.getElementById('form-importar-xml');
    var overlay = document.getElementById('importXmlOverlay');
    if (!form || !overlay) return;

    function formatBytes(n) {
        if (n < 1024) return n + ' B';
        if (n < 1048576) return (n / 1024).toFixed(1) + ' KB';
        return (n / 1048576).toFixed(1) + ' MB';
    }

    function setProgress(pct) {
        pct = Math.max(0, Math.min(100, Math.round(pct)));
        var bar = document.getElementById('importXmlProgressBar');
        var label = document.getElementById('importXmlProgressPct');
        if (bar) {
            bar.style.width = pct + '%';
            bar.setAttribute('aria-valuenow', String(pct));
        }
        if (label) label.textContent = pct + '%';
    }

    function setStatus(msg) {
        var el = document.getElementById('importXmlStatusMsg');
        if (el) el.textContent = msg;
    }

    function showOverlay() {
        overlay.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
    }

    function hideOverlay() {
        overlay.classList.remove('is-visible');
        document.body.style.overflow = '';
    }

    form.addEventListener('submit', function (e) {
        if (!form.checkValidity()) return;
        e.preventDefault();

        setProgress(0);
        setStatus('Preparando envio...');
        showOverlay();

        var fd = new FormData(form);
        var xhr = new XMLHttpRequest();
        var serverSimTimer = null;

        xhr.upload.addEventListener('progress', function (ev) {
            if (ev.lengthComputable && ev.total > 0) {
                var uploadPct = (ev.loaded / ev.total) * 78;
                setProgress(uploadPct);
                setStatus('Enviando arquivo... ' + formatBytes(ev.loaded) + ' de ' + formatBytes(ev.total));
            } else {
                setStatus('Enviando arquivo...');
                setProgress(15);
            }
        });

        xhr.upload.addEventListener('load', function () {
            setProgress(80);
            setStatus('Processando XML no servidor...');
            var fake = 80;
            if (serverSimTimer) clearInterval(serverSimTimer);
            serverSimTimer = setInterval(function () {
                if (fake < 96) {
                    fake += Math.random() * 2.2 + 0.3;
                    if (fake > 96) fake = 96;
                    setProgress(fake);
                }
            }, 350);
        });

        xhr.onload = function () {
            if (serverSimTimer) clearInterval(serverSimTimer);
            var url = xhr.responseURL || '';

            if (url.indexOf('mapear_xml.php') !== -1) {
                setProgress(100);
                setStatus('Concluindo...');
                window.location.href = url;
                return;
            }

            setProgress(100);
            hideOverlay();
            try {
                document.open('text/html', 'replace');
                document.write(xhr.responseText);
                document.close();
            } catch (err) {
                window.location.reload();
            }
        };

        xhr.onerror = function () {
            if (serverSimTimer) clearInterval(serverSimTimer);
            hideOverlay();
            alert('Não foi possível concluir o envio. Verifique sua conexão e tente novamente.');
        };

        xhr.open('POST', form.getAttribute('action') || 'importar_xml.php');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(fd);
    });
})();
</script>
<?php endif; ?>

<?php include 'admin_footer.php'; ?>
