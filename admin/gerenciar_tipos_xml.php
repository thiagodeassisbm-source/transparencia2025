<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

if ($_SESSION['admin_user_perfil'] !== 'admin' && ($_SESSION['admin_user_nome'] ?? '') !== 'admin') {
    $_SESSION['mensagem_sucesso'] = 'Acesso negado.';
    header('Location: index.php');
    exit;
}

// Lógica para salvar (Adicionar/Editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nome_amigavel = trim($_POST['nome_amigavel'] ?? '');
    $tag_container = trim($_POST['tag_container'] ?? '');
    $tag_registro = trim($_POST['tag_registro'] ?? '');

    if ($nome_amigavel !== '' && $tag_container !== '' && $tag_registro !== '') {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE tipos_xml SET nome_amigavel=?, tag_container=?, tag_registro=? WHERE id=?');
            $stmt->execute([$nome_amigavel, $tag_container, $tag_registro, $id]);
            registrar_log($pdo, 'EDIÇÃO', 'tipos_xml', "Atualizou tipo de XML: $nome_amigavel (ID: $id).");
            $_SESSION['mensagem_sucesso'] = 'Tipo de XML atualizado com sucesso!';
        } else {
            $stmt = $pdo->prepare('INSERT INTO tipos_xml (nome_amigavel, tag_container, tag_registro) VALUES (?, ?, ?)');
            $stmt->execute([$nome_amigavel, $tag_container, $tag_registro]);
            $novo_id = (int) $pdo->lastInsertId();
            registrar_log($pdo, 'ADIÇÃO', 'tipos_xml', "Adicionou tipo de XML: $nome_amigavel (ID: $novo_id).");
            $_SESSION['mensagem_sucesso'] = 'Tipo de XML adicionado com sucesso!';
        }
    }
    header('Location: gerenciar_tipos_xml.php');
    exit;
}

// Lógica para deletar
if (isset($_GET['deletar'])) {
    $id = filter_input(INPUT_GET, 'deletar', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt_nm = $pdo->prepare('SELECT nome_amigavel FROM tipos_xml WHERE id = ?');
        $stmt_nm->execute([$id]);
        $nome_del = $stmt_nm->fetchColumn();
        $stmt = $pdo->prepare('DELETE FROM tipos_xml WHERE id = ?');
        $stmt->execute([$id]);
        registrar_log($pdo, 'EXCLUSÃO', 'tipos_xml', 'Excluiu tipo de XML: ' . ($nome_del ?: "ID $id") . " (ID: $id).");
        $_SESSION['mensagem_sucesso'] = 'Tipo de XML excluído com sucesso!';
    }
    header('Location: gerenciar_tipos_xml.php');
    exit;
}

$tipo_edicao = null;
if (isset($_GET['editar'])) {
    $id = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM tipos_xml WHERE id = ?');
        $stmt->execute([$id]);
        $tipo_edicao = $stmt->fetch();
    }
}

$tipos = $pdo->query('SELECT * FROM tipos_xml ORDER BY nome_amigavel ASC')->fetchAll(PDO::FETCH_ASSOC);

$page_title_for_header = 'Gerenciar Tipos de XML';
include 'admin_header.php';
?>

<style>
    .document-type-card { border-radius: 15px; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    .xml-tag-code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.8125rem;
        background: #f1f5f9;
        color: #b91c1c;
        padding: 0.2rem 0.45rem;
        border-radius: 6px;
    }
