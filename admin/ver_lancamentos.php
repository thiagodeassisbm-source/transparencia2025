<?php
require_once 'auth_check.php';
require_once '../conexao.php';

$portal_id = filter_input(INPUT_GET, 'portal_id', FILTER_VALIDATE_INT);
if (!$portal_id) { header("Location: index.php"); exit; }

// Pega o perfil do usuário da sessão para decidir quais botões mostrar
$perfil_usuario = $_SESSION['admin_user_perfil'];

// Busca informações da seção (Aplicando Trava SaaS)
$sql_portal = "SELECT nome FROM portais WHERE id = ?";
$params_portal = [$portal_id];

if (!$_SESSION['is_superadmin']) {
    $sql_portal .= " AND id_prefeitura = ?";
    $params_portal[] = $_SESSION['id_prefeitura'];
}

$stmt_portal = $pdo->prepare($sql_portal);
$stmt_portal->execute($params_portal);
$secao = $stmt_portal->fetch();
if (!$secao) { header("Location: index.php"); exit; }

// TRAVA DE SEGURANÇA: Verifica permissão granular de acesso à seção
if (!tem_permissao('form_' . $portal_id, 'ver')) {
    header("Location: index.php");
    exit;
}

// --- LÓGICA DE PAGINAÇÃO ---
$itens_por_pagina = 15;
$pagina_atual = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
if ($pagina_atual < 1) { $pagina_atual = 1; }
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$stmt_total = $pdo->prepare("SELECT COUNT(id) FROM registros WHERE id_portal = ?");
$stmt_total->execute([$portal_id]);
$total_itens = $stmt_total->fetchColumn();
$total_paginas = ceil($total_itens / $itens_por_pagina);

// 1. Busca os dados ESTRUTURADOS da tabela 'registros' com LIMIT e OFFSET
$stmt_registros = $pdo->prepare(
    "SELECT r.id, r.exercicio, r.mes, r.unidade_gestora, td.nome as nome_documento
     FROM registros r
     LEFT JOIN tipos_documento td ON r.id_tipo_documento = td.id
     WHERE r.id_portal = ? 
     ORDER BY r.id DESC
     LIMIT ? OFFSET ?"
);
$stmt_registros->bindValue(1, $portal_id, PDO::PARAM_INT);
$stmt_registros->bindValue(2, $itens_por_pagina, PDO::PARAM_INT);
$stmt_registros->bindValue(3, $offset, PDO::PARAM_INT);
$stmt_registros->execute();
$registros_base = $stmt_registros->fetchAll(PDO::FETCH_ASSOC);
$registros_ids = array_column($registros_base, 'id');

// 2. Busca os cabeçalhos e TIPOS dos campos DINÂMICOS
$stmt_campos = $pdo->prepare("SELECT id, nome_campo, tipo_campo FROM campos_portal WHERE id_portal = ? ORDER BY ordem, id");
$stmt_campos->execute([$portal_id]);
$campos_dinamicos = $stmt_campos->fetchAll(PDO::FETCH_ASSOC);
$id_campos_ordenados = array_column($campos_dinamicos, 'id');

