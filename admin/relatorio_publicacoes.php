<?php
require_once 'auth_check.php';
require_once '../conexao.php';

$is_superadmin = isset($_SESSION['is_superadmin']) && (int) $_SESSION['is_superadmin'] === 1;
$session_pref = (int) ($_SESSION['id_prefeitura'] ?? 0);
$req_pref = filter_input(INPUT_GET, 'pref_id', FILTER_VALIDATE_INT);

// Cada prefeitura vê apenas o próprio relatório; super admin escolhe a prefeitura (GET ou contexto da sessão após switch_pref).
$pref_id = 0;
if ($is_superadmin) {
    if ($req_pref !== false && $req_pref !== null && $req_pref > 0) {
        $stmt_chk = $pdo->prepare('SELECT id FROM prefeituras WHERE id = ?');
        $stmt_chk->execute([$req_pref]);
        if ($stmt_chk->fetch()) {
            $pref_id = $req_pref;
        }
    }
    if ($pref_id <= 0 && $session_pref > 0) {
        $stmt_chk2 = $pdo->prepare('SELECT id FROM prefeituras WHERE id = ?');
        $stmt_chk2->execute([$session_pref]);
        if ($stmt_chk2->fetch()) {
            $pref_id = $session_pref;
        }
    }
} else {
    $pref_id = $session_pref;
    if ($pref_id <= 0) {
        $_SESSION['mensagem_erro'] = 'Contexto de prefeitura não identificado. Acesse pelo link do seu município.';
        header('Location: index.php');
        exit;
    }
}

$stmt_lista_prefs = $pdo->query('SELECT id, nome FROM prefeituras ORDER BY nome ASC');
$lista_prefeituras = $stmt_lista_prefs ? $stmt_lista_prefs->fetchAll(PDO::FETCH_ASSOC) : [];

$nome_prefeitura_contexto = '';
if ($pref_id > 0) {
    $stmt_np = $pdo->prepare('SELECT nome FROM prefeituras WHERE id = ?');
    $stmt_np->execute([$pref_id]);
    $nome_prefeitura_contexto = (string) ($stmt_np->fetchColumn() ?: '');
}

$relatorio_sem_pref = $is_superadmin && $pref_id <= 0;

// --- CONFIGURAÇÕES DA PAGINAÇÃO ---
$itens_por_pagina = 15;
$pagina_atual = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
if ($pagina_atual < 1) { $pagina_atual = 1; }
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// --- LÓGICA DOS FILTROS ---
$filtros = [
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'categoria_id' => filter_input(INPUT_GET, 'categoria_id', FILTER_VALIDATE_INT),
    'secao_id' => filter_input(INPUT_GET, 'secao_id', FILTER_VALIDATE_INT),
    'tipo_documento_id' => filter_input(INPUT_GET, 'tipo_documento_id', FILTER_VALIDATE_INT),
    'palavra_chave' => $_GET['palavra_chave'] ?? ''
];

$publicacoes = [];
$total_itens = 0;
$total_paginas = 0;

