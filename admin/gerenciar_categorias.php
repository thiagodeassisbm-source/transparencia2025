<?php
require_once 'auth_check.php';
require_once '../conexao.php';

if ($_SESSION['admin_user_perfil'] !== 'admin') {
    $_SESSION['mensagem_sucesso'] = "Acesso negado.";
    header("Location: dashboard.php");
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

$page_title_for_header = 'Gerenciar Categorias'; 
include 'admin_header.php'; 
?>

<style>
    .list-group-item:hover { cursor: grab; }
    .list-group-item:active { cursor: grabbing; }
    .sortable-ghost { opacity: 0.4; background: #c8ebfb; }
</style>


<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-12">
             <?php
            if (isset($_SESSION['mensagem_sucesso'])) {
                echo '<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">' 
                   . htmlspecialchars($_SESSION['mensagem_sucesso']) . 
                   '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                unset($_SESSION['mensagem_sucesso']);
            }
             if ($erro) {
                echo '<div class="alert alert-danger border-0 shadow-sm">' . htmlspecialchars($erro) . '</div>';
            }
            ?>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-header bg-white py-3 fw-bold">Adicionar Nova Categoria</div>
                        <div class="card-body">
                            <form method="POST" action="gerenciar_categorias.php">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome da Categoria</label>
                                    <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex: Financeiro" required>
                                </div>
                                <input type="hidden" name="add_categoria" value="1">
                                <button type="submit" class="btn btn-primary w-100">Salvar Categoria</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-header bg-white py-3 fw-bold">Categorias Existentes (Arraste para reordenar)</div>
                        <div id="lista-categorias" class="list-group list-group-flush">
                            <?php if (empty($categorias)): ?>
                                <div class="p-5 text-center text-muted">Nenhuma categoria cadastrada.</div>
                            <?php else: ?>
                                <?php foreach ($categorias as $categoria): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3" data-id="<?php echo $categoria['id']; ?>">
                                        <span>
                                            <i class="bi bi-grip-vertical me-2 text-muted"></i>
                                            <?php echo htmlspecialchars($categoria['nome']); ?>
                                        </span>
                                        <div class="d-flex gap-1">
                                            <a href="editar_categoria.php?id=<?php echo $categoria['id']; ?>" class="btn btn-light border btn-sm" data-bs-toggle="tooltip" title="Editar Nome">
                                                <i class="bi bi-pencil text-primary"></i>
                                            </a>
                                            <form method="POST" action="gerenciar_categorias.php" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria? As seções e cards associados não serão apagados, mas ficarão sem categoria.');">
                                                <input type="hidden" name="id_categoria" value="<?php echo $categoria['id']; ?>">
                                                <input type="hidden" name="delete_categoria" value="1">
                                                <button type="submit" class="btn btn-light border btn-sm" data-bs-toggle="tooltip" title="Excluir Categoria">
                                                    <i class="bi bi-trash text-danger"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const lista = document.getElementById('lista-categorias');
    if (lista) {
        new Sortable(lista, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            handle: '.bi-grip-vertical',
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
    }
});
</script>
</body>
</html>