// 3. Busca todos os valores DINÂMICOS
$valores_dinamicos = [];
if (!empty($registros_ids)) {
    $placeholders = implode(',', array_fill(0, count($registros_ids), '?'));
    $stmt_valores = $pdo->prepare("SELECT id_registro, id_campo, valor FROM valores_registros WHERE id_registro IN ($placeholders)");
    $stmt_valores->execute($registros_ids);
    $valores_raw = $stmt_valores->fetchAll();
    foreach ($valores_raw as $valor) {
        $valores_dinamicos[$valor['id_registro']][$valor['id_campo']] = $valor['valor'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lançamentos - <?php echo htmlspecialchars($secao['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        /* Ajustes de densidade para telas de notebook (média resolução) */
        @media (max-width: 1400px) {
            .table { font-size: 0.8rem !important; }
            .btn-sm { padding: 0.25rem 0.4rem !important; font-size: 0.75rem !important; }
            .table th, .table td { padding: 0.4rem !important; }
        }
        
        /* Evita que colunas de texto fiquem muito estreitas ou muito largas */
        .table td { max-width: 300px; word-wrap: break-word; vertical-align: middle; }
        .col-exercicio { width: 80px; }
        .col-mes { width: 100px; }
        .col-acoes { width: 100px; }
        
        /* Estilização para o link de anexo */
        .btn-anexo {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 8px;
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
            border-radius: 4px;
            background: #eef2f7;
            color: #475569;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            transition: all 0.2s;
        }
        .btn-anexo:hover { background: #36c0d3; color: #fff; border-color: #36c0d3; }
    </style>
</head>
<body class="bg-light-subtle">

<?php 
$page_title_for_header = 'Lançamentos: ' . htmlspecialchars($secao['nome']);
include 'admin_header.php'; 
?>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-12">
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
                    <span>Total de <?php echo $total_itens; ?> registro(s) cadastrado(s)</span>
                    <?php if (tem_permissao('form_' . $portal_id, 'lancar')): ?>
                        <a href="lancar_dados.php?portal_id=<?php echo $portal_id; ?>" class="btn btn-success btn-sm">
                            <i class="bi bi-plus-circle"></i> Novo Lançamento
                        </a>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th class="col-exercicio">Exercício</th>
                                <th class="col-mes">Mês</th>
                                <th>Unidade Gestora</th>
                                <th>Tipo Documento</th>
                                <?php foreach ($campos_dinamicos as $campo): ?>
                                    <th><?php echo htmlspecialchars($campo['nome_campo']); ?></th>
                                <?php endforeach; ?>
                                <th class="text-end col-acoes">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($registros_base)): ?>
                                <tr><td colspan="<?php echo 4 + count($campos_dinamicos) + 1; ?>" class="text-center">Nenhum registro encontrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($registros_base as $registro): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($registro['exercicio']); ?></td>
                                        <td><?php echo htmlspecialchars($registro['mes']); ?></td>
                                        <td><?php echo htmlspecialchars($registro['unidade_gestora']); ?></td>
                                        
                                        <td><?php echo htmlspecialchars($registro['nome_documento'] ?? 'Preenchimento não foi realizado'); ?></td>
                                        
                                        <?php
                                        $valores_deste_registro = $valores_dinamicos[$registro['id']] ?? [];
                                        foreach ($campos_dinamicos as $campo) {
                                            $valor = $valores_deste_registro[$campo['id']] ?? '';
                                            
                                            // Lógica especial por tipo de campo
                                            if ($campo['tipo_campo'] === 'anexo' && !empty($valor)) {
                                                echo '<td><a href="../' . htmlspecialchars($valor) . '" target="_blank" class="btn-anexo"><i class="bi bi-file-earmark-pdf"></i> Ver</a></td>';
                                            } elseif ($campo['tipo_campo'] === 'data' && !empty($valor)) {
                                                $data_objeto = date_create($valor);
                                                $valor_formatado = $data_objeto ? date_format($data_objeto, 'd/m/Y') : $valor;
                                                echo '<td>' . htmlspecialchars($valor_formatado) . '</td>';
                                            } else {
                                                $valor_formatado = htmlspecialchars(mb_strimwidth((string)$valor, 0, 80, "..."));
                                                echo '<td>' . $valor_formatado . '</td>';
                                            }
                                        }
                                        ?>

                                        <td class="text-end">
                                            <?php if (tem_permissao('form_' . $portal_id, 'editar')): ?>
                                                <a href="editar_lancamento.php?registro_id=<?php echo $registro['id']; ?>" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Editar Lançamento">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (tem_permissao('form_' . $portal_id, 'excluir')): ?>
                                            <form method="POST" action="excluir_lancamento.php" class="d-inline ms-1" onsubmit="return confirm('Tem certeza que deseja excluir este lançamento?');">
                                                <input type="hidden" name="registro_id" value="<?php echo $registro['id']; ?>">
                                                <input type="hidden" name="portal_id" value="<?php echo $portal_id; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" data-bs-toggle="tooltip" title="Excluir Lançamento">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
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
                        <li class="page-item <?php if($pagina_atual <= 1) echo 'disabled'; ?>">
                            <?php $query_params['page'] = $pagina_atual - 1; ?>
                            <a class="page-link" href="?<?php echo http_build_query($query_params); ?>">Anterior</a>
                        </li>
                        <?php for($i=1; $i<=$total_paginas; $i++): $query_params['page'] = $i; ?>
                        <li class="page-item <?php if($pagina_atual == $i) echo 'active'; ?>">
                            <a class="page-link" href="?<?php echo http_build_query($query_params); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php if($pagina_atual >= $total_paginas) echo 'disabled'; ?>">
                            <?php $query_params['page'] = $pagina_atual + 1; ?>
                            <a class="page-link" href="?<?php echo http_build_query($query_params); ?>">Próximo</a>
                        </li>
                    </ul></nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<footer class="text-center p-3 bg-light mt-4">
    &copy; <?php echo date('Y'); ?> - Todos os direitos reservados.
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl) });
</script>
</body>
</html>