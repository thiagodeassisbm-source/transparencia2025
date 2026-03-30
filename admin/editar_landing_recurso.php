<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Bloqueia se não for superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$recurso = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM landing_recursos WHERE id = ?");
    $stmt->execute([$id]);
    $recurso = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_SPECIAL_CHARS);
    $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_SPECIAL_CHARS);
    $icone = filter_input(INPUT_POST, 'icone', FILTER_SANITIZE_SPECIAL_CHARS);
    $ordem = filter_input(INPUT_POST, 'ordem', FILTER_VALIDATE_INT);

    if ($id) {
        $stmt_upd = $pdo->prepare("UPDATE landing_recursos SET titulo = ?, descricao = ?, icone = ?, ordem = ? WHERE id = ?");
        $stmt_upd->execute([$titulo, $descricao, $icone, $ordem, $id]);
        registrar_log($pdo, 'SUPERADMIN', 'EDITAR_LANDING_RECURSO', "Recurso $titulo atualizado na landing page.");
    } else {
        $stmt_ins = $pdo->prepare("INSERT INTO landing_recursos (titulo, descricao, icone, ordem) VALUES (?, ?, ?, ?)");
        $stmt_ins->execute([$titulo, $descricao, $icone, $ordem]);
        registrar_log($pdo, 'SUPERADMIN', 'CRIAR_LANDING_RECURSO', "Novo recurso $titulo adicionado à landing page.");
    }

    header("Location: gerenciar_landing_recursos.php");
    exit;
}

$page_title_for_header = ($id ? 'Editar' : 'Novo') . ' Recurso da Landing Page';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-dark text-white p-4 rounded-top-4">
                    <h5 class="mb-0 fw-bold"><i class="bi <?php echo $id ? 'bi-pencil-square' : 'bi-plus-circle'; ?> me-2"></i> <?php echo $id ? 'Editar Recurso' : 'Novo Recurso'; ?></h5>
                    <p class="mb-0 text-white-50 opacity-75 small">Este item aparecerá na seção "Recursos do Sistema" da página principal.</p>
                </div>
                <div class="card-body p-4">
                    <form method="POST" class="row g-4">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-muted">Título do Recurso</label>
                            <input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($recurso['titulo'] ?? ''); ?>" required placeholder="Ex: Dados Abertos">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Ordem de Exibição</label>
                            <input type="number" name="ordem" class="form-control" value="<?php echo $recurso['ordem'] ?? '1'; ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Ícone (Classe Bootstrap Icons)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi <?php echo htmlspecialchars($recurso['icone'] ?? 'bi-info-circle'); ?>" id="previewIcon"></i></span>
                                <input type="text" name="icone" id="iconInput" class="form-control" value="<?php echo htmlspecialchars($recurso['icone'] ?? 'bi-info-circle'); ?>" required placeholder="Ex: bi-clipboard-data">
                            </div>
                            <small class="text-muted" style="font-size: 0.65rem;">Veja os ícones em: <a href="https://icons.getbootstrap.com/" target="_blank">icons.getbootstrap.com</a> (copie apenas a classe, ex: bi-star)</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Descrição / Conteúdo</label>
                            <textarea name="descricao" class="form-control" rows="4" required placeholder="Digite uma breve descrição sobre o recurso..."><?php echo htmlspecialchars($recurso['descricao'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-12 mt-5 text-end d-flex justify-content-between align-items-center">
                            <a href="gerenciar_landing_recursos.php" class="btn btn-outline-secondary rounded-pill px-4">Cancelar</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow">
                                <i class="bi bi-save me-2"></i> Salvar Recurso
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('iconInput').addEventListener('input', function(e) {
    const iconClass = e.target.value.trim() || 'bi-info-circle';
    document.getElementById('previewIcon').className = 'bi ' + iconClass;
});
</script>

<?php include 'admin_footer.php'; ?>
