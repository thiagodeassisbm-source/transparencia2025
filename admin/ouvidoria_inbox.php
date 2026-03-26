<?php
require_once 'auth_check.php';
require_once '../conexao.php';

$stmt = $pdo->query("SELECT id, protocolo, assunto, nome_cidadao, status, data_criacao FROM ouvidoria_manifestacoes ORDER BY data_criacao DESC");
$manifestacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ouvidoria - Caixa de Entrada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">
<?php 
$page_title_for_header = 'Ouvidoria - Caixa de Entrada'; 
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Manifestações Recebidas</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Protocolo</th>
                            <th>Assunto</th>
                            <th>Cidadão</th>
                            <th>Status</th>
                            <th>Recebido em</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($manifestacoes)): ?>
                            <tr><td colspan="6" class="text-center">Nenhuma manifestação encontrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($manifestacoes as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['protocolo']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['assunto']); ?></td>
                                    <td><?php echo htmlspecialchars($item['nome_cidadao'] ?? 'Anônimo'); ?></td>
                                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($item['status']); ?></span></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($item['data_criacao'])); ?></td>
                                    <td class="text-end">
                                        <a href="responder_manifestacao.php?id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-pencil-fill"></i> Responder / Ver Detalhes
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div></div>
</div>
<?php include 'admin_footer.php'; ?>
</body>
</html>