<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Bloqueia se não for superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

$mensagem_sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configs = [
        'copyright_ano' => $_POST['copyright_ano'],
        'copyright_dev_nome' => $_POST['copyright_dev_nome'],
        'copyright_dev_site' => $_POST['copyright_dev_site'],
        'copyright_texto' => $_POST['copyright_texto']
    ];

    foreach ($configs as $chave => $valor) {
        $stmt = $pdo->prepare("INSERT INTO config_global (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?");
        $stmt->execute([$chave, $valor, $valor]);
    }

    registrar_log($pdo, 'SUPERADMIN', 'CONFIG_COPYRIGHT', "Atualizou as configurações de rodapé/copyright");
    $mensagem_sucesso = "Configurações atualizadas com sucesso!";
}

// Busca configurações atuais
$stmt = $pdo->query("SELECT chave, valor FROM config_global");
$config_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Valores padrão caso não existam
$copyright_ano = $config_raw['copyright_ano'] ?? date('Y');
$copyright_dev_nome = $config_raw['copyright_dev_nome'] ?? 'UpGyn';
$copyright_dev_site = $config_raw['copyright_dev_site'] ?? 'https://www.upgyn.com.br';
$copyright_texto = $config_raw['copyright_texto'] ?? 'Todos os Direitos Reservados';

$page_title_for_header = 'Configurar Copyright e Rodapé';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            
            <?php if ($mensagem_sucesso): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo $mensagem_sucesso; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 p-4 pt-5 text-center">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle d-inline-flex mb-3">
                        <i class="bi bi-c-circle fs-3 text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-1 border-bottom d-inline-block pb-2 px-3">Copyright Global</h4>
                    <p class="text-muted small">Gerencie as informações que aparecem no rodapé de todo o sistema.</p>
                </div>
                <div class="card-body p-4 p-md-5 pt-2">
                    <form method="POST">
                        <div class="row g-4 justify-content-center">
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted">Ano Corrente</label>
                                <input type="text" name="copyright_ano" class="form-control form-control-lg bg-light border-0 rounded-3 text-center" value="<?php echo htmlspecialchars($copyright_ano); ?>" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-muted">Desenvolvido por (Nome)</label>
                                <input type="text" name="copyright_dev_nome" class="form-control form-control-lg bg-light border-0 rounded-3" value="<?php echo htmlspecialchars($copyright_dev_nome); ?>" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-muted">Site do Desenvolvedor (URL)</label>
                                <input type="url" name="copyright_dev_site" class="form-control form-control-lg bg-light border-0 rounded-3" value="<?php echo htmlspecialchars($copyright_dev_site); ?>" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted">Texto Adicional (Direitos)</label>
                                <input type="text" name="copyright_texto" class="form-control form-control-lg bg-light border-0 rounded-3" value="<?php echo htmlspecialchars($copyright_texto); ?>" required>
                            </div>

                            <!-- Preview -->
                            <div class="col-12 mt-5">
                                <div class="p-4 bg-light rounded-4 border border-dashed text-center">
                                    <label class="d-block small text-muted mb-2 text-uppercase fw-bold">Visualização do Rodapé</label>
                                    <div class="text-muted small">
                                        &copy; <span id="prev_ano"><?php echo $copyright_ano; ?></span> - Desenvolvido por 
                                        <a href="#" class="text-primary fw-bold text-decoration-none" id="prev_nome"><?php echo $copyright_dev_nome; ?></a> 
                                        | <span id="prev_texto"><?php echo $copyright_texto; ?></span>.
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-4 text-center pb-4">
                                <button type="submit" class="btn btn-primary rounded-pill px-5 py-3 fw-bold shadow-lg" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                                    <i class="bi bi-cloud-upload me-2 text-white"></i> SALVAR CONFIGURAÇÕES DE RODAPÉ
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const name = this.getAttribute('name').replace('copyright_', '');
            const target = document.getElementById('prev_' + name);
            if (target) target.innerText = this.value;
        });
    });
});
</script>

<?php include 'admin_footer.php'; ?>
