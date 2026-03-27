<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Apenas admins podem acessar
if ($_SESSION['admin_user_perfil'] !== 'admin') {
    $_SESSION['mensagem_sucesso'] = "Acesso negado.";
    header("Location: index.php");
    exit;
}

$portal_id = filter_input(INPUT_GET, 'portal_id', FILTER_VALIDATE_INT);
if (!$portal_id) {
    header("Location: index.php");
    exit;
}

// Processa a adição de um novo campo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_campo'])) {
    $nome_campo = trim($_POST['nome_campo']);
    $tipo_campo = $_POST['tipo_campo'];
    $pesquisavel = isset($_POST['pesquisavel']) ? 1 : 0;
    $detalhes_apenas = isset($_POST['detalhes_apenas']) ? 1 : 0;
    $opcoes_campo = trim($_POST['opcoes_campo'] ?? '');

    if (!empty($nome_campo) && !empty($tipo_campo)) {
        // Pega a próxima ordem disponível
        $stmt_ordem = $pdo->prepare("SELECT MAX(ordem) FROM campos_portal WHERE id_portal = ?");
        $stmt_ordem->execute([$portal_id]);
        $max_ordem = $stmt_ordem->fetchColumn();
        $nova_ordem = ($max_ordem ?? 0) + 1;

        $stmt = $pdo->prepare("INSERT INTO campos_portal (id_portal, nome_campo, tipo_campo, opcoes_campo, ordem, pesquisavel, detalhes_apenas) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$portal_id, $nome_campo, $tipo_campo, $opcoes_campo, $nova_ordem, $pesquisavel, $detalhes_apenas]);
        $campo_id = $pdo->lastInsertId();
        
        registrar_log($pdo, 'ADIÇÃO', 'campos_portal', "Adicionou campo: $nome_campo (Seção ID: $portal_id, Campo ID: $campo_id)");
        
        $_SESSION['mensagem_sucesso'] = "Campo adicionado com sucesso!";
    } else {
        $_SESSION['mensagem_sucesso'] = "Erro: Nome e tipo do campo são obrigatórios.";
    }
    header("Location: gerenciar_campos.php?portal_id=" . $portal_id);
    exit;
}

// Busca dados da seção e campos existentes
$stmt_portal = $pdo->prepare("SELECT nome FROM portais WHERE id = ?");
$stmt_portal->execute([$portal_id]);
$secao = $stmt_portal->fetch();
if (!$secao) {
    header("Location: index.php");
    exit;
}

$stmt_campos = $pdo->prepare("SELECT id, nome_campo, tipo_campo, opcoes_campo, pesquisavel, detalhes_apenas FROM campos_portal WHERE id_portal = ? ORDER BY ordem ASC");
$stmt_campos->execute([$portal_id]);
$campos_existentes = $stmt_campos->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Campos - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .sortable-row:hover { cursor: grab; }
        .sortable-row:active { cursor: grabbing; }
        .sortable-ghost { opacity: 0.4; background: #c8ebfb; }
    </style>
</head>
<body class="bg-light-subtle">

<?php
$page_title_for_header = 'Gerenciar Campos: ' . htmlspecialchars($secao['nome']);
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
            ?>
            <div class="card mb-4">
                <div class="card-header">Adicionar Novo Campo</div>
                <div class="card-body">
                    <form method="POST" action="gerenciar_campos.php?portal_id=<?php echo $portal_id; ?>">
                        <input type="hidden" name="add_campo" value="1">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome_campo" class="form-label">Nome do Campo (Rótulo)</label>
                                <input type="text" class="form-control" id="nome_campo" name="nome_campo" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tipo_campo" class="form-label">Tipo do Campo</label>
                                <select class="form-select" id="tipo_campo" name="tipo_campo" required>
                                    <option value="texto">Texto Curto</option>
                                    <option value="textarea">Texto Longo</option>
                                    <option value="numero">Número</option>
                                    <option value="moeda">Moeda (R$)</option>
                                    <option value="data">Data</option>
                                    <option value="select">Lista de Opções (Select)</option>
                                    <option value="anexo">Anexo (Arquivo)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3" id="opcoes_campo_div" style="display: none;">
                            <label for="opcoes_campo" class="form-label">Opções do Campo</label>
                            <input type="text" class="form-control" id="opcoes_campo" name="opcoes_campo" placeholder="Ex: Opção 1, Opção 2, Opção 3">
                            <small class="form-text text-muted">Separe as opções com vírgula. Ou, para buscar de uma tabela, use `tabela:nome_da_tabela`.</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" value="1" id="pesquisavel" name="pesquisavel">
                                    <label class="form-check-label" for="pesquisavel">Tornar este campo pesquisável?</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" value="1" id="detalhes_apenas" name="detalhes_apenas">
                                    <label class="form-check-label" for="detalhes_apenas">Listar apenas na página de detalhes?</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">Adicionar Campo</button>
                        <a href="index.php" class="btn btn-secondary">Voltar</a>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Campos Existentes (Arraste para reordenar)</div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 50px;"></th>
                                <th>Nome do Campo</th>
                                <th>Tipo</th>
                                <th>Opções</th>
                                <th>Pesquisável?</th>
                                <th>Só nos Detalhes?</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="lista-campos">
                            <?php foreach ($campos_existentes as $campo): ?>
                                <tr class="sortable-row" data-id="<?php echo $campo['id']; ?>">
                                    <td><i class="bi bi-grip-vertical"></i></td>
                                    <td><?php echo htmlspecialchars($campo['nome_campo']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($campo['tipo_campo'])); ?></td>
                                    <td style="word-break: break-all; max-width: 250px;"><?php if ($campo['tipo_campo'] === 'select') { echo htmlspecialchars($campo['opcoes_campo']); } ?></td>
                                    <td><?php echo $campo['pesquisavel'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>'; ?></td>
                                    <td><?php echo $campo['detalhes_apenas'] ? '<span class="badge bg-info text-dark">Sim</span>' : '<span class="badge bg-secondary">Não</span>'; ?></td>
                                    <td class="text-end">
                                        <a href="editar_campo.php?id=<?php echo $campo['id']; ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="excluir_campo.php" class="d-inline ms-1" onsubmit="return confirm('ATENÇÃO! Excluir este campo irá apagar todos os dados inseridos nele. Deseja continuar?');">
                                            <input type="hidden" name="campo_id" value="<?php echo $campo['id']; ?>">
                                            <input type="hidden" name="portal_id" value="<?php echo $portal_id; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('tipo_campo').addEventListener('change', function() {
        document.getElementById('opcoes_campo_div').style.display = (this.value === 'select') ? 'block' : 'none';
    });

    const lista = document.getElementById('lista-campos');
    new Sortable(lista, {
        animation: 150,
        handle: '.bi-grip-vertical',
        ghostClass: 'sortable-ghost',
        onEnd: function (evt) {
            const novaOrdem = Array.from(lista.children).map(item => item.getAttribute('data-id'));
            
            fetch('salvar_ordem_campos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ordem: novaOrdem })
            });
        }
    });
});
</script>
</body>
</html>