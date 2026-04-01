<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

$pref_id = $_SESSION['id_prefeitura'];

if ($_SESSION['admin_user_perfil'] !== 'admin' && $_SESSION['admin_user_nome'] !== 'admin') {
    $_SESSION['mensagem_sucesso'] = "Acesso negado.";
    header("Location: dashboard.php");
    exit;
}

$erro = '';

// Adiciona uma nova categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_categoria'])) {
    $nome = trim($_POST['nome']);
    if (!empty($nome)) {
        // Busca a última ordem para colocar no final
        $stmt_ordem = $pdo->prepare("SELECT MAX(ordem) FROM categorias WHERE id_prefeitura = ?");
        $stmt_ordem->execute([$pref_id]);
        $max_ordem = $stmt_ordem->fetchColumn() ?: 0;
        $nova_ordem = $max_ordem + 1;

        $stmt = $pdo->prepare("INSERT INTO categorias (nome, id_prefeitura, ordem) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $pref_id, $nova_ordem]);
        $cat_id = $pdo->lastInsertId();
        
        registrar_log($pdo, 'ADIÇÃO', 'categorias', "Adicionou a categoria: $nome (ID: $cat_id)");
        
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
        $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ? AND id_prefeitura = ?");
        $stmt->execute([$id_categoria, $pref_id]);
        
        registrar_log($pdo, 'EXCLUSÃO', 'categorias', "Excluiu a categoria ID: $id_categoria");
        
        $_SESSION['mensagem_sucesso'] = "Categoria excluída com sucesso!";
    }
    header("Location: gerenciar_categorias.php");
    exit;
}

$stmt_cats = $pdo->prepare("SELECT id, nome, ordem FROM categorias WHERE id_prefeitura = ? ORDER BY ordem ASC");
$stmt_cats->execute([$pref_id]);
$categorias = $stmt_cats->fetchAll();

$page_title_for_header = 'Gerenciar Categorias'; 
include 'admin_header.php'; 
?>

<style>
    .list-group-item:hover { background-color: #f8fafc; }
    .bi-grip-vertical { cursor: grab; padding: 5px; border-radius: 4px; transition: all 0.2s; }
    .bi-grip-vertical:hover { background: #e2e8f0; color: #3b82f6 !important; }
    .sortable-ghost { opacity: 0.4; background: #eff6ff; border: 2px dashed #3b82f6 !important; }
    .category-card { border-radius: 15px; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
</style>


<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-12">
            
            <div class="row align-items-center mb-4">
                <div class="col-md-7">
                    <h3 class="fw-bold text-dark mb-1">Gerenciar Categorias</h3>
                    <p class="text-muted small mb-0"><i class="bi bi-folder2-open me-1"></i> Organize suas seções e cards em categorias para facilitar a navegação do portal.</p>
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
            <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: #fff; border-radius: 15px;">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="me-4 d-none d-md-block">
                        <div class="bg-white bg-opacity-20 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                            <i class="bi bi-tags-fill fs-2"></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1 text-white">Categorias & Organização</h5>
                        <p class="mb-0 opacity-90 small">
                            As categorias agrupam seções semelhantes. Você pode <strong>arrastar e soltar</strong> os itens abaixo para definir a ordem em que aparecerão no portal público.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Formulário no Topo -->
            <div class="card category-card mb-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-plus-circle me-2 text-primary"></i>Adicionar Nova Categoria</h6>
                </div>
                <div class="card-body bg-light bg-opacity-10 px-4 py-4">
                    <form method="POST" action="gerenciar_categorias.php" class="row g-3 align-items-end">
                        <div class="col-md-9">
                            <label for="nome" class="form-label fw-bold small text-muted">Nome da Categoria</label>
                            <input type="text" class="form-control form-control-lg border-0 shadow-sm" id="nome" name="nome" placeholder="Ex: Finanças, Obras, Educação..." required style="border-radius: 10px;">
                        </div>
                        <div class="col-md-3">
                            <input type="hidden" name="add_categoria" value="1">
                            <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm" style="border-radius: 10px;"><i class="bi bi-save me-2"></i>Salvar Categoria</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de Categorias -->
            <div class="card category-card overflow-hidden mb-5">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-ul me-2 text-primary"></i>Categorias Existentes</h6>
                    <span class="badge bg-light text-primary rounded-pill px-3"><?php echo count($categorias); ?> Total</span>
                </div>
                <div id="lista-categorias" class="list-group list-group-flush">
                    <?php if (empty($categorias)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-3 opacity-20"></i>
                            Nenhuma categoria cadastrada ainda.
                        </div>
                    <?php else: ?>
                        <?php foreach ($categorias as $categoria): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center p-3 border-start-0 border-end-0" data-id="<?php echo $categoria['id']; ?>">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-grip-vertical me-3 text-muted fs-4"></i>
                                    <div>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($categoria['nome']); ?></div>
                                        <div class="text-muted small">ID: #<?php echo $categoria['id']; ?></div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="editar_categoria.php?id=<?php echo $categoria['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-none">
                                        <i class="bi bi-pencil me-1"></i> Editar
                                    </a>
                                    <form method="POST" action="gerenciar_categorias.php" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria?');">
                                        <input type="hidden" name="id_categoria" value="<?php echo $categoria['id']; ?>">
                                        <input type="hidden" name="delete_categoria" value="1">
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-none">
                                            <i class="bi bi-trash me-1"></i> Excluir
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

<?php include 'admin_footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const lista = document.getElementById('lista-categorias');
    if (lista) {
        new Sortable(lista, {
            animation: 200,
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
