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
// ATUALIZAÇÃO: Busca apenas os campos que NÃO SÃO 'detalhes_apenas' para as colunas da tabela
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
        // Monta a linha apenas com os campos visíveis na tabela
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
    <title><?php echo htmlspecialchars($secao['nome']); ?> - Portal da Transparência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        .table-custom tbody tr:hover { background-color: #f5f5f5; cursor: pointer; }
    </style>
</head>
<body>

<header class="page-header">
    <div class="container-fluid container-custom-padding">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Transparência</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($secao['nome']); ?></li>
                    </ol>
                </nav>
                <h1><?php echo htmlspecialchars($secao['nome']); ?></h1>
            </div>
            <div class="accessibility-bar-header d-flex align-items-center pt-2">
                <span class="me-2 d-none d-lg-inline text-white" style="font-size: 0.8rem;">ACESSIBILIDADE</span>
                <button id="font-increase" class="btn btn-sm btn-outline-light me-1" title="Aumentar Fonte">A+</button>
                <button id="font-reset" class="btn btn-sm btn-outline-light me-1" title="Fonte Padrão">A</button>
                <button id="font-decrease" class="btn btn-sm btn-outline-light me-2" title="Diminuir Fonte">A-</button>
                <button id="contrast-toggle" class="btn btn-sm btn-outline-light" title="Alto Contraste"><i class="bi bi-circle-half"></i></button>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid">
    <div class="row">
        <?php include 'menu.php'; ?>
        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            <?php if (!empty($campos_pesquisaveis)): ?>
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-funnel-fill"></i> Filtros de Pesquisa</div>
                <div class="card-body">
                    <form method="GET" action="portal.php">
                        <input type="hidden" name="slug" value="<?php echo htmlspecialchars($slug_portal); ?>">
                        <div class="row">
                            <?php foreach ($campos_pesquisaveis as $campo): ?>
                                <div class="col-md-4 mb-3">
                                    <label for="filtro_<?php echo $campo['id']; ?>" class="form-label"><?php echo htmlspecialchars($campo['nome_campo']); ?></label>
                                    <?php
                                        $valor_filtro_atual = $filtros_ativos[$campo['id']] ?? '';
                                        // A lógica para criar select, date, etc. foi mantida do seu código original
                                        $tipo_input = 'text';
                                        if ($campo['tipo_campo'] == 'data') $tipo_input = 'date';
                                        if ($campo['tipo_campo'] == 'numero' || $campo['tipo_campo'] == 'moeda') $tipo_input = 'number';
                                        echo '<input type="'. $tipo_input .'" class="form-control" name="filtros['. $campo['id'] .']" id="filtro_'. $campo['id'] .'" value="'. htmlspecialchars($valor_filtro_atual) .'">';
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-flex align-items-center">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
                            <a href="portal.php?slug=<?php echo htmlspecialchars($slug_portal); ?>" class="btn btn-secondary ms-2"><i class="bi bi-eraser-fill"></i> Limpar Filtros</a>
                            <div class="dropdown ms-auto">
                                <button class="btn btn-success dropdown-toggle" type="button" id="dropdownExport" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-download"></i> Exportar Dados</button>
                                <?php $export_params = http_build_query($_GET); ?>
                                <ul class="dropdown-menu" aria-labelledby="dropdownExport">
                                    <li><a class="dropdown-item" href="exportar_dados.php?<?php echo $export_params; ?>&formato=csv">CSV</a></li>
                                    <li><a class="dropdown-item" href="exportar_dados.php?<?php echo $export_params; ?>&formato=xls">XLS (Excel)</a></li>
                                    <li><a class="dropdown-item" href="exportar_dados.php?<?php echo $export_params; ?>&formato=json">JSON</a></li>
                                    <li><a class="dropdown-item" href="exportar_dados.php?<?php echo $export_params; ?>&formato=xml">XML</a></li>
                                    <li><a class="dropdown-item" href="exportar_dados.php?<?php echo $export_params; ?>&formato=txt">TXT</a></li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card shadow-sm">
                <div class="card-header"><?php echo $total_itens; ?> Registros Encontrados</div>
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
                                <tr><td colspan="<?php echo count($campos_tabela); ?>" class="text-center p-4">Nenhum registro encontrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($dados_tabela as $linha): ?>
                                    <tr style="cursor: pointer;" onclick="window.location='detalhes.php?id=<?php echo $linha['id_registro_para_link']; ?>';">
                                        <?php 
                                        $id_registro_atual = $linha['id_registro_para_link'];
                                        unset($linha['id_registro_para_link']);
                                        ?>
                                        <?php foreach ($linha as $coluna_index => $valor): ?>
                                            <td>
                                                <?php
                                                $tipo_do_campo_atual = $campos_tabela[$coluna_index]['tipo_campo'];
                                                if ($tipo_do_campo_atual == 'anexo') {
                                                    if (!empty($valor)) echo '<i class="bi bi-paperclip"></i> Sim'; else echo 'Não';
                                                } elseif ($tipo_do_campo_atual == 'data' && !empty($valor)) {
                                                    $data_objeto = date_create($valor);
                                                    if ($data_objeto) { echo date_format($data_objeto, 'd/m/Y'); } 
                                                    else { echo htmlspecialchars($valor); }
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
                </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<footer class="p-3 mt-4"></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>