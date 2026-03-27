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

    registrar_log($pdo, 'EDIÇÃO', 'configuracoes', "Atualizou as configurações do e-SIC.");

    $_SESSION['mensagem_sucesso'] = "Configurações do SIC salvas com sucesso!";
    header("Location: configuracoes_sic.php");
    exit;
}

// Busca as configurações atuais do banco para preencher o formulário
$stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'sic_%'");
$config_atuais = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Configurações do SIC - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">
<?php 
$page_title_for_header = 'Configurações do SIC'; 
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
            <div class="card-header"><h4>Informações de Contato do SIC Físico</h4></div>
            <div class="card-body">
                <form method="POST" action="configuracoes_sic.php">
                    <div class="mb-3">
                        <label for="sic_setor" class="form-label">Setor</label>
                        <input type="text" class="form-control" id="sic_setor" name="config[sic_setor]" value="<?php echo htmlspecialchars($config_atuais['sic_setor'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="sic_endereco" class="form-label">Endereço</label>
                        <input type="text" class="form-control" id="sic_endereco" name="config[sic_endereco]" value="<?php echo htmlspecialchars($config_atuais['sic_endereco'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="sic_responsavel" class="form-label">Responsável</label>
                        <input type="text" class="form-control" id="sic_responsavel" name="config[sic_responsavel]" value="<?php echo htmlspecialchars($config_atuais['sic_responsavel'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="sic_email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="sic_email" name="config[sic_email]" value="<?php echo htmlspecialchars($config_atuais['sic_email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="sic_telefone" class="form-label">Telefone</label>
                        <input type="text" class="form-control" id="sic_telefone" name="config[sic_telefone]" value="<?php echo htmlspecialchars($config_atuais['sic_telefone'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="sic_horario" class="form-label">Horário de Funcionamento</label>
                        <input type="text" class="form-control" id="sic_horario" name="config[sic_horario]" value="<?php echo htmlspecialchars($config_atuais['sic_horario'] ?? ''); ?>">
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