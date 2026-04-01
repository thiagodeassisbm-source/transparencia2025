<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

if ($_SESSION['admin_user_perfil'] !== 'admin' && $_SESSION['admin_user_nome'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$tipo_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$tipo_id) { 
    header("Location: gerenciar_tipos_documento.php"); 
    exit; 
}

// Processa o formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    if (!empty($nome)) {
        $stmt = $pdo->prepare("UPDATE tipos_documento SET nome = ? WHERE id = ?");
        $stmt->execute([$nome, $tipo_id]);
        
        registrar_log($pdo, 'EDIÇÃO', 'tipos_documento', "Renomeou o tipo de documento para: $nome (ID: $tipo_id)");
        
        $_SESSION['mensagem_sucesso'] = "Tipo de documento atualizado com sucesso!";
        header("Location: gerenciar_tipos_documento.php");
        exit;
    }
}

// Busca os dados para preencher o formulário
$stmt = $pdo->prepare("SELECT nome FROM tipos_documento WHERE id = ?");
$stmt->execute([$tipo_id]);
$tipo = $stmt->fetch();

if (!$tipo) { 
    header("Location: gerenciar_tipos_documento.php"); 
    exit; 
}

$page_title_for_header = 'Editar Tipo de Documento';
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-12">
            
            <div class="row align-items-center mb-4">
                <div class="col-auto">
                    <a href="gerenciar_tipos_documento.php" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                </div>
                <div class="col">
                    <h3 class="fw-bold text-dark mb-0">Editar Tipo de Documento</h3>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h6 class="mb-0 fw-bold text-dark">Informações do Tipo</h6>
                </div>
                <div class="card-body bg-light bg-opacity-10 px-4 py-4">
                    <form method="POST" action="editar_tipo_documento.php?id=<?php echo $tipo_id; ?>">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-9">
                                <label for="nome" class="form-label fw-bold small text-muted">Nome do Tipo de Documento</label>
                                <input type="text" class="form-control form-control-lg border-0 shadow-sm" id="nome" name="nome" value="<?php echo htmlspecialchars($tipo['nome']); ?>" required style="border-radius: 10px;">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm" style="border-radius: 10px;">
                                    <i class="bi bi-save me-2"></i>Salvar Alterações
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
</body>
</html>
