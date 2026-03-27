<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

if ($_SESSION['admin_user_perfil'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$categoria_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$categoria_id) { header("Location: gerenciar_categorias.php"); exit; }

// Processa o formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    if (!empty($nome)) {
        $stmt = $pdo->prepare("UPDATE categorias SET nome = ? WHERE id = ?");
        $stmt->execute([$nome, $categoria_id]);
        
        registrar_log($pdo, 'EDIÇÃO', 'categorias', "Renomeou a categoria para: $nome (ID: $categoria_id)");
        
        $_SESSION['mensagem_sucesso'] = "Categoria atualizada com sucesso!";
        header("Location: gerenciar_categorias.php");
        exit;
    }
}

// Busca os dados da categoria para preencher o formulário
$stmt = $pdo->prepare("SELECT nome FROM categorias WHERE id = ?");
$stmt->execute([$categoria_id]);
$categoria = $stmt->fetch();

if (!$categoria) { header("Location: gerenciar_categorias.php"); exit; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Categoria - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">

<?php 
$page_title_for_header = 'Editar Categoria'; 
include 'admin_header.php'; 
?>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">Formulário de Edição</div>
                <div class="card-body">
                    <form method="POST" action="editar_categoria.php?id=<?php echo $categoria_id; ?>">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome da Categoria</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($categoria['nome']); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        <a href="gerenciar_categorias.php" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center p-3 bg-light mt-4">
    &copy; <?php echo date('Y'); ?> - Todos os direitos reservados.
</footer>
</body>
</html>