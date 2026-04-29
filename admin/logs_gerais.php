<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Filtros básicos
$filtro_acao = $_GET['acao'] ?? '';
$filtro_usuario = $_GET['usuario'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

$sql = "SELECT * FROM logs_sistema WHERE 1=1";
$params = [];

if ($filtro_acao) {
    $sql .= " AND acao = ?";
    $params[] = $filtro_acao;
}
if ($filtro_usuario) {
    $sql .= " AND usuario_id = ?";
    $params[] = $filtro_usuario;
}
if ($data_inicio) {
    $sql .= " AND DATE(horario) >= ?";
    $params[] = $data_inicio;
}
if ($data_fim) {
    $sql .= " AND DATE(horario) <= ?";
    $params[] = $data_fim;
}

// Filtro SaaS: Apenas logs da própria prefeitura e esconde logs de gestão global
if (!$_SESSION['is_superadmin']) {
    $sql .= " AND id_prefeitura = ? AND tabela != 'SUPERADMIN'";
    $params[] = $_SESSION['id_prefeitura'];
}

$sql .= " ORDER BY horario DESC LIMIT 500"; // Limite para performance
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Agrupar logs por Módulo/Tabela
$logs_por_modulo = [];
foreach ($logs as $log) {
    $modulo = $log['tabela'] ?: 'SISTEMA';
    if (!isset($logs_por_modulo[$modulo])) {
        $logs_por_modulo[$modulo] = [
            'modulo' => $modulo,
            'total' => 0,
            'ultimo_horario' => $log['horario'],
            'logs' => []
        ];
    }
    $logs_por_modulo[$modulo]['logs'][] = $log;
    $logs_por_modulo[$modulo]['total']++;
}

// Pegar lista de usuários para filtro (Aplicando Filtro SaaS)
$sql_users = "SELECT DISTINCT usuario_id, usuario_nome FROM logs_sistema WHERE usuario_id > 0";
$params_users = [];
if (!$_SESSION['is_superadmin']) {
    $sql_users .= " AND id_prefeitura = ?";
    $params_users[] = $_SESSION['id_prefeitura'];
}
$usuarios_stmt = $pdo->prepare($sql_users);
$usuarios_stmt->execute($params_users);
$usuarios = $usuarios_stmt->fetchAll();

$page_title_for_header = 'Logs Gerais';
include 'admin_header.php';
?>

<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h4 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i> Logs de Atividade</h4>
            <p class="text-muted small mb-0">Rastro completo de todas as ações realizadas no painel administrativo.</p>
        </div>
        <div class="col-auto">
             <button onclick="window.location.reload()" class="btn btn-outline-secondary btn-sm rounded-pill"><i class="bi bi-arrow-clockwise"></i> Atualizar</button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <form class="row g-2 align-items-end" method="GET">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Filtrar por Usuário</label>
                    <select name="usuario" class="form-select form-select-sm border-0 shadow-sm">
                        <option value="">Todos os Usuários</option>
                        <?php foreach($usuarios as $u): ?>
                            <option value="<?php echo $u['usuario_id']; ?>" <?php echo $filtro_usuario == $u['usuario_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['usuario_nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Filtrar por Ação</label>
                    <select name="acao" class="form-select form-select-sm border-0 shadow-sm">
                        <option value="">Todas as Ações</option>
                        <option value="ADIÇÃO" <?php echo $filtro_acao == 'ADIÇÃO' ? 'selected' : ''; ?>>ADIÇÃO</option>
                        <option value="EDIÇÃO" <?php echo $filtro_acao == 'EDIÇÃO' ? 'selected' : ''; ?>>EDIÇÃO</option>
                        <option value="EXCLUSÃO" <?php echo $filtro_acao == 'EXCLUSÃO' ? 'selected' : ''; ?>>EXCLUSÃO</option>
                        <option value="LOGIN" <?php echo $filtro_acao == 'LOGIN' ? 'selected' : ''; ?>>LOGIN</option>
                        <option value="LOGOUT" <?php echo $filtro_acao == 'LOGOUT' ? 'selected' : ''; ?>>LOGOUT</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Data Início</label>
                    <input type="date" name="data_inicio" value="<?php echo $data_inicio; ?>" class="form-control form-control-sm border-0 shadow-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Data Fim</label>
                    <input type="date" name="data_fim" value="<?php echo $data_fim; ?>" class="form-control form-control-sm border-0 shadow-sm">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill rounded-pill shadow-sm"><i class="bi bi-funnel"></i> Aplicar</button>
                    <a href="logs_gerais.php" class="btn btn-outline-secondary btn-sm flex-fill rounded-pill"><i class="bi bi-x-circle"></i> Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($logs_por_modulo)): ?>
        <div class="card shadow-sm border-0 py-5">
            <div class="text-center">
                <i class="bi bi-inbox-fill display-4 text-light"></i>
                <p class="mt-2 text-muted">Nenhum rastro encontrado.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4" id="cardsLogs">
            <?php foreach ($logs_por_modulo as $modulo_nome => $info): 
                $modulo_id = md5($modulo_nome);
            ?>
                <div class="col-md-4 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm card-modulo ripple" 
                         data-bs-toggle="collapse" 
                         data-bs-target="#collapse_<?php echo $modulo_id; ?>" 
                         aria-expanded="false" 
                         style="cursor: pointer; transition: all 0.3s ease;">
                        <div class="card-body p-4 text-center">
                            <div class="mb-3">
                                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
                                    <i class="bi bi-layers-fill me-1"></i> <?php echo $info['total']; ?> registros
                                </span>
                            </div>
                            <h5 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($modulo_nome); ?></h5>
                            <p class="text-muted small mb-0">
                                <i class="bi bi-clock me-1"></i> Última atividade:<br>
                                <span class="fw-bold"><?php echo date('d/m/Y H:i:s', strtotime($info['ultimo_horario'])); ?></span>
                            </p>
                        </div>
                        <div class="card-footer bg-white border-0 text-center pb-3">
                            <span class="text-primary small fw-bold">Ver Histórico <i class="bi bi-chevron-down ms-1"></i></span>
                        </div>
                    </div>
                </div>

                <!-- Detalhes do Módulo (Collapse) -->
                <div class="col-12 collapse mt-3" id="collapse_<?php echo $modulo_id; ?>" data-bs-parent="#cardsLogs">
                    <div class="card border-0 shadow-lg overflow-hidden">
                        <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-table me-2"></i>Histórico Detalhado: <?php echo htmlspecialchars($modulo_nome); ?></h6>
                            <button class="btn-close btn-close-white btn-sm" data-bs-toggle="collapse" data-bs-target="#collapse_<?php echo $modulo_id; ?>"></button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted">
                                    <tr>
                                        <th class="ps-4" style="width: 150px;">Horário</th>
                                        <th style="width: 250px;">Usuário</th>
                                        <th style="width: 120px;">Ação</th>
                                        <th>O que foi feito / Detalhes</th>
                                        <th class="text-nowrap" style="width: 150px;">IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($info['logs'] as $log): ?>
                                    <tr>
                                        <td class="ps-4 text-nowrap">
                                            <div class="text-dark fw-bold"><?php echo date('H:i:s', strtotime($log['horario'])); ?></div>
                                            <div class="text-muted small"><?php echo date('d/m/Y', strtotime($log['horario'])); ?></div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle-sm me-2 bg-light text-primary small">
                                                    <?php echo strtoupper(substr($log['usuario_nome'] ?? 'A', 0, 1)); ?>
                                                </div>
                                                <span class="fw-bold"><?php echo htmlspecialchars($log['usuario_nome']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $badge_class = 'bg-secondary';
                                            if ($log['acao'] == 'ADIÇÃO') $badge_class = 'bg-success';
                                            if ($log['acao'] == 'EDIÇÃO') $badge_class = 'bg-warning text-dark';
                                            if ($log['acao'] == 'EXCLUSÃO') $badge_class = 'bg-danger';
                                            if ($log['acao'] == 'LOGIN') $badge_class = 'bg-info text-dark';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?> rounded-pill small" style="font-size: 0.7rem;"><?php echo $log['acao']; ?></span>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo nl2br(htmlspecialchars($log['detalhes'])); ?>
                                        </td>
                                        <td class="small font-monospace text-nowrap">
                                            <?php echo $log['ip_endereco']; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.card-modulo:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    background-color: #f8f9fa;
}
.bg-primary-subtle {
    background-color: rgba(13, 110, 253, 0.1);
}
.avatar-circle-sm {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.7rem;
    border: 1px solid rgba(0,0,0,0.1);
}
.table thead th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1rem 0.75rem;
    font-weight: 700;
}
.table tbody td {
    padding: 1rem 0.75rem;
}
.fw-600 { font-weight: 600; }

.collapse.show {
    margin-bottom: 2rem;
}
</style>

<?php include 'admin_footer.php'; ?>
