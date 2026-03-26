<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Variáveis de sessão do usuário
$perfil_usuario = $_SESSION['admin_user_perfil'];

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

$sql_base = "FROM registros r 
             LEFT JOIN portais p ON r.id_portal = p.id
             LEFT JOIN categorias cat ON p.id_categoria = cat.id
             LEFT JOIN tipos_documento td ON r.id_tipo_documento = td.id";
$sql_where = " WHERE 1=1 ";
$params = [];

// Monta a cláusula WHERE dinamicamente
if (!empty($filtros['data_inicio'])) { $sql_where .= " AND r.data_criacao >= ? "; $params[] = $filtros['data_inicio']; }
if (!empty($filtros['data_fim'])) { $sql_where .= " AND r.data_criacao <= ? "; $params[] = $filtros['data_fim']; }
if (!empty($filtros['categoria_id'])) { $sql_where .= " AND p.id_categoria = ? "; $params[] = $filtros['categoria_id']; }
if (!empty($filtros['secao_id'])) { $sql_where .= " AND r.id_portal = ? "; $params[] = $filtros['secao_id']; }
if (!empty($filtros['tipo_documento_id'])) { $sql_where .= " AND r.id_tipo_documento = ? "; $params[] = $filtros['tipo_documento_id']; }
if (!empty($filtros['palavra_chave'])) {
    $sql_where .= " AND (p.nome LIKE ? OR td.nome LIKE ? OR EXISTS (SELECT 1 FROM valores_registros vr WHERE vr.id_registro = r.id AND vr.valor LIKE ?))";
    $params[] = '%' . $filtros['palavra_chave'] . '%';
    $params[] = '%' . $filtros['palavra_chave'] . '%';
    $params[] = '%' . $filtros['palavra_chave'] . '%';
}

// --- CONTAGEM TOTAL DE ITENS ---
$stmt_total = $pdo->prepare("SELECT COUNT(r.id) " . $sql_base . $sql_where);
$stmt_total->execute($params);
$total_itens = $stmt_total->fetchColumn();
$total_paginas = ceil($total_itens / $itens_por_pagina);

// --- BUSCA DOS ITENS DA PÁGINA ATUAL ---
$sql_select = "SELECT r.id, r.data_criacao, p.id as portal_id, p.nome as nome_secao, cat.nome as nome_categoria, td.nome as nome_documento ";
$sql_order = " ORDER BY r.data_criacao DESC ";
$sql_limit = " LIMIT " . (int)$itens_por_pagina . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($sql_select . $sql_base . $sql_where . $sql_order . $sql_limit);
$stmt->execute($params);
$publicacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca dados para os dropdowns do formulário
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC")->fetchAll();
$secoes = $pdo->query("SELECT id, nome FROM portais ORDER BY nome ASC")->fetchAll();
$tipos_documento = $pdo->query("SELECT id, nome FROM tipos_documento ORDER BY nome ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Publicações - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">

<?php
$page_title_for_header = 'Relatório de Publicações';
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-search"></i> Pesquisa Avançada</div>
                <div class="card-body">
                    <form method="GET" action="relatorio_publicacoes.php">
                        <div class="row">
                            <div class="col-md-3 mb-3"><label for="data_inicio" class="form-label">Data de (Início)</label><input type="date" class="form-control" name="data_inicio" id="data_inicio" value="<?php echo htmlspecialchars($filtros['data_inicio']); ?>"></div>
                            <div class="col-md-3 mb-3"><label for="data_fim" class="form-label">Data de (Fim)</label><input type="date" class="form-control" name="data_fim" id="data_fim" value="<?php echo htmlspecialchars($filtros['data_fim']); ?>"></div>
                            <div class="col-md-3 mb-3"><label for="categoria_id" class="form-label">Categoria</label><select class="form-select" name="categoria_id"><option value="">Todas</option><?php foreach($categorias as $cat): ?><option value="<?php echo $cat['id']; ?>" <?php if($cat['id'] == $filtros['categoria_id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['nome']); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-3 mb-3"><label for="secao_id" class="form-label">Seção</label><select class="form-select" name="secao_id"><option value="">Todas</option><?php foreach($secoes as $sec): ?><option value="<?php echo $sec['id']; ?>" <?php if($sec['id'] == $filtros['secao_id']) echo 'selected'; ?>><?php echo htmlspecialchars($sec['nome']); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-6 mb-3"><label for="tipo_documento_id" class="form-label">Tipo de Documento</label><select class="form-select" name="tipo_documento_id"><option value="">Todos</option><?php foreach($tipos_documento as $tipo): ?><option value="<?php echo $tipo['id']; ?>" <?php if($tipo['id'] == $filtros['tipo_documento_id']) echo 'selected'; ?>><?php echo htmlspecialchars($tipo['nome']); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-6 mb-3"><label for="palavra_chave" class="form-label">Palavra-chave</label><input type="text" class="form-control" name="palavra_chave" id="palavra_chave" value="<?php echo htmlspecialchars($filtros['palavra_chave']); ?>"></div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Pesquisar</button>
                        <a href="relatorio_publicacoes.php" class="btn btn-secondary">Limpar Filtros</a>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><?php echo $total_itens; ?> publicações encontradas</div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr><th>Publicado em</th><th>Categoria</th><th>Seção</th><th>Tipo do Documento</th><th class="text-end">Ações</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($publicacoes)): ?>
                                <tr><td colspan="5" class="text-center">Nenhuma publicação encontrada para os critérios informados.</td></tr>
                            <?php else: ?>
                                <?php foreach($publicacoes as $pub): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($pub['data_criacao'])); ?></td>
                                    <td><?php echo htmlspecialchars($pub['nome_categoria'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($pub['nome_secao'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($pub['nome_documento'] ?? 'N/A'); ?></td>
                                    <td class="text-end"><a href="ver_lancamentos.php?portal_id=<?php echo htmlspecialchars($pub['portal_id'] ?? 0); ?>" class="btn btn-sm btn-info">Ver Lançamentos</a></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_paginas > 1): ?>
                <div class="card-footer">
                    <nav><ul class="pagination justify-content-center mb-0">
                        <?php $query_params = $_GET; ?>
                        <li class="page-item <?php if($pagina_atual <= 1) echo 'disabled'; ?>"><?php $query_params['page'] = $pagina_atual - 1; ?><a class="page-link" href="?<?php echo http_build_query($query_params); ?>">Anterior</a></li>
                        <?php for($i=1; $i<=$total_paginas; $i++): $query_params['page'] = $i; ?>
                        <li class="page-item <?php if($pagina_atual == $i) echo 'active'; ?>"><a class="page-link" href="?<?php echo http_build_query($query_params); ?>"><?php echo $i; ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?php if($pagina_atual >= $total_paginas) echo 'disabled'; ?>"><?php $query_params['page'] = $pagina_atual + 1; ?><a class="page-link" href="?<?php echo http_build_query($query_params); ?>">Próximo</a></li>
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