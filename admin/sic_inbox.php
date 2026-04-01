<?php
require_once 'auth_check.php';
require_once '../conexao.php';

$pref_id = $_SESSION['id_prefeitura'];

// Filtros
$busca = trim($_GET['busca'] ?? '');
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

$sql = "SELECT id, protocolo, nome_solicitante, status, data_solicitacao FROM sic_solicitacoes WHERE id_prefeitura = ?";
$params = [$pref_id];

if ($busca !== '') {
    $sql .= " AND nome_solicitante LIKE ?";
    $params[] = "%$busca%";
}

if ($data_inicio !== '') {
    $sql .= " AND DATE(data_solicitacao) >= ?";
    $params[] = $data_inicio;
}

if ($data_fim !== '') {
    $sql .= " AND DATE(data_solicitacao) <= ?";
    $params[] = $data_fim;
}

$sql .= " ORDER BY data_solicitacao DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SIC - Caixa de Entrada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .badge-status { font-weight: 700; text-transform: uppercase; font-size: 11px; padding: 6px 12px; border-radius: 50px; }
    </style>
</head>
<body class="bg-light-subtle">

<?php 
$page_title_for_header = 'SIC - Caixa de Entrada'; 
include 'admin_header.php'; 
?>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-12">
            <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['mensagem_sucesso']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['mensagem_sucesso']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['mensagem_erro'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['mensagem_erro']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['mensagem_erro']); ?>
            <?php endif; ?>

            <!-- Filtros de Busca -->
            <div class="card mb-4 border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted small">Buscar por Solicitante</label>
                            <input type="text" name="busca" class="form-control" placeholder="Nome do cidadão..." value="<?php echo htmlspecialchars($busca); ?>">
                        </div>
                        <div class="col-md-3 mt-md-0">
                            <label class="form-label fw-bold text-muted small">Data Início</label>
                            <input type="date" name="data_inicio" class="form-control" value="<?php echo htmlspecialchars($data_inicio); ?>">
                        </div>
                        <div class="col-md-3 mt-md-0">
                            <label class="form-label fw-bold text-muted small">Data Fim</label>
                            <input type="date" name="data_fim" class="form-control" value="<?php echo htmlspecialchars($data_fim); ?>">
                        </div>
                        <div class="col-md-2 mt-md-0 d-grid gap-2 d-md-flex">
                            <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center">
                                <i class="bi bi-search me-2"></i> Filtrar
                            </button>
                            <a href="sic_inbox.php" class="btn btn-light text-decoration-none d-flex align-items-center justify-content-center">Limpar</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela de Solicitações -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold">Solicitações de Informação Recebidas</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th class="px-4">Protocolo</th>
                                <th>Solicitante</th>
                                <th>Status</th>
                                <th>Recebido em</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($solicitacoes)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted italic">
                                        <i class="bi bi-folder2-open display-4 d-block mb-2 opacity-25"></i>
                                        Nenhuma solicitação encontrada para os filtros aplicados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($solicitacoes as $item): 
                                    $status_class = 'bg-primary';
                                    if($item['status'] == 'Finalizada') $status_class = 'bg-success';
                                    if($item['status'] == 'Respondida') $status_class = 'bg-info';
                                ?>
                                    <tr>
                                        <td class="px-4"><strong><?php echo htmlspecialchars($item['protocolo']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['nome_solicitante']); ?></td>
                                        <td><span class="badge badge-status <?php echo $status_class; ?>"><?php echo htmlspecialchars($item['status']); ?></span></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($item['data_solicitacao'])); ?></td>
                                        <td class="text-center">
                                            <div class="btn-group shadow-sm rounded-3">
                                                <a href="responder_esic.php?id=<?php echo $item['id']; ?>" class="btn btn-light btn-sm fw-bold border" title="Responder / Ver Detalhes">
                                                    <i class="bi bi-pencil-fill text-primary"></i> <span class="d-none d-lg-inline ms-1">Responder</span>
                                                </a>
                                                <a href="sic_excluir.php?id=<?php echo $item['id']; ?>" class="btn btn-light btn-sm fw-bold border" title="Excluir Solicitação" onclick="return confirm('Tem certeza que deseja excluir esta solicitação permanentemente?')">
                                                    <i class="bi bi-trash-fill text-danger"></i>
                                                </a>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>