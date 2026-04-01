<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Apenas admins podem acessar esta página
if ($_SESSION['admin_user_perfil'] !== 'admin' && $_SESSION['admin_user_nome'] !== 'admin') {
    $_SESSION['mensagem_sucesso'] = "Acesso negado.";
    header("Location: index.php");
    exit;
}

$erro = '';

// Adiciona um novo tipo de documento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_documento'])) {
    $nome = trim($_POST['nome']);
    if (!empty($nome)) {
        $stmt = $pdo->prepare("INSERT INTO tipos_documento (nome) VALUES (?)");
        $stmt->execute([$nome]);
        
        $doc_id = $pdo->lastInsertId();
        registrar_log($pdo, 'ADIÇÃO', 'tipos_documento', "Adicionou o tipo de documento: $nome (ID: $doc_id)");
        
        $_SESSION['mensagem_sucesso'] = "Tipo de Documento adicionado com sucesso!";
    } else {
        $erro = "O nome não pode ser vazio.";
    }
    header("Location: gerenciar_tipos_documento.php");
    exit;
}

// Exclui um tipo de documento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_documento'])) {
    $id_documento = filter_input(INPUT_POST, 'id_documento', FILTER_VALIDATE_INT);
    if ($id_documento) {
        $stmt = $pdo->prepare("DELETE FROM tipos_documento WHERE id = ?");
        $stmt->execute([$id_documento]);
        
        registrar_log($pdo, 'EXCLUSÃO', 'tipos_documento', "Excluiu o tipo de documento ID: $id_documento");
        
        $_SESSION['mensagem_sucesso'] = "Tipo de Documento excluído com sucesso!";
    }
    header("Location: gerenciar_tipos_documento.php");
    exit;
}

// Busca os tipos de documento existentes para listar
$tipos_documento = $pdo->query("SELECT id, nome FROM tipos_documento ORDER BY nome ASC")->fetchAll();

$page_title_for_header = 'Gerenciar Tipos de Documento';
include 'admin_header.php';
?>

<style>
    .document-type-card { border-radius: 15px; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
</style>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-12">
            
            <div class="row align-items-center mb-4">
                <div class="col-md-7">
                    <h3 class="fw-bold text-dark mb-1">Gerenciar Tipos de Documento</h3>
                    <p class="text-muted small mb-0"><i class="bi bi-file-earmark-text me-1"></i> Defina os tipos de documentos que estarão disponíveis para categorização em suas publicações.</p>
                </div>
            </div>

            <?php
            if (isset($_SESSION['mensagem_sucesso'])) {
                echo '<div class="alert alert-success border-0 shadow-sm alert-dismissible fade show rounded-4" role="alert">' 
                   . '<i class="bi bi-check-circle-fill me-2"></i>' . htmlspecialchars($_SESSION['mensagem_sucesso']) . 
                   '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                unset($_SESSION['mensagem_sucesso']);
            }
            if ($erro) {
                echo '<div class="alert alert-danger border-0 shadow-sm rounded-4">' . htmlspecialchars($erro) . '</div>';
            }
            ?>

            <!-- Card Informativo -->
            <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; border-radius: 15px;">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="me-4 d-none d-md-block">
                        <div class="bg-white bg-opacity-20 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                            <i class="bi bi-file-earmark-plus fs-2"></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1 text-white">Classificação de Arquivos</h5>
                        <p class="mb-0 opacity-90 small">
                            Os tipos de documento ajudam na organização do portal. Exemplos: <strong>Lei Municipal</strong>, <strong>Decreto</strong>, <strong>Portaria</strong>, <strong>Contrato</strong>, entre outros.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Formulário no Topo -->
            <div class="card document-type-card mb-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-plus-circle me-2 text-success"></i>Adicionar Novo Tipo</h6>
                </div>
                <div class="card-body bg-light bg-opacity-10 px-4 py-4">
                    <form method="POST" action="gerenciar_tipos_documento.php" class="row g-3 align-items-end">
                        <div class="col-md-9">
                            <label for="nome" class="form-label fw-bold small text-muted">Nome do Tipo de Documento</label>
                            <input type="text" class="form-control form-control-lg border-0 shadow-sm" id="nome" name="nome" placeholder="Ex: Lei Ordinária, Edital, Parecer Técnico..." required style="border-radius: 10px;">
                        </div>
                        <div class="col-md-3">
                            <input type="hidden" name="add_documento" value="1">
                            <button type="submit" class="btn btn-success btn-lg w-100 shadow-sm" style="border-radius: 10px;"><i class="bi bi-save me-2"></i>Salvar Tipo</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de Tipos de Documento -->
            <div class="card document-type-card overflow-hidden mb-5">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-ul me-2 text-success"></i>Tipos de Documento Cadastrados</h6>
                    <span class="badge bg-light text-success rounded-pill px-3"><?php echo count($tipos_documento); ?> Total</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="p-3" style="width: 80px;">ID</th>
                                <th class="p-3">Nome do Tipo</th>
                                <th class="p-3 text-center" style="width: 150px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tipos_documento)): ?>
                                <tr>
                                    <td colspan="3" class="p-5 text-center text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-3 opacity-20"></i>
                                        Nenhum tipo de documento cadastrado ainda.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tipos_documento as $tipo): ?>
                                    <tr>
                                        <td class="p-3 text-muted">#<?php echo $tipo['id']; ?></td>
                                        <td class="p-3 fw-bold text-dark"><?php echo htmlspecialchars($tipo['nome']); ?></td>
                                        <td class="p-3">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="editar_tipo_documento.php?id=<?php echo $tipo['id']; ?>" class="btn btn-outline-primary btn-sm rounded-3 d-flex align-items-center justify-content-center shadow-none" style="width: 32px; height: 32px;" title="Editar Tipo">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" action="gerenciar_tipos_documento.php" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este tipo de documento?');">
                                                    <input type="hidden" name="id_documento" value="<?php echo $tipo['id']; ?>">
                                                    <input type="hidden" name="delete_documento" value="1">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-3 d-flex align-items-center justify-content-center shadow-none" style="width: 32px; height: 32px;" title="Excluir Tipo">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
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
</body>
</html>