</style>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-12">

            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <h3 class="fw-bold text-dark mb-1">Gerenciar Tipos de XML</h3>
                    <p class="text-muted small mb-0"><i class="bi bi-filetype-xml me-1"></i> Defina as tags dos arquivos XML para reconhecimento e importação nas seções do portal.</p>
                </div>
            </div>

            <?php
            if (!empty($_SESSION['mensagem_sucesso'])) {
                echo '<div class="alert alert-success border-0 shadow-sm alert-dismissible fade show rounded-4" role="alert">'
                   . '<i class="bi bi-check-circle-fill me-2"></i>' . htmlspecialchars($_SESSION['mensagem_sucesso'])
                   . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div>';
                unset($_SESSION['mensagem_sucesso']);
            }
            ?>

            <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; border-radius: 15px;">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="me-4 d-none d-md-block">
                        <div class="bg-white bg-opacity-20 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                            <i class="bi bi-diagram-3 fs-2"></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1 text-white">Estrutura dos arquivos XML</h5>
                        <p class="mb-0 opacity-90 small">
                            A <strong>tag contêiner</strong> (plural) agrupa os registros no arquivo; a <strong>tag de registro</strong> (singular) identifica cada item importado. Ex.: <strong>Contratos</strong> / <strong>Contrato</strong>.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card document-type-card mb-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi <?php echo $tipo_edicao ? 'bi-pencil-square' : 'bi-plus-circle'; ?> me-2 text-success"></i>
                        <?php echo $tipo_edicao ? 'Editar tipo de XML' : 'Adicionar novo tipo'; ?>
                    </h6>
                </div>
                <div class="card-body bg-light bg-opacity-10 px-4 py-4">
                    <form method="POST" action="gerenciar_tipos_xml.php" class="row g-3">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($tipo_edicao['id'] ?? '')); ?>">
                        <div class="col-md-4">
                            <label for="nome_amigavel" class="form-label fw-bold small text-muted">Nome amigável</label>
                            <input type="text" class="form-control border-0 shadow-sm" id="nome_amigavel" name="nome_amigavel" value="<?php echo htmlspecialchars($tipo_edicao['nome_amigavel'] ?? ''); ?>" placeholder="Ex.: Licitações, Folha de Pagamento" required style="border-radius: 10px;">
                            <div class="form-text small">Nome exibido internamente na lista.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="tag_container" class="form-label fw-bold small text-muted">Tag contêiner (plural)</label>
                            <input type="text" class="form-control border-0 shadow-sm" id="tag_container" name="tag_container" value="<?php echo htmlspecialchars($tipo_edicao['tag_container'] ?? ''); ?>" placeholder="Ex.: Licitacoes, Contratos" required style="border-radius: 10px;">
                            <div class="form-text small">Elemento pai no XML (sem &lt;&gt;).</div>
                        </div>
                        <div class="col-md-4">
                            <label for="tag_registro" class="form-label fw-bold small text-muted">Tag de registro (singular)</label>
                            <input type="text" class="form-control border-0 shadow-sm" id="tag_registro" name="tag_registro" value="<?php echo htmlspecialchars($tipo_edicao['tag_registro'] ?? ''); ?>" placeholder="Ex.: Licitacao, Contrato" required style="border-radius: 10px;">
                            <div class="form-text small">Cada linha/item importado (sem &lt;&gt;).</div>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2 justify-content-end pt-2">
                            <?php if ($tipo_edicao): ?>
                                <a href="gerenciar_tipos_xml.php" class="btn btn-outline-secondary rounded-3 px-4">Cancelar edição</a>
                            <?php endif; ?>
                            <button type="submit" name="salvar" value="1" class="btn btn-success shadow-sm rounded-3 px-4"><i class="bi bi-save me-2"></i>Salvar tipo</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card document-type-card overflow-hidden mb-5">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-ul me-2 text-success"></i>Tipos de XML cadastrados</h6>
                    <span class="badge bg-light text-success rounded-pill px-3"><?php echo count($tipos); ?> total</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="p-3" style="width: 72px;">ID</th>
                                <th class="p-3">Nome amigável</th>
                                <th class="p-3">Tag contêiner</th>
                                <th class="p-3">Tag de registro</th>
                                <th class="p-3 text-center" style="width: 140px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tipos)): ?>
                                <tr>
                                    <td colspan="5" class="p-5 text-center text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                                        Nenhum tipo de XML cadastrado ainda.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tipos as $tipo): ?>
                                    <tr>
                                        <td class="p-3 text-muted">#<?php echo (int) $tipo['id']; ?></td>
                                        <td class="p-3 fw-bold text-dark"><?php echo htmlspecialchars($tipo['nome_amigavel']); ?></td>
                                        <td class="p-3"><span class="xml-tag-code">&lt;<?php echo htmlspecialchars($tipo['tag_container']); ?>&gt;</span></td>
                                        <td class="p-3"><span class="xml-tag-code">&lt;<?php echo htmlspecialchars($tipo['tag_registro']); ?>&gt;</span></td>
                                        <td class="p-3">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="?editar=<?php echo (int) $tipo['id']; ?>" class="btn btn-outline-primary btn-sm rounded-3 d-flex align-items-center justify-content-center shadow-none" style="width: 32px; height: 32px;" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?deletar=<?php echo (int) $tipo['id']; ?>" class="btn btn-outline-danger btn-sm rounded-3 d-flex align-items-center justify-content-center shadow-none" style="width: 32px; height: 32px;" title="Excluir" onclick="return confirm('Excluir este tipo de XML?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
