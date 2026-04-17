<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Bloqueia se não for superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

// Filtros básicos
$filtro_pref = $_GET['pref'] ?? '';
$filtro_data = $_GET['data'] ?? '';
$filtro_acao = $_GET['acao'] ?? '';

$sql = "
    SELECT l.*, p.nome as prefeitura_nome 
    FROM logs_sistema l
    LEFT JOIN prefeituras p ON l.id_prefeitura = p.id
    WHERE 1=1
";
$params = [];

if ($filtro_pref !== '') {
    if ($filtro_pref === '0') {
        $sql .= " AND (l.id_prefeitura IS NULL OR l.id_prefeitura = 0)";
    } else {
        $sql .= " AND l.id_prefeitura = ?";
        $params[] = $filtro_pref;
    }
}
if ($filtro_data) {
    $sql .= " AND DATE(l.horario) = ?";
    $params[] = $filtro_data;
}
if ($filtro_acao) {
    $sql .= " AND l.acao = ?";
    $params[] = $filtro_acao;
}

$sql .= " ORDER BY l.horario DESC LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca lista de prefeituras para o filtro
$prefeituras = $pdo->query("SELECT id, nome FROM prefeituras ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

$page_title_for_header = 'Auditoria Global de Operações';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h4 class="mb-0 fw-bold"><i class="bi bi-activity text-primary me-2"></i> Monitoramento Global do SaaS</h4>
            <p class="text-muted small mb-0 px-1">Filtre e analise todas as ações realizadas no sistema.</p>
        </div>
        <div class="col-auto">
             <button onclick="window.location.reload()" class="btn btn-outline-secondary btn-sm rounded-pill px-4"><i class="bi bi-arrow-clockwise me-2"></i> Atualizar</button>
        </div>
    </div>

    <!-- Barra de Filtros Avançada -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light bg-gradient">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted"><i class="bi bi-building me-1"></i> Filtrar por Prefeitura</label>
                    <select name="pref" class="form-select border-0 shadow-sm rounded-3">
                        <option value="">Todas as Instâncias</option>
                        <option value="0" <?php echo $filtro_pref === '0' ? 'selected' : ''; ?>>PLATAFORMA CENTRAL</option>
                        <hr>
                        <?php foreach($prefeituras as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo $filtro_pref == $p['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted"><i class="bi bi-calendar3 me-1"></i> Data Específica</label>
                    <input type="date" name="data" class="form-select border-0 shadow-sm rounded-3" value="<?php echo $filtro_data; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted"><i class="bi bi-funnel me-1"></i> Tipo de Ação</label>
                    <select name="acao" class="form-select border-0 shadow-sm rounded-3">
                        <option value="">Todas as Ações</option>
                        <option value="ADIÇÃO" <?php echo $filtro_acao == 'ADIÇÃO' ? 'selected' : ''; ?>>ADIÇÃO</option>
                        <option value="EDIÇÃO" <?php echo $filtro_acao == 'EDIÇÃO' ? 'selected' : ''; ?>>EDIÇÃO</option>
                        <option value="EXCLUSÃO" <?php echo $filtro_acao == 'EXCLUSÃO' ? 'selected' : ''; ?>>EXCLUSÃO</option>
                        <option value="LOGIN" <?php echo $filtro_acao == 'LOGIN' ? 'selected' : ''; ?>>LOGIN</option>
                        <option value="LOGOUT" <?php echo $filtro_acao == 'LOGOUT' ? 'selected' : ''; ?>>LOGOUT</option>
                        <option value="CONFIG" <?php echo $filtro_acao == 'CONFIG' ? 'selected' : ''; ?>>CONFIGURAÇÃO</option>
                    </select>
                </div>
                <div class="col-md-5 d-flex gap-2 justify-content-end">
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill shadow fw-bold">
                        <i class="bi bi-search me-2"></i> Aplicar Filtros
                    </button>
                    <a href="super_logs.php" class="btn btn-white border px-4 py-2 rounded-pill shadow-sm fw-bold">
                        <i class="bi bi-x-lg me-2"></i> Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 overflow-hidden rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-dark text-white border-0">
                    <tr>
                        <th class="ps-4 text-nowrap" style="width: 120px;">Horário</th>
                        <th class="text-nowrap" style="width: 250px;">Prefeitura</th>
                        <th class="text-nowrap" style="width: 200px;">Usuário</th>
                        <th class="text-nowrap" style="width: 130px;">Ação</th>
                        <th class="text-nowrap" style="width: 200px;">Módulo/Tabela</th>
                        <th>O que foi feito / Detalhes</th>
                        <th class="text-nowrap" style="width: 150px;">IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="opacity-25 mb-3"><i class="bi bi-search" style="font-size: 3rem;"></i></div>
                            <p class="mt-2 text-muted fw-bold">Nenhuma atividade encontrada para os filtros selecionados.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($logs as $log): ?>
                    <tr class="transition-all hover-light">
                        <td class="ps-4 py-3 text-nowrap">
                            <div class="text-dark fw-bold" style="font-size: 13px;"><?php echo date('H:i:s', strtotime($log['horario'])); ?></div>
                            <div class="text-muted" style="font-size: 11px;"><?php echo date('d/m/Y', strtotime($log['horario'])); ?></div>
                        </td>
                        <td>
                            <?php if (!$log['id_prefeitura'] || $log['id_prefeitura'] == 0): ?>
                                <span class="badge bg-dark rounded-pill border border-dark px-3 py-2 small" style="letter-spacing: 0.5px;">
                                    <i class="bi bi-grid-fill me-1 small"></i> PLATAFORMA
                                </span>
                            <?php else: ?>
                                <div class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 small">
                                    <i class="bi bi-building me-1 small"></i> <?php echo htmlspecialchars($log['prefeitura_nome'] ?: 'Desconhecida'); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle-sm me-2 bg-light text-secondary small border shadow-sm">
                                    <?php echo strtoupper(substr($log['usuario_nome'], 0, 1)); ?>
                                </div>
                                <span class="fw-bold text-secondary" style="font-size: 13px;"><?php echo htmlspecialchars($log['usuario_nome']); ?></span>
                            </div>
                        </td>
                        <td>
                            <?php 
                            $badge_class = 'bg-secondary';
                            if ($log['acao'] == 'ADIÇÃO') $badge_class = 'bg-success';
                            if ($log['acao'] == 'EDIÇÃO') $badge_class = 'bg-warning text-dark';
                            if ($log['acao'] == 'EXCLUSÃO') $badge_class = 'bg-danger';
                            if ($log['acao'] == 'LOGIN') $badge_class = 'bg-info text-dark';
                            if ($log['acao'] == 'LOGOUT') $badge_class = 'bg-dark';
                            if ($log['acao'] == 'CONFIG') $badge_class = 'bg-primary';
                            ?>
                            <span class="badge <?php echo $badge_class; ?> rounded-pill shadow-xs" style="font-size: 0.65rem; padding: 0.4em 0.8em;"><?php echo $log['acao']; ?></span>
                        </td>
                        <td>
                            <span class="text-uppercase fw-600 small px-2 py-1 bg-light rounded-3 text-dark border" style="font-size: 11px; letter-spacing: 0.3px;">
                                <?php echo htmlspecialchars($log['tabela'] ?: 'SISTEMA'); ?>
                            </span>
                        </td>
                        <td class="text-wrap" style="min-width: 300px;">
                            <p class="mb-0 text-dark small" style="line-height: 1.5;"><?php echo nl2br(htmlspecialchars($log['detalhes'])); ?></p>
                        </td>
                        <td class="small font-monospace text-muted py-2 text-nowrap" style="font-size: 11px;">
                            <i class="bi bi-pc-display me-1 opacity-75"></i> <?php echo $log['ip_endereco']; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.btn-white { background: #fff; color: #333; }
.avatar-circle-sm {
    width: 25px;
    height: 25px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.7rem;
}
.table thead th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding: 1.2rem 0.75rem;
    font-weight: 700;
}
.table tbody td {
    padding: 1.2rem 0.75rem;
}
.bg-primary-subtle { background-color: rgba(13, 110, 253, 0.08) !important; color: #0d6efd !important; }
.fw-600 { font-weight: 600; }
.transition-all { transition: all 0.2s ease; }
.hover-light:hover { background-color: #fcfcfc !important; }
.shadow-xs { box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
</style>

<?php include 'admin_footer.php'; ?>
