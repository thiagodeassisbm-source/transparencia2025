<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

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

    registrar_log($pdo, 'EDIÇÃO', 'configuracoes', "Atualizou as configurações da Ouvidoria.");

    $_SESSION['mensagem_sucesso'] = "Configurações da Ouvidoria salvas com sucesso!";
    header("Location: config_ouvidoria.php");
    exit;
}

// Busca as configurações atuais do banco para preencher o formulário
$stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'ouvidoria_%'");
$configuracoes_atuais = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Configurações da Ouvidoria - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">
<?php 
$page_title_for_header = 'Configurações da Ouvidoria'; 
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
            <div class="card-header"><h4>Informações de Contato e Atendimento Presencial</h4></div>
            <div class="card-body">
                <form method="POST" action="config_ouvidoria.php">
                    <div class="mb-3">
                        <label for="ouvidoria_setor" class="form-label">Setor</label>
                        <input type="text" class="form-control" id="ouvidoria_setor" name="config[ouvidoria_setor]" value="<?php echo htmlspecialchars($configuracoes_atuais['ouvidoria_setor'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="ouvidoria_endereco" class="form-label">Endereço</label>
                        <input type="text" class="form-control" id="ouvidoria_endereco" name="config[ouvidoria_endereco]" value="<?php echo htmlspecialchars($configuracoes_atuais['ouvidoria_endereco'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="ouvidoria_email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="ouvidoria_email" name="config[ouvidoria_email]" value="<?php echo htmlspecialchars($configuracoes_atuais['ouvidoria_email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="ouvidoria_telefone" class="form-label">Telefone</label>
                        <input type="text" class="form-control" id="ouvidoria_telefone" name="config[ouvidoria_telefone]" value="<?php echo htmlspecialchars($configuracoes_atuais['ouvidoria_telefone'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                </form>
            </div>
        </div>
    </div></div>
</div>
<?php include 'admin_footer.php'; ?>
</body>
</html>