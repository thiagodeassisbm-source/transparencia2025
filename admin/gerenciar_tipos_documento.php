<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Apenas admins podem acessar esta página
if ($_SESSION['admin_user_perfil'] !== 'admin') {
    $_SESSION['mensagem_sucesso'] = "Acesso negado.";
    header("Location: index.php");
    exit;
}

// Variáveis de sessão do usuário (para o cabeçalho)
$perfil_usuario = $_SESSION['admin_user_perfil'];
$id_usuario_logado = $_SESSION['admin_user_id'];
$nome_usuario_logado = $_SESSION['admin_user_nome'] ?? 'Usuário';

$erro = '';

// Adiciona um novo tipo de documento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_documento'])) {
    $nome = trim($_POST['nome']);
    if (!empty($nome)) {
        $stmt = $pdo->prepare("INSERT INTO tipos_documento (nome) VALUES (?)");
        $stmt->execute([$nome]);
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
        $_SESSION['mensagem_sucesso'] = "Tipo de Documento excluído com sucesso!";
    }
    header("Location: gerenciar_tipos_documento.php");
    exit;
}

// Busca os tipos de documento existentes para listar
$tipos_documento = $pdo->query("SELECT id, nome FROM tipos_documento ORDER BY nome ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Tipos de Documento - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">

<?php
// Define as variáveis para o cabeçalho reutilizável
$page_title_for_header = 'Gerenciar Tipos de Documento';
$active_breadcrumb = 'Tipos de Documento';
$active_nav_link = 'gerenciar_tipos_documento.php';
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-12 pt-4">

            <?php
            if (isset($_SESSION['mensagem_sucesso'])) {
                echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['mensagem_sucesso']) . '</div>';
                unset($_SESSION['mensagem_sucesso']);
            }
            if ($erro) {
                echo '<div class="alert alert-danger">' . $erro . '</div>';
            }
            ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Adicionar Novo Tipo</div>
                        <div class="card-body">
                            <form method="POST" action="gerenciar_tipos_documento.php">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome do Tipo de Documento</label>
                                    <input type="text" class="form-control" id="nome" name="nome" required>
                                </div>
                                <input type="hidden" name="add_documento" value="1">
                                <button type="submit" class="btn btn-success">Salvar</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">Tipos Cadastrados</div>
                        <ul class="list-group list-group-flush">
                            <?php if(empty($tipos_documento)): ?>
                                <li class="list-group-item">Nenhum tipo de documento cadastrado.</li>
                            <?php else: ?>
                                <?php foreach ($tipos_documento as $tipo): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($tipo['nome']); ?>
                                        <form method="POST" action="gerenciar_tipos_documento.php" onsubmit="return confirm('Tem certeza que deseja excluir este item?');">
                                            <input type="hidden" name="id_documento" value="<?php echo $tipo['id']; ?>">
                                            <input type="hidden" name="delete_documento" value="1">
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-3 d-flex align-items-center justify-content-center shadow-none" style="width: 32px; height: 32px;" title="Excluir Tipo">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center p-3 bg-light mt-4">
    &copy; <?php echo date('Y'); ?> - Todos os direitos reservados.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl) });
</script>
</body>
</html>