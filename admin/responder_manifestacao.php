<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

$manifestacao_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$manifestacao_id) { header("Location: ouvidoria_inbox.php"); exit; }

// Processa o formulário de resposta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_resposta'])) {
    $novo_status = $_POST['status'];
    $nova_resposta = $_POST['resposta'];
    
    $pref_id = $_SESSION['id_prefeitura'];
    $stmt = $pdo->prepare("UPDATE ouvidoria_manifestacoes SET status = ?, resposta = ?, data_resposta = NOW() WHERE id = ? AND id_prefeitura = ?");
    $stmt->execute([$novo_status, $nova_resposta, $manifestacao_id, $pref_id]);
    $st_proto = $pdo->prepare('SELECT protocolo FROM ouvidoria_manifestacoes WHERE id = ? AND id_prefeitura = ?');
    $st_proto->execute([$manifestacao_id, $pref_id]);
    $proto = $st_proto->fetchColumn();
    registrar_log(
        $pdo,
        'EDIÇÃO',
        'ouvidoria_manifestacoes',
        'Atualizou resposta/status ouvidoria — protocolo ' . ($proto ?: '#' . $manifestacao_id) . " (status: $novo_status)."
    );

    $_SESSION['mensagem_sucesso'] = "Manifestação respondida com sucesso!";
    header("Location: ouvidoria_inbox.php");
    exit;
}

// Busca a manifestação para exibir no formulário
$pref_id = $_SESSION['id_prefeitura'];
$stmt = $pdo->prepare("SELECT * FROM ouvidoria_manifestacoes WHERE id = ? AND id_prefeitura = ?");
$stmt->execute([$manifestacao_id, $pref_id]);
$manifestacao = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$manifestacao) { header("Location: ouvidoria_inbox.php"); exit; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Responder Manifestação - Ouvidoria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">

<?php 
$page_title_for_header = 'Responder Manifestação';
include 'admin_header.php'; 
?>

<div class="container-fluid container-custom-padding">
    <div class="row"><div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4>Detalhes da Manifestação - Protocolo: <?php echo htmlspecialchars($manifestacao['protocolo']); ?></h4>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">Nome do Cidadão:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($manifestacao['nome_cidadao'] ?: 'Anônimo'); ?></dd>
                    <dt class="col-sm-3">E-mail:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($manifestacao['email'] ?: 'Não informado'); ?></dd>
                    <dt class="col-sm-3">Telefone:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($manifestacao['telefone'] ?: 'Não informado'); ?></dd>
                    <hr class="my-3">
                    <dt class="col-sm-3">Tipo de Manifestação:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($manifestacao['tipo_manifestacao']); ?></dd>
                    <dt class="col-sm-3">Assunto:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($manifestacao['assunto']); ?></dd>
                    <dt class="col-sm-3">Descrição:</dt>
                    <dd class="col-sm-9"><p><?php echo nl2br(htmlspecialchars($manifestacao['descricao'])); ?></p></dd>
                </dl>
                <hr>
                <form method="POST" action="responder_manifestacao.php?id=<?php echo $manifestacao_id; ?>">
                    <div class="mb-3">
                        <label for="status" class="form-label fw-bold">Alterar Status</label>
                        <select id="status" name="status" class="form-select" style="max-width: 250px;">
                            <?php $status_atual = $manifestacao['status']; ?>
                            <option <?php if($status_atual == 'Recebida') echo 'selected'; ?>>Recebida</option>
                            <option <?php if($status_atual == 'Em Análise') echo 'selected'; ?>>Em Análise</option>
                            <option <?php if($status_atual == 'Respondida') echo 'selected'; ?>>Respondida</option>
                            <option <?php if($status_atual == 'Finalizada') echo 'selected'; ?>>Finalizada</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="resposta" class="form-label fw-bold">Resposta da Ouvidoria</label>
                        <textarea id="resposta" name="resposta" class="form-control" rows="6"><?php echo htmlspecialchars($manifestacao['resposta'] ?? ''); ?></textarea>
                    </div>
                    <input type="hidden" name="salvar_resposta" value="1">
                    <button type="submit" class="btn btn-success">Salvar Resposta e Atualizar Status</button>
                    <a href="ouvidoria_inbox.php" class="btn btn-secondary">Voltar para a Caixa de Entrada</a>
                </form>
            </div>
        </div>
    </div></div>
</div>
<?php include 'admin_footer.php'; ?>
</body>
</html>