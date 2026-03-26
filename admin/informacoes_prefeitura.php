<?php
require_once 'auth_check.php';
require_once '../conexao.php';

if ($_SESSION['admin_user_perfil'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Lógica para salvar as configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configuracoes = $_POST['config'];
    
    $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE chave = ?");
    
    foreach ($configuracoes as $chave => $valor) {
        $stmt->execute([trim($valor), $chave]);
    }

    // Lógica para o upload do logo
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/site/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
        
        $logo_antigo = $_POST['logo_antigo'];
        // Deleta o logo antigo se ele existir
        if (!empty($logo_antigo) && file_exists($logo_antigo)) {
            unlink($logo_antigo);
        }

        $nome_arquivo = 'logo-' . time() . '.' . pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $caminho_destino = $upload_dir . $nome_arquivo;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $caminho_destino)) {
            $stmt->execute([$caminho_destino, 'prefeitura_logo']);
        }
    }

    $_SESSION['mensagem_sucesso'] = "Informações da Prefeitura salvas com sucesso!";
    header("Location: informacoes_prefeitura.php");
    exit;
}

// Busca as configurações atuais do banco para preencher o formulário
$stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'prefeitura_%'");
$config_atuais = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Informações da Prefeitura - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">
<?php 
$page_title_for_header = 'Informações da Prefeitura'; 
include 'admin_header.php'; 
?>
<div class="container-fluid container-custom-padding">
    <div class="row"><div class="col-12">
        <?php
        if (isset($_SESSION['mensagem_sucesso'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' 
               . htmlspecialchars($_SESSION['mensagem_sucesso']) . 
               '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            unset($_SESSION['mensagem_sucesso']);
        }
        ?>
        <div class="card">
            <div class="card-header"><h4>Gerenciar Informações Principais</h4></div>
            <div class="card-body">
                <form method="POST" action="informacoes_prefeitura.php" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título do Portal</label>
                        <input type="text" class="form-control" id="titulo" name="config[prefeitura_titulo]" value="<?php echo htmlspecialchars($config_atuais['prefeitura_titulo'] ?? ''); ?>">
                    </div>
                     <div class="mb-3">
                        <label for="cidade" class="form-label">Cidade / Estado</label>
                        <input type="text" class="form-control" id="cidade" name="config[prefeitura_cidade]" value="<?php echo htmlspecialchars($config_atuais['prefeitura_cidade'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="endereco" class="form-label">Endereço</label>
                        <input type="text" class="form-control" id="endereco" name="config[prefeitura_endereco]" value="<?php echo htmlspecialchars($config_atuais['prefeitura_endereco'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="text" class="form-control" id="telefone" name="config[prefeitura_telefone]" value="<?php echo htmlspecialchars($config_atuais['prefeitura_telefone'] ?? ''); ?>">
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Logo Atual</label><br>
                        <?php if(!empty($config_atuais['prefeitura_logo'])): ?>
                            <img src="<?php echo htmlspecialchars($config_atuais['prefeitura_logo']); ?>" alt="Logo Atual" class="img-thumbnail mb-2" style="max-height: 80px;">
                        <?php else: ?>
                            <p class="text-muted">Nenhum logo enviado.</p>
                        <?php endif; ?>
                        <input type="hidden" name="logo_antigo" value="<?php echo htmlspecialchars($config_atuais['prefeitura_logo'] ?? ''); ?>">
                    </div>
                     <div class="mb-3">
                        <label for="logo" class="form-label">Enviar Novo Logo (Opcional)</label>
                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                        <small class="form-text text-muted">Envie apenas se desejar substituir o logo atual. Formatos recomendados: PNG, SVG.</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Salvar Informações</button>
                </form>
            </div>
        </div>
    </div></div>
</div>
<?php include 'admin_footer.php'; ?>
</body>
</html>