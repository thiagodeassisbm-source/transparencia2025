<?php
require_once 'conexao.php';

// --- CONFIGURAÇÕES DA PAGINAÇÃO ---
$itens_por_pagina = 10;
$pagina_atual = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
if ($pagina_atual < 1) { $pagina_atual = 1; }
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$slug_portal = filter_input(INPUT_GET, 'slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if (!$slug_portal) { die("Seção não especificada."); }

// --- Busca de dados básicos da seção ---
$stmt_portal = $pdo->prepare("SELECT id, nome, id_categoria FROM portais WHERE slug = ?");
$stmt_portal->execute([$slug_portal]);
$secao = $stmt_portal->fetch();
if (!$secao) { die("Seção não encontrada."); }
$id_portal = $secao['id'];
$page_title = $secao['nome'];
$_GET['id'] = $secao['id_categoria'];

// --- LÓGICA DOS FILTROS ---
$stmt_pesquisaveis = $pdo->prepare("SELECT id, nome_campo, tipo_campo, opcoes_campo FROM campos_portal WHERE id_portal = ? AND pesquisavel = 1 ORDER BY ordem");
$stmt_pesquisaveis->execute([$id_portal]);
$campos_pesquisaveis = $stmt_pesquisaveis->fetchAll();
$filtros_ativos = $_GET['filtros'] ?? [];
$params_where = [$id_portal];
$sql_where_extra = "";
foreach ($campos_pesquisaveis as $campo) {
    $id_campo_filtro = $campo['id'];
    if (isset($filtros_ativos[$id_campo_filtro]) && $filtros_ativos[$id_campo_filtro] !== '') {
        $valor_filtro = $filtros_ativos[$id_campo_filtro];
        $sql_where_extra .= " AND EXISTS (SELECT 1 FROM valores_registros vr WHERE vr.id_registro = r.id AND vr.id_campo = ? AND vr.valor LIKE ?)";
        $params_where[] = $id_campo_filtro;
        $params_where[] = '%' . $valor_filtro . '%';
    }
}

// --- CONTAGEM TOTAL DE ITENS PARA A PAGINAÇÃO ---
$sql_count = "SELECT COUNT(r.id) FROM registros r WHERE id_portal = ? $sql_where_extra";
$stmt_total = $pdo->prepare($sql_count);
$stmt_total->execute($params_where);
$total_itens = $stmt_total->fetchColumn();
$total_paginas = $total_itens > 0 ? ceil($total_itens / $itens_por_pagina) : 0;

// --- BUSCA DOS IDs DOS REGISTROS DA PÁGINA ATUAL ---
$sql_registros = "SELECT id FROM registros r WHERE id_portal = ? $sql_where_extra ORDER BY id DESC LIMIT ? OFFSET ?";
$params_paginados = array_merge($params_where, [$itens_por_pagina, $offset]);
$stmt_registros = $pdo->prepare($sql_registros);
$stmt_registros->execute($params_paginados);
$registros_ids = $stmt_registros->fetchAll(PDO::FETCH_COLUMN);

// --- Lógica para montar a tabela de resultados ---
$stmt_campos_todos = $pdo->prepare("SELECT id, nome_campo, tipo_campo FROM campos_portal WHERE id_portal = ? AND detalhes_apenas = 0 ORDER BY ordem, id");
$stmt_campos_todos->execute([$id_portal]);
$campos_tabela = $stmt_campos_todos->fetchAll();
$id_campos_ordenados = array_column($campos_tabela, 'id');
$dados_tabela = [];
if (!empty($registros_ids)) {
    $placeholders = implode(',', array_fill(0, count($registros_ids), '?'));
    $stmt_valores = $pdo->prepare("SELECT id_registro, id_campo, valor FROM valores_registros WHERE id_registro IN ($placeholders)");
    $stmt_valores->execute($registros_ids);
    $valores_por_registro = [];
    while($row = $stmt_valores->fetch()) { $valores_por_registro[$row['id_registro']][$row['id_campo']] = $row['valor']; }
    foreach ($registros_ids as $id_registro) {
        $linha_ordenada = ['id_registro_para_link' => $id_registro];
        $valores_do_registro_atual = $valores_por_registro[$id_registro] ?? [];
        foreach ($id_campos_ordenados as $id_campo) { 
            $linha_ordenada[] = $valores_do_registro_atual[$id_campo] ?? ''; 
        }
        $dados_tabela[] = $linha_ordenada;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <base href="<?php echo $base_url; ?>">
    <title><?php echo htmlspecialchars($secao['nome']); ?> - Portal da Transparência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        .table-custom tbody tr:hover { background-color: #f8f9fa; cursor: pointer; }
        .table-custom thead th { border-bottom: 2px solid #dee2e6; }
    </style>
</head>
<body class="bg-light">

<?php 
$page_title = $secao['nome']; 
include 'header_publico.php'; 
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'menu.php'; ?>
        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            
            <h2 class="mb-4 fw-bold"><?php echo htmlspecialchars($secao['nome']); ?></h2>

            <?php if (!empty($campos_pesquisaveis)): ?>
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-white py-3"><h4><i class="bi bi-funnel-fill text-primary me-2"></i>Filtros de Pesquisa</h4></div>
                <div class="card-body">
                    <form method="GET" action="portal.php">
                        <input type="hidden" name="slug" value="<?php echo htmlspecialchars($slug_portal); ?>">
                        <div class="row">
                            <?php foreach ($campos_pesquisaveis as $campo): ?>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold"><?php echo htmlspecialchars($campo['nome_campo']); ?></label>
                                    <?php
                                        $valor_filtro_atual = $filtros_ativos[$campo['id']] ?? '';
                                        $tipo_input = 'text';
                                        if ($campo['tipo_campo'] == 'data') $tipo_input = 'date';
                                        if ($campo['tipo_campo'] == 'numero' || $campo['tipo_campo'] == 'moeda') $tipo_input = 'number';
                                        echo '<input type="'. $tipo_input .'" class="form-control" name="filtros['. $campo['id'] .']" value="'. htmlspecialchars($valor_filtro_atual) .'">';
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Filtrar</button>
                            <a href="portal.php?slug=<?php echo htmlspecialchars($slug_portal); ?>" class="btn btn-outline-secondary"><i class="bi bi-eraser-fill"></i> Limpar</a>
                            
                            <div class="dropdown ms-auto">
                                <button class="btn btn-dynamic-primary dropdown-toggle" type="button" id="dropdownExport" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-download me-1"></i> Exportar Dados</button>
                                <?php $export_params = http_build_query($_GET); ?>
                                <ul class="dropdown-menu shadow border-0" aria-labelledby="dropdownExport">
                                    <li><a class="dropdown-item" href="exportar_dados.php?<?php echo $export_params; ?>&formato=csv"><i class="bi bi-filetype-csv me-2"></i>CSV</a></li>
                                    <li><a class="dropdown-item" href="exportar_dados.php?<?php echo $export_params; ?>&formato=xls"><i class="bi bi-file-earmark-excel me-2"></i>Excel</a></li>
                                    <li><a class="dropdown-item" href="exportar_dados.php?<?php echo $export_params; ?>&formato=json"><i class="bi bi-filetype-json me-2"></i>JSON</a></li>
                                    <li><a class="dropdown-item" href="exportar_dados.php?<?php echo $export_params; ?>&formato=xml"><i class="bi bi-filetype-xml me-2"></i>XML</a></li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Registros Encontrados: <span class="badge bg-light text-dark border"><?php echo $total_itens; ?></span></h5>
                </div>
                <div class="table-responsive">
                    <table class="table-custom table-hover align-middle mb-0 w-100">
                        <thead>
                            <tr>
                                <?php foreach ($campos_tabela as $campo): ?>
                                    <th><?php echo htmlspecialchars($campo['nome_campo']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dados_tabela)): ?>
                                <tr><td colspan="<?php echo count($campos_tabela); ?>" class="text-center p-5 text-muted">Ainda não há dados cadastrados nesta seção.</td></tr>
                            <?php else: ?>
                                <?php foreach ($dados_tabela as $linha): ?>
                                    <tr onclick="window.location='detalhes.php?id=<?php echo $linha['id_registro_para_link']; ?>';">
                                        <?php 
                                        $id_registro_atual = $linha['id_registro_para_link'];
                                        unset($linha['id_registro_para_link']);
                                        ?>
                                        <?php foreach ($linha as $coluna_index => $valor): ?>
                                            <td>
                                                <?php
                                                $tipo_do_campo_atual = $campos_tabela[$coluna_index]['tipo_campo'];
                                                if ($tipo_do_campo_atual == 'anexo') {
                                                    if (!empty($valor)) echo '<i class="bi bi-paperclip text-primary"></i> Sim'; else echo '-';
                                                } elseif ($tipo_do_campo_atual == 'data' && !empty($valor)) {
                                                    $data_objeto = date_create($valor);
                                                    echo ($data_objeto) ? date_format($data_objeto, 'd/m/Y') : htmlspecialchars($valor);
                                                } elseif ($tipo_do_campo_atual == 'moeda' && !empty($valor)) {
                                                    echo 'R$ ' . number_format($valor, 2, ',', '.');
                                                } else {
                                                    echo htmlspecialchars(mb_strtoupper($valor));
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if ($total_paginas > 1): ?>
            <nav aria-label="Navegação das páginas" class="mt-4 d-flex justify-content-center">
                <ul class="pagination">
                    <?php $query_params_page = $_GET; ?>
                    <li class="page-item <?php if($pagina_atual <= 1){ echo 'disabled'; } ?>">
                        <?php $query_params_page['page'] = $pagina_atual - 1; ?>
                        <a class="page-link" href="?<?php echo http_build_query($query_params_page); ?>">Anterior</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <?php $query_params_page['page'] = $i; ?>
                        <li class="page-item <?php if($pagina_atual == $i) { echo 'active-dynamic'; } ?>">
                            <a class="page-link" href="?<?php echo http_build_query($query_params_page); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php if($pagina_atual >= $total_paginas) { echo 'disabled'; } ?>">
                        <?php $query_params_page['page'] = $pagina_atual + 1; ?>
                        <a class="page-link" href="?<?php echo http_build_query($query_params_page); ?>">Próximo</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php 
$custom_container_class = "container-custom-padding";
include 'footer_publico.php'; 
?>
</body>
</html>