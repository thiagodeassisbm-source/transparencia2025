<?php
require_once 'auth_check.php';
require_once '../conexao.php';

$solicitacao_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$solicitacao_id) {
    header("Location: sic_inbox.php");
    exit;
}

// Processa o formulário de resposta quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_resposta'])) {
    $novo_status = $_POST['status'];
    $nova_resposta = $_POST['resposta'];
    
    $stmt = $pdo->prepare("UPDATE sic_solicitacoes SET status = ?, resposta = ?, data_resposta = NOW() WHERE id = ?");
    $stmt->execute([$novo_status, $nova_resposta, $solicitacao_id]);
    
    $_SESSION['mensagem_sucesso'] = "Solicitação respondida com sucesso!";
    header("Location: sic_inbox.php");
    exit;
}

// Busca a solicitação para exibir no formulário
$stmt = $pdo->prepare("SELECT * FROM sic_solicitacoes WHERE id = ?");
$stmt->execute([$solicitacao_id]);
$solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$solicitacao) {
    $_SESSION['mensagem_sucesso'] = "Solicitação não encontrada.";
    header("Location: sic_inbox.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Responder Solicitação SIC - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">

<?php 
$page_title_for_header = 'Responder Solicitação SIC';
include 'admin_header.php'; 
?>

<div class="container-fluid container-custom-padding">
    <div class="row"><div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4>Detalhes da Solicitação - Protocolo: <?php echo htmlspecialchars($solicitacao['protocolo']); ?></h4>
            </div>
            <div class="card-body">
                <h5>Dados do Solicitante</h5>
                <dl class="row">
                    <dt class="col-sm-3">Nome:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($solicitacao['nome_solicitante']); ?></dd>

                    <dt class="col-sm-3">Documento:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($solicitacao['tipo_documento'] ?? 'Não informado') . ': ' . htmlspecialchars($solicitacao['numero_documento'] ?? 'Não informado'); ?></dd>

                    <dt class="col-sm-3">E-mail:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($solicitacao['email'] ?: 'Não informado'); ?></dd>

                    <dt class="col-sm-3">Telefone:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($solicitacao['telefone'] ?: 'Não informado'); ?></dd>
                </dl>
                
                <hr>

                <h5>Pedido de Informação</h5>
                <dl class="row">
                    <dt class="col-sm-3">Data da Solicitação:</dt>
                    <dd class="col-sm-9"><?php echo date('d/m/Y H:i', strtotime($solicitacao['data_solicitacao'])); ?></dd>

                    <dt class="col-sm-3">Descrição do Pedido:</dt>
                    <dd class="col-sm-9"><p><?php echo nl2br(htmlspecialchars($solicitacao['descricao_pedido'])); ?></p></dd>
                </dl>
                
                <hr class="my-4">

                <h4>Resposta do Órgão</h4>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $solicitacao_id; ?>">
                    <div class="mb-3">
                        <label for="status" class="form-label fw-bold">Alterar Status da Solicitação</label>
                        <select id="status" name="status" class="form-select" style="max-width: 250px;">
                            <?php $status_atual = $solicitacao['status']; ?>
                            <option <?php if($status_atual == 'Recebido') echo 'selected'; ?>>Recebido</option>
                            <option <?php if($status_atual == 'Em Análise') echo 'selected'; ?>>Em Análise</option>
                            <option <?php if($status_atual == 'Respondida') echo 'selected'; ?>>Respondida</option>
                            <option <?php if($status_atual == 'Finalizada') echo 'selected'; ?>>Finalizada</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="resposta" class="form-label fw-bold">Escreva a Resposta</label>
                        <textarea id="resposta" name="resposta" class="form-control" rows="8"><?php echo htmlspecialchars($solicitacao['resposta'] ?? ''); ?></textarea>
                    </div>
                    <input type="hidden" name="salvar_resposta" value="1">
                    <button type="submit" class="btn btn-success">Salvar Resposta e Atualizar Status</button>
                    <a href="sic_inbox.php" class="btn btn-secondary">Voltar para a Caixa de Entrada</a>
                </form>
            </div>
        </div>
    </div></div>
</div>

<?php include 'admin_footer.php'; ?>
</body>
</html>