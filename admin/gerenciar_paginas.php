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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pagina'])) {
    $id_pagina = filter_input(INPUT_POST, 'id_pagina', FILTER_VALIDATE_INT);
    $pref_id = $_SESSION['id_prefeitura'];
    if ($id_pagina) {
        // Verifica se a página pertence à prefeitura
        $stmt_check = $pdo->prepare("DELETE FROM paginas WHERE id = ? AND id_prefeitura = ?");
        $stmt_check->execute([$id_pagina, $pref_id]);
        $_SESSION['mensagem_sucesso'] = "Página excluída com sucesso!";
    }
    header("Location: gerenciar_paginas.php");
    exit;
}

$pref_id = $_SESSION['id_prefeitura'];
$stmt_get = $pdo->prepare("SELECT id, titulo, slug, data_modificacao FROM paginas WHERE id_prefeitura = ? ORDER BY titulo ASC");
$stmt_get->execute([$pref_id]);
$paginas = $stmt_get->fetchAll();
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

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-12">
            
            <div class="row align-items-center mb-4">
                <div class="col-md-6">
                    <h3 class="fw-bold text-dark mb-1">Páginas de Conteúdo</h3>
                    <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i> Gerencie textos institucionais, legislações e informativos do portal.</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <a href="editor_pagina.php" class="btn btn-primary shadow-sm rounded-pill px-4 py-2">
                        <i class="bi bi-plus-circle me-2"></i> Criar Nova Página
                    </a>
                </div>
            </div>

            <?php
            if (isset($_SESSION['mensagem_sucesso'])) {
                echo '<div class="alert alert-success border-0 shadow-sm alert-dismissible fade show rounded-3" role="alert">' 
                   . '<i class="bi bi-check-circle-fill me-2"></i>' . htmlspecialchars($_SESSION['mensagem_sucesso']) . 
                   '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                unset($_SESSION['mensagem_sucesso']);
            }
            ?>

            <!-- Card Informativo -->
            <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); color: #fff; border-radius: 12px;">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="me-4 d-none d-md-block">
                        <div class="bg-white bg-opacity-20 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                            <i class="bi bi-file-richtext-fill fs-2"></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Estrutura de Páginas Institucionais</h5>
                        <p class="mb-0 opacity-90 small">
                            Use esta seção para criar páginas que não seguem um padrão de planilha de dados, como: <strong>Lista de Espera, Regras do IPTU, Organogramas</strong> ou <strong>Relatórios Anuais</strong>. Você pode formatar o texto e anexar documentos PDF/DOCX.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-ul me-2 text-primary"></i>Suas Páginas Cadastradas</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Título</th>
                                <th>Última Modificação</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($paginas)): ?>
                                <tr><td colspan="3" class="text-center">Nenhuma página foi criada ainda.</td></tr>
                            <?php else: ?>
                                <?php foreach ($paginas as $pagina): ?>
                                <tr class="align-middle">
                                    <td class="p-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 me-3">
                                                <i class="bi bi-file-richtext fs-5"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($pagina['titulo']); ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;">URL: /pagina.php?slug=<?php echo htmlspecialchars($pagina['slug']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-3 text-muted">
                                        <i class="bi bi-clock me-1"></i> <?php echo date('d/m/Y H:i', strtotime($pagina['data_modificacao'])); ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="editor_pagina.php?id=<?php echo $pagina['id']; ?>" class="btn btn-outline-primary btn-sm rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Editar Página">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="gerenciar_paginas.php" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta página?');">
                                                <input type="hidden" name="id_pagina" value="<?php echo $pagina['id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Excluir Página">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
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