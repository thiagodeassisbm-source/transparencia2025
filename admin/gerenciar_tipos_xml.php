<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Lógica para salvar (Adicionar/Editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nome_amigavel = trim($_POST['nome_amigavel']);
    $tag_container = trim($_POST['tag_container']);
    $tag_registro = trim($_POST['tag_registro']);

    if ($id) { // Edição
        $stmt = $pdo->prepare("UPDATE tipos_xml SET nome_amigavel=?, tag_container=?, tag_registro=? WHERE id=?");
        $stmt->execute([$nome_amigavel, $tag_container, $tag_registro, $id]);
    } else { // Adição
        $stmt = $pdo->prepare("INSERT INTO tipos_xml (nome_amigavel, tag_container, tag_registro) VALUES (?, ?, ?)");
        $stmt->execute([$nome_amigavel, $tag_container, $tag_registro]);
    }
    header("Location: gerenciar_tipos_xml.php");
    exit;
}

// Lógica para deletar
if (isset($_GET['deletar'])) {
    $id = filter_input(INPUT_GET, 'deletar', FILTER_VALIDATE_INT);
    $stmt = $pdo->prepare("DELETE FROM tipos_xml WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: gerenciar_tipos_xml.php");
    exit;
}

// Busca dados para edição ou para listar
$tipo_edicao = null;
if (isset($_GET['editar'])) {
    $id = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
    $stmt = $pdo->prepare("SELECT * FROM tipos_xml WHERE id = ?");
    $stmt->execute([$id]);
    $tipo_edicao = $stmt->fetch();
}
$tipos = $pdo->query("SELECT * FROM tipos_xml ORDER BY nome_amigavel ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Tipos de XML - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">
<?php
// TÍTULO DA PÁGINA DEFINIDO PARA O CABEÇALHO
$page_title_for_header = 'Gerenciar Tipos de XML';
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4><?= $tipo_edicao ? 'Editar Tipo' : 'Adicionar Novo Tipo de XML' ?></h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="gerenciar_tipos_xml.php">
                        <input type="hidden" name="id" value="<?= $tipo_edicao['id'] ?? '' ?>">
                        <div class="mb-3">
                            <label for="nome_amigavel" class="form-label">Nome Amigável</label>
                            <input type="text" class="form-control" id="nome_amigavel" name="nome_amigavel" value="<?= htmlspecialchars($tipo_edicao['nome_amigavel'] ?? '') ?>" required>
                            <div class="form-text">Ex: Licitações, Folha de Pagamento</div>
                        </div>
                        <div class="mb-3">
                            <label for="tag_container" class="form-label">Tag Contêiner (Plural)</label>
                            <input type="text" class="form-control" id="tag_container" name="tag_container" value="<?= htmlspecialchars($tipo_edicao['tag_container'] ?? '') ?>" required>
                            <div class="form-text">Ex: Licitacoes, FolhaPagamento</div>
                        </div>
                        <div class="mb-3">
                            <label for="tag_registro" class="form-label">Tag de Registro (Singular)</label>
                            <input type="text" class="form-control" id="tag_registro" name="tag_registro" value="<?= htmlspecialchars($tipo_edicao['tag_registro'] ?? '') ?>" required>
                            <div class="form-text">Ex: Licitacao, Servidor</div>
                        </div>
                        <button type="submit" name="salvar" class="btn btn-primary">Salvar</button>
                        <?php if ($tipo_edicao): ?>
                            <a href="gerenciar_tipos_xml.php" class="btn btn-secondary">Cancelar Edição</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Tipos de XML Cadastrados</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Nome Amigável</th>
                                <th>Tag Contêiner</th>
                                <th>Tag de Registro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tipos as $tipo): ?>
                            <tr>
                                <td><?= htmlspecialchars($tipo['nome_amigavel']) ?></td>
                                <td><code>&lt;<?= htmlspecialchars($tipo['tag_container']) ?>&gt;</code></td>
                                <td><code>&lt;<?= htmlspecialchars($tipo['tag_registro']) ?>&gt;</code></td>
                                <td>
                                    <a href="?editar=<?= $tipo['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                    <a href="?deletar=<?= $tipo['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza?')"><i class="bi bi-trash"></i></a>
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

<?php include 'admin_footer.php'; // FOOTER DO ADMIN ADICIONADO ?>
</body>
</html>