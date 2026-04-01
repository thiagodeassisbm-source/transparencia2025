<?php
require_once 'auth_check.php';
require_once '../conexao.php';

$pref_id = $_SESSION['id_prefeitura'];

$busca = trim($_GET['busca'] ?? '');
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

$sql = "SELECT id, protocolo, assunto, nome_cidadao, status, data_criacao FROM ouvidoria_manifestacoes WHERE id_prefeitura = ?";
$params = [$pref_id];

if ($busca !== '') {
    $sql .= " AND (nome_cidadao LIKE ? OR assunto LIKE ? OR protocolo LIKE ?)";
    $like = '%' . $busca . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($data_inicio !== '') {
    $sql .= " AND DATE(data_criacao) >= ?";
    $params[] = $data_inicio;
}

if ($data_fim !== '') {
    $sql .= " AND DATE(data_criacao) <= ?";
    $params[] = $data_fim;
}

$sql .= " ORDER BY data_criacao DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$manifestacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title_for_header = 'Ouvidoria - Caixa de Entrada';
include 'admin_header.php';
?>

<style>
    .ouv-inbox-wrap { font-family: 'Inter', system-ui, sans-serif; }
    .ouv-inbox-wrap .card-premium { border-radius: 15px !important; border: none !important; box-shadow: 0 4px 12px rgba(0,0,0,0.03) !important; }
    /* Gradiente teal — distinto do card azul do e-SIC */
    .header-card-ouvidoria {
        background: linear-gradient(135deg, #0d9488 0%, #0f766e 55%, #115e59 100%);
        color: #fff;
        padding: 28px 30px;
        border-radius: 15px;
        margin-bottom: 24px;
        box-shadow: 0 8px 24px rgba(15, 118, 110, 0.25);
    }
    .header-card-ouvidoria .btn-white-ouv {
        background: #fff;
        color: #0f766e;
        border: none;
        font-weight: 600;
    }
    .header-card-ouvidoria .btn-white-ouv:hover {
        background: #f0fdfa;
        color: #134e4a;
    }
    .ouv-inbox-wrap .table thead th {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 11px;
        font-weight: 700;
        color: #666;
        border-bottom: 2px solid #eee;
        padding: 15px;
    }
    .ouv-inbox-wrap .table tbody td {
        padding: 15px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f1f1;
    }
    .ouv-inbox-wrap .badge-status {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 10px;
        padding: 6px 12px;
        border-radius: 50px;
    }
    .ouv-inbox-wrap .btn-action {
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        transition: all 0.2s;
        background: #fff;
        border: 1px solid #eee;
        margin: 0 2px;
    }
    .ouv-inbox-wrap .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .ouv-inbox-wrap .filter-section {
        background: #fff;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        margin-bottom: 24px;
    }
    .ouv-inbox-wrap .protocol-icon {
        background: rgba(13, 148, 136, 0.12);
        color: #0f766e;
    }
</style>

<div class="container-fluid container-custom-padding py-4 ouv-inbox-wrap">
    <div class="row">
        <div class="col-12">

            <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                <div class="alert alert-success alert-dismissible fade show card-premium mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_SESSION['mensagem_sucesso']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['mensagem_sucesso']); ?>
            <?php endif; ?>

            <div class="header-card-ouvidoria d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h3 class="fw-bold mb-1">Caixa de Entrada — Ouvidoria</h3>
                    <p class="mb-0 opacity-90 small">Gerencie as manifestações enviadas pelos cidadãos (elogios, sugestões, reclamações, solicitações e denúncias).</p>
                </div>
                <div>
                    <a href="config_ouvidoria.php" class="btn btn-white-ouv fw-bold rounded-pill shadow-sm px-4">
                        <i class="bi bi-gear-fill me-1"></i> Configurações
                    </a>
                </div>
            </div>

            <div class="filter-section">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small"><i class="bi bi-search me-1"></i> Buscar</label>
                        <input type="text" name="busca" class="form-control rounded-pill border-light-subtle shadow-sm" placeholder="Cidadão, assunto ou protocolo..." value="<?php echo htmlspecialchars($busca); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted small"><i class="bi bi-calendar-event me-1"></i> Data início</label>
                        <input type="date" name="data_inicio" class="form-control rounded-pill border-light-subtle shadow-sm" value="<?php echo htmlspecialchars($data_inicio); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted small"><i class="bi bi-calendar-event me-1"></i> Data fim</label>
                        <input type="date" name="data_fim" class="form-control rounded-pill border-light-subtle shadow-sm" value="<?php echo htmlspecialchars($data_fim); ?>">
                    </div>
                    <div class="col-md-2 d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn rounded-pill px-4 shadow-sm fw-bold text-white" style="background: linear-gradient(135deg, #0d9488, #0f766e); border: none;">
                            <i class="bi bi-funnel-fill me-1"></i> Filtrar
                        </button>
                        <a href="ouvidoria_inbox.php" class="btn btn-light rounded-pill px-3 border" title="Limpar filtros"><i class="bi bi-x-lg"></i></a>
                    </div>
                </form>
            </div>

            <div class="card card-premium overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Protocolo</th>
                                <th>Assunto</th>
                                <th>Cidadão</th>
                                <th>Status</th>
                                <th>Recebido em</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($manifestacoes)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <div class="opacity-25 mb-3">
                                            <i class="bi bi-inbox display-1"></i>
                                        </div>
                                        <h5 class="fw-bold">Nenhuma manifestação encontrada</h5>
                                        <p class="mb-0">Não há registros para os filtros aplicados.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($manifestacoes as $item):
                                    $status_class = 'bg-primary';
                                    if ($item['status'] === 'Finalizada') {
                                        $status_class = 'bg-success';
                                    }
                                    if ($item['status'] === 'Respondida') {
                                        $status_class = 'bg-info';
                                    }
                                    if ($item['status'] === 'Em Análise') {
                                        $status_class = 'bg-warning text-dark';
                                    }
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="protocol-icon p-2 rounded-3 me-3 d-none d-md-flex">
                                                    <i class="bi bi-file-earmark-text"></i>
                                                </div>
                                                <strong class="text-dark"><?php echo htmlspecialchars($item['protocolo']); ?></strong>
                                            </div>
                                        </td>
                                        <td><span class="text-dark fw-500"><?php echo htmlspecialchars($item['assunto']); ?></span></td>
                                        <td><?php echo htmlspecialchars($item['nome_cidadao'] ?? '—'); ?></td>
                                        <td><span class="badge badge-status <?php echo $status_class; ?> shadow-sm"><?php echo htmlspecialchars($item['status']); ?></span></td>
                                        <td>
                                            <span class="text-muted small"><i class="bi bi-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($item['data_criacao'])); ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="responder_manifestacao.php?id=<?php echo (int)$item['id']; ?>" class="btn-action text-primary" title="Responder / Detalhes">
                                                    <i class="bi bi-pencil-square"></i>
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

            <p class="text-center mt-4 text-muted small opacity-75">Exibindo <?php echo count($manifestacoes); ?> manifestação(ões) filtrada(s).</p>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