if (!$relatorio_sem_pref) {
    $sql_base = "FROM registros r 
                 INNER JOIN portais p ON r.id_portal = p.id
                 LEFT JOIN categorias cat ON p.id_categoria = cat.id
                 LEFT JOIN tipos_documento td ON r.id_tipo_documento = td.id";
    $sql_where = ' WHERE p.id_prefeitura = ? ';
    $params = [$pref_id];

    // Monta a cláusula WHERE dinamicamente
    if (!empty($filtros['data_inicio'])) { $sql_where .= " AND r.data_criacao >= ? "; $params[] = $filtros['data_inicio'] . ' 00:00:00'; }
    if (!empty($filtros['data_fim'])) { $sql_where .= " AND r.data_criacao <= ? "; $params[] = $filtros['data_fim'] . ' 23:59:59'; }
    if (!empty($filtros['categoria_id'])) { $sql_where .= " AND p.id_categoria = ? "; $params[] = $filtros['categoria_id']; }
    if (!empty($filtros['secao_id'])) { $sql_where .= " AND r.id_portal = ? "; $params[] = $filtros['secao_id']; }
    if (!empty($filtros['tipo_documento_id'])) { $sql_where .= " AND r.id_tipo_documento = ? "; $params[] = $filtros['tipo_documento_id']; }
    if (!empty($filtros['palavra_chave'])) {
        $sql_where .= " AND (p.nome LIKE ? OR td.nome LIKE ? OR cat.nome LIKE ?)";
        $termo = '%' . $filtros['palavra_chave'] . '%';
        $params[] = $termo; $params[] = $termo; $params[] = $termo;
    }

    // --- CONTAGEM TOTAL DE ITENS ---
    $stmt_total = $pdo->prepare("SELECT COUNT(r.id) " . $sql_base . $sql_where);
    $stmt_total->execute($params);
    $total_itens = (int) $stmt_total->fetchColumn();
    $total_paginas = (int) ceil($total_itens / $itens_por_pagina);

    // --- BUSCA DOS ITENS DA PÁGINA ATUAL ---
    $sql_select = "SELECT r.id, r.data_criacao, p.id as portal_id, p.nome as nome_secao, cat.nome as nome_categoria, td.nome as nome_documento ";
    $sql_order = " ORDER BY r.data_criacao DESC ";
    $sql_limit = " LIMIT " . (int)$itens_por_pagina . " OFFSET " . (int)$offset;

    $stmt = $pdo->prepare($sql_select . $sql_base . $sql_where . $sql_order . $sql_limit);
    $stmt->execute($params);
    $publicacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Busca dados para os dropdowns (Filtrado por prefeitura)
$categorias = [];
$secoes = [];
$tipos_documento = [];
if ($pref_id > 0) {
    $stmt_cats = $pdo->prepare("SELECT id, nome FROM categorias WHERE id_prefeitura = ? ORDER BY nome ASC");
    $stmt_cats->execute([$pref_id]);
    $categorias = $stmt_cats->fetchAll();

    $stmt_secs = $pdo->prepare("SELECT id, nome FROM portais WHERE id_prefeitura = ? ORDER BY nome ASC");
    $stmt_secs->execute([$pref_id]);
    $secoes = $stmt_secs->fetchAll();

    $stmt_tipos = $pdo->prepare("SELECT id, nome FROM tipos_documento WHERE id_prefeitura = ? OR id_prefeitura IS NULL ORDER BY nome ASC");
    $stmt_tipos->execute([$pref_id]);
    $tipos_documento = $stmt_tipos->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Publicações - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .filter-card { border-radius: 15px; border: none; background: #fff; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
        .table-rounded { border-radius: 12px; overflow: hidden; border: 1px solid #f1f5f9; }
        /* Contraste explícito: o .badge global do Bootstrap pode forçar texto branco */
        .relat-cat-pill {
            display: inline-block;
            padding: 0.4rem 0.85rem;
            border-radius: 50rem;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.2;
            color: #1e293b !important;
            background-color: #e2e8f0 !important;
            border: 1px solid #cbd5e1;
        }
    </style>
</head>
<body class="bg-light-subtle">

<?php
$page_title_for_header = 'Relatório de Publicações';
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-12">
            
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <h3 class="fw-bold text-dark mb-1">Relatório de Publicações</h3>
                    <p class="text-muted small mb-0"><i class="bi bi-clock-history me-1"></i> Acompanhe cronologicamente tudo o que foi publicado no portal<?php echo $nome_prefeitura_contexto !== '' ? ' da prefeitura selecionada' : ''; ?>.</p>
                    <?php if ($pref_id > 0 && $nome_prefeitura_contexto !== ''): ?>
                        <p class="mb-0 mt-2"><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill"><i class="bi bi-building me-1"></i><?php echo htmlspecialchars($nome_prefeitura_contexto); ?></span></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($relatorio_sem_pref): ?>
            <div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-start gap-3 mb-4">
                <i class="bi bi-exclamation-triangle-fill fs-4 mt-1"></i>
                <div>
                    <strong>Selecione uma prefeitura</strong>
                    <p class="mb-0 small">Cada município possui relatório próprio. Use o filtro <strong>Prefeitura</strong> abaixo para carregar as publicações da cidade desejada (ou entre pelo atalho &quot;Gerenciar&quot; e use &quot;Entrar&quot; na prefeitura para definir o contexto).</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Card Informativo -->
            <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff; border-radius: 15px;">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="me-4 d-none d-md-block">
                        <div class="bg-white bg-opacity-20 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                            <i class="bi bi-bar-chart-fill fs-2"></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Auditoria e Controle</h5>
                        <p class="mb-0 opacity-90 small">
                            Os dados são sempre filtrados pela prefeitura do contexto — um município não visualiza publicações de outro. Utilize os filtros para período ou categoria.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card filter-card mb-4">
                <div class="card-body p-4">
                    <form method="GET" action="relatorio_publicacoes.php" class="row g-3">
                        <?php if ($is_superadmin): ?>
                        <div class="col-md-3 col-lg-2">
                            <label class="form-label fw-bold small">Prefeitura</label>
                            <select class="form-select form-select-sm" name="pref_id" required>
                                <option value="" <?php echo $pref_id <= 0 ? 'selected' : ''; ?>>Selecione…</option>
                                <?php foreach ($lista_prefeituras as $lp): ?>
                                    <option value="<?php echo (int) $lp['id']; ?>" <?php echo ((int) $lp['id'] === $pref_id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($lp['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small">Início</label>
                            <input type="date" class="form-control form-control-sm" name="data_inicio" value="<?php echo htmlspecialchars($filtros['data_inicio']); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small">Fim</label>
                            <input type="date" class="form-control form-control-sm" name="data_fim" value="<?php echo htmlspecialchars($filtros['data_fim']); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small">Categoria</label>
                            <select class="form-select form-select-sm" name="categoria_id">
                                <option value="">Todas</option>
                                <?php foreach($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php if($cat['id'] == $filtros['categoria_id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small">Seção</label>
                            <select class="form-select form-select-sm" name="secao_id">
                                <option value="">Todas</option>
                                <?php foreach($secoes as $sec): ?>
                                    <option value="<?php echo $sec['id']; ?>" <?php if($sec['id'] == $filtros['secao_id']) echo 'selected'; ?>><?php echo htmlspecialchars($sec['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small">Palavra-chave</label>
                            <input type="text" class="form-control form-control-sm" name="palavra_chave" value="<?php echo htmlspecialchars($filtros['palavra_chave']); ?>" placeholder="Buscar...">
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary btn-sm px-3 w-100"><i class="bi bi-search me-1"></i> Filtrar</button>
                            <a href="relatorio_publicacoes.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-check me-2 text-primary"></i><?php echo number_format($total_itens, 0, ',', '.'); ?> Publicações Encontradas</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="p-3 text-muted small text-uppercase">Data/Hora</th>
                                <th class="p-3 text-muted small text-uppercase">Categoria</th>
                                <th class="p-3 text-muted small text-uppercase">Seção / Documento</th>
                                <th class="p-3 text-muted small text-uppercase text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($relatorio_sem_pref): ?>
                                <tr><td colspan="4" class="text-center p-5 text-muted">Escolha uma prefeitura no filtro acima para listar as publicações.</td></tr>
                            <?php elseif(empty($publicacoes)): ?>
                                <tr><td colspan="4" class="text-center p-5 text-muted">Nenhuma publicação encontrada para os critérios informados.</td></tr>
                            <?php else: ?>
                                <?php foreach($publicacoes as $pub): ?>
                                <tr>
                                    <td class="p-3">
                                        <div class="fw-bold text-dark"><i class="bi bi-calendar3 me-2 text-primary small"></i><?php echo date('d/m/Y', strtotime($pub['data_criacao'])); ?></div>
                                        <div class="text-muted small ms-4"><?php echo date('H:i', strtotime($pub['data_criacao'])); ?></div>
                                    </td>
                                    <td class="p-3">
                                        <span class="relat-cat-pill"><?php echo htmlspecialchars($pub['nome_categoria'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td class="p-3">
                                        <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($pub['nome_secao'] ?? 'N/A'); ?></div>
                                        <div class="text-muted small"><i class="bi bi-file-earmark-text me-1"></i><?php echo htmlspecialchars($pub['nome_documento'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td class="p-3 text-end">
                                        <a href="ver_lancamentos.php?portal_id=<?php echo htmlspecialchars($pub['portal_id'] ?? 0); ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                            Visualizar <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_paginas > 1): ?>
                <div class="card-footer bg-white border-top-0 py-4">
                    <nav><ul class="pagination pagination-sm justify-content-center mb-0">
                        <?php $query_params = $_GET; ?>
                        <li class="page-item <?php if($pagina_atual <= 1) echo 'disabled'; ?>"><?php $query_params['page'] = $pagina_atual - 1; ?><a class="page-link shadow-none" href="?<?php echo http_build_query($query_params); ?>"><i class="bi bi-chevron-left"></i></a></li>
                        <?php 
                        $start = max(1, $pagina_atual - 2);
                        $end = min($total_paginas, $pagina_atual + 2);
                        for($i=$start; $i<=$end; $i++): $query_params['page'] = $i; ?>
                        <li class="page-item <?php if($pagina_atual == $i) echo 'active'; ?>"><a class="page-link shadow-none" href="?<?php echo http_build_query($query_params); ?>"><?php echo $i; ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?php if($pagina_atual >= $total_paginas) echo 'disabled'; ?>"><?php $query_params['page'] = $pagina_atual + 1; ?><a class="page-link shadow-none" href="?<?php echo http_build_query($query_params); ?>"><i class="bi bi-chevron-right"></i></a></li>
                    </ul></nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
</body>
</html>