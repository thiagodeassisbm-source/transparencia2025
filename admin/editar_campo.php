<?php
require_once 'auth_check.php';
require_once '../conexao.php';

if ($_SESSION['admin_user_perfil'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$campo_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$campo_id) {
    header("Location: index.php");
    exit;
}

// Lógica para salvar as alterações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_campo = trim($_POST['nome_campo']);
    $tipo_campo = $_POST['tipo_campo'];
    $pesquisavel = isset($_POST['pesquisavel']) ? 1 : 0;
    $detalhes_apenas = isset($_POST['detalhes_apenas']) ? 1 : 0; // Pega o valor do novo switcher
    $opcoes_campo = trim($_POST['opcoes_campo'] ?? '');
    $portal_id = $_POST['portal_id'];

    if (!empty($nome_campo) && !empty($tipo_campo)) {
        $stmt = $pdo->prepare(
            "UPDATE campos_portal 
             SET nome_campo = ?, tipo_campo = ?, opcoes_campo = ?, pesquisavel = ?, detalhes_apenas = ? 
             WHERE id = ?"
        );
        $stmt->execute([$nome_campo, $tipo_campo, $opcoes_campo, $pesquisavel, $detalhes_apenas, $campo_id]);
        $_SESSION['mensagem_sucesso'] = "Campo atualizado com sucesso!";
    } else {
        $_SESSION['mensagem_sucesso'] = "Erro: Nome e tipo do campo são obrigatórios.";
    }
    header("Location: gerenciar_campos.php?portal_id=" . $portal_id);
    exit;
}

// Busca os dados atuais do campo para preencher o formulário
$stmt_campo = $pdo->prepare("SELECT * FROM campos_portal WHERE id = ?");
$stmt_campo->execute([$campo_id]);
$campo = $stmt_campo->fetch();
if (!$campo) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Campo - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">
<?php 
$page_title_for_header = 'Editar Campo'; 
include 'admin_header.php'; 
?>
<div class="container-fluid container-custom-padding">
    <div class="row"><div class="col-12">
        <div class="card">
            <div class="card-header"><h4>Formulário de Edição de Campo</h4></div>
            <div class="card-body">
                <form method="POST" action="editar_campo.php?id=<?php echo $campo_id; ?>">
                    <input type="hidden" name="portal_id" value="<?php echo $campo['id_portal']; ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nome_campo" class="form-label">Nome do Campo (Rótulo)</label>
                            <input type="text" class="form-control" id="nome_campo" name="nome_campo" value="<?php echo htmlspecialchars($campo['nome_campo']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tipo_campo" class="form-label">Tipo do Campo</label>
                            <select class="form-select" id="tipo_campo" name="tipo_campo" required>
                                <option value="texto" <?php if($campo['tipo_campo'] == 'texto') echo 'selected'; ?>>Texto Curto</option>
                                <option value="textarea" <?php if($campo['tipo_campo'] == 'textarea') echo 'selected'; ?>>Texto Longo</option>
                                <option value="numero" <?php if($campo['tipo_campo'] == 'numero') echo 'selected'; ?>>Número</option>
                                <option value="moeda" <?php if($campo['tipo_campo'] == 'moeda') echo 'selected'; ?>>Moeda (R$)</option>
                                <option value="data" <?php if($campo['tipo_campo'] == 'data') echo 'selected'; ?>>Data</option>
                                <option value="select" <?php if($campo['tipo_campo'] == 'select') echo 'selected'; ?>>Lista de Opções (Select)</option>
                                <option value="anexo" <?php if($campo['tipo_campo'] == 'anexo') echo 'selected'; ?>>Anexo (Arquivo)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3" id="opcoes_campo_div" style="display: none;">
                        <label for="opcoes_campo" class="form-label">Opções do Campo</label>
                        <input type="text" class="form-control" id="opcoes_campo" name="opcoes_campo" value="<?php echo htmlspecialchars($campo['opcoes_campo']); ?>" placeholder="Ex: Opção 1, Opção 2">
                        <small class="form-text text-muted">Separe as opções com vírgula ou use `tabela:nome_da_tabela`.</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" value="1" id="pesquisavel" name="pesquisavel" <?php if($campo['pesquisavel']) echo 'checked'; ?>>
                                <label class="form-check-label" for="pesquisavel">Tornar este campo pesquisável?</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" value="1" id="detalhes_apenas" name="detalhes_apenas" <?php if($campo['detalhes_apenas']) echo 'checked'; ?>>
                                <label class="form-check-label" for="detalhes_apenas">Listar apenas na página de detalhes?</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    <a href="gerenciar_campos.php?portal_id=<?php echo $campo['id_portal']; ?>" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div></div>
</div>
<?php include 'admin_footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoCampoSelect = document.getElementById('tipo_campo');
    const divOpcoes = document.getElementById('opcoes_campo_div');

    function toggleOpcoes() {
        divOpcoes.style.display = (tipoCampoSelect.value === 'select') ? 'block' : 'none';
    }

    tipoCampoSelect.addEventListener('change', toggleOpcoes);
    // Executa a função ao carregar a página para definir o estado inicial correto
    toggleOpcoes();
});
</script>
</body>
</html>