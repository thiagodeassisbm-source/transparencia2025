<?php
require_once 'auth_check.php';
require_once '../conexao.php';

$perfil_usuario = $_SESSION['admin_user_perfil'];

// --- 1. CONSULTA ATUALIZADA PARA ORDENAR POR CATEGORIA ---
$stmt = $pdo->query(
    "SELECT p.id, p.nome, p.slug, c.nome as nome_categoria 
     FROM portais p
     LEFT JOIN categorias c ON p.id_categoria = c.id
     ORDER BY c.ordem, c.nome, p.ordem ASC"
);
$secoes_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 2. ORGANIZA AS SEÇÕES EM GRUPOS DE CATEGORIA ---
$secoes_agrupadas = [];
foreach ($secoes_raw as $secao) {
    $categoria = $secao['nome_categoria'] ?? 'Sem Categoria';
    $secoes_agrupadas[$categoria][] = $secao;
}

$page_title_for_header = 'Painel Administrativo'; 
include 'admin_header.php'; 
?>

<style>
    .list-group-item-draggable:hover { cursor: grab; }
    .list-group-item-draggable:active { cursor: grabbing; }
    .sortable-ghost { opacity: 0.4; background: #c8ebfb; }
    .accordion-button:not(.collapsed) { background-color: #f8f9fa; color: var(--primary-color); }
</style>


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
            ?>
            <div class="card">
                <div class="card-header">
                    Seções Cadastradas por Categoria
                </div>
                <div class="accordion accordion-flush" id="accordionCategorias">
                    <?php if (empty($secoes_agrupadas)): ?>
                        <div class="list-group-item">Nenhuma seção foi criada ainda.</div>
                    <?php else: ?>
                        <?php foreach ($secoes_agrupadas as $categoria => $secoes_do_grupo): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-<?php echo md5($categoria); ?>">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo md5($categoria); ?>" aria-expanded="true" aria-controls="collapse-<?php echo md5($categoria); ?>">
                                        <strong><?php echo htmlspecialchars($categoria); ?></strong>
                                    </button>
                                </h2>
                                <div id="collapse-<?php echo md5($categoria); ?>" class="accordion-collapse collapse show" aria-labelledby="heading-<?php echo md5($categoria); ?>">
                                    <div class="list-group list-group-flush sortable-list">
                                        <?php foreach ($secoes_do_grupo as $secao): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap <?php if ($perfil_usuario == 'admin') echo 'list-group-item-draggable'; ?>" data-id="<?php echo $secao['id']; ?>">
                                                <div class="me-auto my-1">
                                                    <?php if ($perfil_usuario == 'admin'): ?><i class="bi bi-grip-vertical me-2"></i><?php endif; ?>
                                                    <strong><?php echo htmlspecialchars($secao['nome']); ?></strong>
                                                    <br>
                                                    <small class="text-muted ms-4">
                                                        <a href="../portal.php?slug=<?php echo htmlspecialchars($secao['slug']); ?>" target="_blank"><i class="bi bi-link-45deg"></i> Link Público</a>
                                                    </small>
                                                </div>
                                                <div class="d-flex align-items-center my-1">
                                                    <a href="ver_lancamentos.php?portal_id=<?php echo $secao['id']; ?>" class="btn btn-info btn-sm" data-bs-toggle="tooltip" title="Ver Lançamentos"><i class="bi bi-eye"></i></a>
                                                    <a href="lancar_dados.php?portal_id=<?php echo $secao['id']; ?>" class="btn btn-secondary btn-sm ms-1" data-bs-toggle="tooltip" title="Lançar Dados"><i class="bi bi-file-earmark-plus"></i></a>
                                                    <?php if ($perfil_usuario == 'admin'): ?>
                                                    <a href="editar_secao.php?id=<?php echo $secao['id']; ?>" class="btn btn-warning btn-sm ms-1" data-bs-toggle="tooltip" title="Editar Seção"><i class="bi bi-pencil"></i></a>
                                                    <a href="gerenciar_campos.php?portal_id=<?php echo $secao['id']; ?>" class="btn btn-primary btn-sm ms-1" data-bs-toggle="tooltip" title="Gerenciar Campos"><i class="bi bi-pencil-square"></i></a>
                                                    <form method="POST" action="excluir_secao.php" class="d-inline ms-1" onsubmit="return confirm('ATENÇÃO: Excluir a seção apagará PERMANENTEMENTE todos os seus campos e registros. Deseja continuar?');">
                                                        <input type="hidden" name="portal_id" value="<?php echo $secao['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" data-bs-toggle="tooltip" title="Excluir Seção"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
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
    <?php if ($perfil_usuario == 'admin'): ?>
    // 4. JAVASCRIPT ATUALIZADO PARA FUNCIONAR COM MÚLTIPLAS LISTAS
    const listas = document.querySelectorAll('.sortable-list');
    listas.forEach(lista => {
        new Sortable(lista, { 
            animation: 150,
            ghostClass: 'sortable-ghost',
            handle: '.bi-grip-vertical', // Define o ícone como a "alça" para arrastar
            onEnd: function (evt) {
                const novaOrdem = Array.from(evt.from.children).map(item => item.getAttribute('data-id'));
                fetch('salvar_ordem.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ordem: novaOrdem })
                });
            }
        });
    });
    <?php endif; ?>
</script>
</body>
</html>