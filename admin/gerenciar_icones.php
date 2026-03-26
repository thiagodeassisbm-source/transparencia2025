<?php
session_start();
require_once '../conexao.php';

// Lógica para processar o formulário de novo ícone
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_icone'])) {
    $titulo = trim($_POST['titulo']);
    $link_url = trim($_POST['link_url']);
    $ordem = (int)$_POST['ordem'];
    $caminho_icone = '';

    // Validação básica
    if (!empty($titulo) && !empty($link_url) && isset($_FILES['icone_upload']) && $_FILES['icone_upload']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        $nome_arquivo = uniqid() . '-' . basename($_FILES['icone_upload']['name']);
        $caminho_destino = $upload_dir . $nome_arquivo;

        if (move_uploaded_file($_FILES['icone_upload']['tmp_name'], $caminho_destino)) {
            $caminho_icone = $caminho_destino;
            $stmt = $pdo->prepare("INSERT INTO icones_menu (titulo, caminho_icone, link_url, ordem) VALUES (?, ?, ?, ?)");
            $stmt->execute([$titulo, $caminho_icone, $link_url, $ordem]);
            $_SESSION['mensagem_sucesso'] = "Ícone cadastrado com sucesso!";
        } else {
            $_SESSION['mensagem_sucesso'] = "Erro ao mover o arquivo de upload.";
        }
    } else {
        $_SESSION['mensagem_sucesso'] = "Erro: Todos os campos são obrigatórios e o upload do ícone é necessário.";
    }
    header("Location: gerenciar_icones.php");
    exit;
}

// Busca os ícones existentes para listar na página
$icones = $pdo->query("SELECT id, titulo, caminho_icone, link_url, ordem FROM icones_menu ORDER BY ordem ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Ícones - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light-subtle">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Painel Administrativo</a>
    </div>
</nav>
<div class="container mt-4">
    <h2>Gerenciar Ícones da Página Inicial</h2>
    
    <?php
    if (isset($_SESSION['mensagem_sucesso'])) {
        echo '<div class="alert alert-info">' . $_SESSION['mensagem_sucesso'] . '</div>';
        unset($_SESSION['mensagem_sucesso']);
    }
    ?>

    <div class="card mb-4">
        <div class="card-header">Cadastrar Novo Ícone</div>
        <div class="card-body">
            <form method="POST" action="gerenciar_icones.php" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="titulo" class="form-label">Título do Ícone</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="icone_upload" class="form-label">Arquivo de Imagem (PNG, JPG)</label>
                        <input type="file" class="form-control" id="icone_upload" name="icone_upload" accept="image/*" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="link_url" class="form-label">Link de Destino (URL)</label>
                        <input type="url" class="form-control" id="link_url" name="link_url" placeholder="https://..." required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="ordem" class="form-label">Ordem</label>
                        <input type="number" class="form-control" id="ordem" name="ordem" value="0">
                    </div>
                </div>
                <input type="hidden" name="add_icone" value="1">
                <button type="submit" class="btn btn-success">Salvar Ícone</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Ícones Cadastrados</div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($icones as $icone): ?>
                    <div class="col-md-2 text-center mb-3">
                        <img src="<?php echo htmlspecialchars($icone['caminho_icone']); ?>" alt="<?php echo htmlspecialchars($icone['titulo']); ?>" class="img-thumbnail mb-2" style="max-width: 80px;">
                        <p><?php echo htmlspecialchars($icone['titulo']); ?></p>
                        </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>