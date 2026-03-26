<?php
require_once 'auth_check.php';
require_once '../conexao.php';

if ($_SESSION['admin_user_perfil'] !== 'admin') {
    $_SESSION['mensagem_sucesso'] = "Acesso negado.";
    header("Location: index.php");
    exit;
}

$erro = '';

// Adiciona uma nova categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_categoria'])) {
    $nome = trim($_POST['nome']);
    if (!empty($nome)) {
        $stmt = $pdo->prepare("INSERT INTO categorias (nome) VALUES (?)");
        $stmt->execute([$nome]);
        $_SESSION['mensagem_sucesso'] = "Categoria adicionada com sucesso!";
    } else {
        $erro = "O nome da categoria não pode ser vazio.";
    }
    header("Location: gerenciar_categorias.php");
    exit;
}

// Exclui uma categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_categoria'])) {
    $id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
    if ($id_categoria) {
        // A regra ON DELETE SET NULL no banco de dados garantirá que as seções
        // e cards ligados a esta categoria fiquem com id_categoria = NULL.
        $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
        $stmt->execute([$id_categoria]);
        $_SESSION['mensagem_sucesso'] = "Categoria excluída com sucesso!";
    }
    header("Location: gerenciar_categorias.php");
    exit;
}

$categorias = $pdo->query("SELECT id, nome, ordem FROM categorias ORDER BY ordem ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Categorias - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .list-group-item:hover { cursor: grab; }
        .list-group-item:active { cursor: grabbing; }
        .sortable-ghost { opacity: 0.4; background: #c8ebfb; }
    </style>
</head>
<body class="bg-light-subtle">

<?php 
$page_title_for_header = 'Gerenciar Categorias'; 
include 'admin_header.php'; 
?>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-12">
             <?php
            if (isset($_SESSION['mensagem_sucesso'])) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' 
                   . htmlspecialchars($_SESSION['mensagem_sucesso']) . 
                   '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                unset($_SESSION['mensagem_sucesso']);
            }
             if ($erro) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($erro) . '</div>';
            }
            ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header">Adicionar Nova Categoria</div>
                        <div class="card-body">
                            <form method="POST" action="gerenciar_categorias.php">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome da Categoria</label>
                                    <input type="text" class="form-control" id="nome" name="nome" required>
                                </div>
                                <input type="hidden" name="add_categoria" value="1">
                                <button type="submit" class="btn btn-success">Salvar Categoria</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-header">Categorias Existentes (Arraste para reordenar)</div>
                        <div id="lista-categorias" class="list-group list-group-flush">
                            <?php foreach ($categorias as $categoria): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center" data-id="<?php echo $categoria['id']; ?>">
                                    <span>
                                        <i class="bi bi-grip-vertical me-2"></i>
                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                    </span>
                                    <div>
                                        <a href="editar_categoria.php?id=<?php echo $categoria['id']; ?>" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Editar Categoria">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="gerenciar_categorias.php" class="d-inline ms-1" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria? As seções e cards associados não serão apagados, mas ficarão sem categoria.');">
                                            <input type="hidden" name="id_categoria" value="<?php echo $categoria['id']; ?>">
                                            <input type="hidden" name="delete_categoria" value="1">
                                            <button type="submit" class="btn btn-danger btn-sm" data-bs-toggle="tooltip" title="Excluir Categoria">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center p-3 bg-light mt-4">
    &copy; <?php echo date('Y'); ?> - Todos os direitos reservados.
</footer>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl) });

    const lista = document.getElementById('lista-categorias');
    new Sortable(lista, {
        animation: 150,
        ghostClass: 'sortable-ghost',
        onEnd: function (evt) {
            const novaOrdem = Array.from(lista.children).map(item => item.getAttribute('data-id'));
            fetch('salvar_ordem_categorias.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ordem: novaOrdem })
            }).then(response => response.json()).then(data => {
                if (!data.success) { alert('Ocorreu um erro ao salvar a nova ordem.'); }
            });
        }
    });
</script>
</body>
</html>