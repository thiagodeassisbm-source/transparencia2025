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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIC - Caixa de Entrada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7fa; }
        .card-premium { border-radius: 15px !important; border: none !important; box-shadow: 0 4px 12px rgba(0,0,0,0.03) !important; }
        .header-card { background: linear-gradient(135deg, var(--cor-principal, #007bff), #0056b3); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; }
        .table thead th { background-color: #f8f9fa; text-transform: uppercase; font-size: 11px; font-weight: 700; color: #666; border-bottom: 2px solid #eee; padding: 15px; }
        .table tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f1f1; }
        .badge-status { font-weight: 700; text-transform: uppercase; font-size: 10px; padding: 6px 12px; border-radius: 50px; }
        .btn-action { width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 10px; transition: all 0.2s; background: white; border: 1px solid #eee; margin: 0 2px; }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .filter-section { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); margin-bottom: 30px; }
        .btn-white { background: white; color: var(--cor-principal); border: none; }
        .btn-white:hover { background: #f8f9fa; color: var(--cor-principal-dark); }
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
                <div class="alert alert-success alert-dismissible fade show card-premium mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['mensagem_sucesso']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['mensagem_sucesso']); ?>
            <?php endif; ?>

            <!-- Header da Página -->
            <div class="header-card shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h3 class="fw-bold mb-1">Caixa de Entrada e-SIC</h3>
                    <p class="mb-0 opacity-75">Gerencie as solicitações de informação enviadas pelos cidadãos.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="configuracoes_sic.php" class="btn btn-white fw-bold rounded-pill shadow-sm px-4">
                        <i class="bi bi-gear-fill me-1"></i> Configurações
                    </a>
                </div>
            </div>

            <!-- Filtros de Busca -->
            <div class="filter-section animate__animated animate__fadeIn">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small"><i class="bi bi-person me-1"></i> Buscar Solicitante</label>
                        <input type="text" name="busca" class="form-control rounded-pill border-light-subtle shadow-sm" placeholder="Nome do cidadão..." value="<?php echo htmlspecialchars($busca); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted small"><i class="bi bi-calendar-event me-1"></i> Data Início</label>
                        <input type="date" name="data_inicio" class="form-control rounded-pill border-light-subtle shadow-sm" value="<?php echo htmlspecialchars($data_inicio); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted small"><i class="bi bi-calendar-event me-1"></i> Data Fim</label>
                        <input type="date" name="data_fim" class="form-control rounded-pill border-light-subtle shadow-sm" value="<?php echo htmlspecialchars($data_fim); ?>">
                    </div>
                    <div class="col-md-2 d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
                            <i class="bi bi-funnel-fill me-1"></i> Filtrar
                        </button>
                        <a href="sic_inbox.php" class="btn btn-light rounded-pill px-3 border" title="Limpar Filtros"><i class="bi bi-x-lg"></i></a>
                    </div>
                </form>
            </div>

            <!-- Tabela de Solicitações -->
            <div class="card card-premium overflow-hidden animate__animated animate__fadeInUp">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Protocolo</th>
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
                                        <div class="opacity-25 mb-3">
                                            <i class="bi bi-mailbox display-1"></i>
                                        </div>
                                        <h5 class="fw-bold">Nenhum pedido encontrado</h5>
                                        <p class="mb-0">Não há solicitações para os critérios de busca aplicados.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($solicitacoes as $item): 
                                    $status_class = 'bg-primary';
                                    if($item['status'] == 'Finalizada') $status_class = 'bg-success';
                                    if($item['status'] == 'Respondida') $status_class = 'bg-info';
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-3 d-none d-md-flex">
                                                    <i class="bi bi-file-earmark-text"></i>
                                                </div>
                                                <strong class="text-dark"><?php echo htmlspecialchars($item['protocolo']); ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-dark fw-500"><?php echo htmlspecialchars($item['nome_solicitante']); ?></span>
                                        </td>
                                        <td><span class="badge badge-status <?php echo $status_class; ?> shadow-sm"><?php echo htmlspecialchars($item['status']); ?></span></td>
                                        <td>
                                            <span class="text-muted small"><i class="bi bi-clock me-1"></i> <?php echo date('d/m/Y H:i', strtotime($item['data_solicitacao'])); ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="responder_esic.php?id=<?php echo $item['id']; ?>" class="btn-action text-primary" title="Responder / Detalhes">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="sic_excluir.php?id=<?php echo $item['id']; ?>" class="btn-action text-danger" title="Excluir" onclick="return confirm('Excluir esta solicitação permanentemente?')">
                                                    <i class="bi bi-trash-fill"></i>
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

            <p class="text-center mt-4 text-muted small opacity-50">Exibindo <?php echo count($solicitacoes); ?> solicitações filtradas.</p>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>

