<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Apenas admins podem gerenciar páginas
if ($_SESSION['admin_user_perfil'] !== 'admin') {
    $_SESSION['mensagem_sucesso'] = "Acesso negado.";
    header("Location: index.php");
    exit;
}

// Lógica para excluir uma página
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_pagina'])) {
    $id_pagina = filter_input(INPUT_POST, 'id_pagina', FILTER_VALIDATE_INT);
    if ($id_pagina) {
        $stmt = $pdo->prepare("DELETE FROM paginas WHERE id = ?");
        $stmt->execute([$id_pagina]);
        $_SESSION['mensagem_sucesso'] = "Página excluída com sucesso!";
    }
    header("Location: gerenciar_paginas.php");
    exit;
}

$paginas = $pdo->query("SELECT id, titulo, slug, data_modificacao FROM paginas ORDER BY titulo ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Páginas - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">

<?php 
$page_title_for_header = 'Gerenciar Páginas'; 
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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Páginas Cadastradas</span>
                    <a href="editor_pagina.php" class="btn btn-success btn-sm">
                        <i class="bi bi-plus-circle"></i> Criar Nova Página
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Título</th>
                                <th>Última Modificação</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($paginas)): ?>
                                <tr><td colspan="3" class="text-center">Nenhuma página foi criada ainda.</td></tr>
                            <?php else: ?>
                                <?php foreach ($paginas as $pagina): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pagina['titulo']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($pagina['data_modificacao'])); ?></td>
                                    <td class="text-end">
                                        <a href="editor_pagina.php?id=<?php echo $pagina['id']; ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Editar</a>
                                        <form method="POST" action="gerenciar_paginas.php" class="d-inline ms-1" onsubmit="return confirm('Tem certeza que deseja excluir esta página?');">
                                            <input type="hidden" name="id_pagina" value="<?php echo $pagina['id']; ?>">
                                            <input type="hidden" name="delete_pagina" value="1">
                                            <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> Excluir</button>
                                        </form>
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