<?php
require_once 'auth_check.php';
require_once '../conexao.php';

$portal_id = filter_input(INPUT_GET, 'portal_id', FILTER_VALIDATE_INT);
if (!$portal_id) {
    header('Location: index.php');
    exit;
}

$perfil_usuario = $_SESSION['admin_user_perfil'];

$sql_portal = 'SELECT nome FROM portais WHERE id = ?';
$params_portal = [$portal_id];

if (!$_SESSION['is_superadmin']) {
    $sql_portal .= ' AND id_prefeitura = ?';
    $params_portal[] = $_SESSION['id_prefeitura'];
}

$stmt_portal = $pdo->prepare($sql_portal);
$stmt_portal->execute($params_portal);
$secao = $stmt_portal->fetch();
if (!$secao) {
    header('Location: index.php');
    exit;
}

if (!tem_permissao('form_' . $portal_id, 'ver')) {
    header('Location: index.php');
    exit;
}

$itens_por_pagina = 15;
$pagina_atual = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
if ($pagina_atual < 1) {
    $pagina_atual = 1;
}
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$stmt_total = $pdo->prepare('SELECT COUNT(id) FROM registros WHERE id_portal = ?');
$stmt_total->execute([$portal_id]);
$total_itens = (int) $stmt_total->fetchColumn();
$total_paginas = (int) ceil($total_itens / $itens_por_pagina);

$stmt_registros = $pdo->prepare(
    'SELECT r.id, r.exercicio, r.mes, r.unidade_gestora, r.id_tipo_documento, td.nome AS nome_documento
     FROM registros r
     LEFT JOIN tipos_documento td ON r.id_tipo_documento = td.id
     WHERE r.id_portal = ?
     ORDER BY r.id DESC
     LIMIT ? OFFSET ?'
);
$stmt_registros->bindValue(1, $portal_id, PDO::PARAM_INT);
$stmt_registros->bindValue(2, $itens_por_pagina, PDO::PARAM_INT);
$stmt_registros->bindValue(3, $offset, PDO::PARAM_INT);
$stmt_registros->execute();
$registros_base = $stmt_registros->fetchAll(PDO::FETCH_ASSOC);
$registros_ids = array_column($registros_base, 'id');

$map_nome_tipo_documento = [];
$ids_tipo = array_unique(array_filter(array_map('intval', array_column($registros_base, 'id_tipo_documento'))));
if ($ids_tipo !== []) {
    $ph = implode(',', array_fill(0, count($ids_tipo), '?'));
    $stmt_tipos = $pdo->prepare("SELECT id, nome FROM tipos_documento WHERE id IN ($ph)");
    $stmt_tipos->execute(array_values($ids_tipo));
    foreach ($stmt_tipos->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map_nome_tipo_documento[(int) $row['id']] = (string) $row['nome'];
    }
}

$stmt_campos = $pdo->prepare('SELECT id, nome_campo, tipo_campo FROM campos_portal WHERE id_portal = ? ORDER BY ordem, id');
$stmt_campos->execute([$portal_id]);
$campos_dinamicos = $stmt_campos->fetchAll(PDO::FETCH_ASSOC);

$valores_dinamicos = [];
if (!empty($registros_ids)) {
    $placeholders = implode(',', array_fill(0, count($registros_ids), '?'));
    $stmt_valores = $pdo->prepare("SELECT id_registro, id_campo, valor FROM valores_registros WHERE id_registro IN ($placeholders)");
    $stmt_valores->execute($registros_ids);
    foreach ($stmt_valores->fetchAll() as $valor) {
        $valores_dinamicos[$valor['id_registro']][$valor['id_campo']] = $valor['valor'];
    }
}

$colunas_totais = 4 + count($campos_dinamicos) + 1;
$page_title_for_header = 'Lançamentos: ' . $secao['nome'];
include 'admin_header.php';
?>

<style>
    .vl-header-card {
        background: linear-gradient(135deg, var(--sidebar-header-bg, #36c0d3) 0%, #2d9fb0 100%);
        color: #fff;
        border-radius: 15px;
        padding: 1.35rem 1.75rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 8px 24px rgba(54, 192, 211, 0.22);
    }
    .vl-header-card .btn-light {
        background: #fff;
        color: #0f766e;
        border: none;
        font-weight: 600;
    }
    .vl-header-card .btn-light:hover {
        background: #f0fdfa;
        color: #134e4a;
    }
    .vl-table-card {
        border-radius: 15px !important;
        border: none !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
        overflow: hidden;
    }
    .vl-table-wrap .table thead th {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 11px;
        font-weight: 700;
        color: #555;
        border-bottom: 2px solid #eee;
        padding: 12px 14px;
        white-space: nowrap;
    }
    .vl-table-wrap .table tbody td {
        padding: 12px 14px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f1f1;
    }
    @media (max-width: 1400px) {
        .vl-table-wrap .table { font-size: 0.82rem; }
        .vl-table-wrap .btn-sm { padding: 0.25rem 0.45rem !important; font-size: 0.75rem !important; }
    }
    .vl-table-wrap .table td {
        max-width: 300px;
        word-wrap: break-word;
    }
    .col-exercicio { width: 80px; }
    .col-mes { width: 100px; }
    .col-acoes { width: 110px; }
    .btn-anexo {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 2px 8px;
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 600;
        border-radius: 6px;
        background: #eef2f7;
        color: #475569;
        text-decoration: none;
        border: 1px solid #cbd5e1;
        transition: all 0.2s;
    }
    .btn-anexo:hover {
        background: var(--sidebar-header-bg, #36c0d3);
        color: #fff;
        border-color: var(--sidebar-header-bg, #36c0d3);
    }
    .vl-btn-action {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        transition: all 0.2s;
    }
    .vl-btn-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
    }
</style>

<div class="container-fluid container-custom-padding py-2">
    <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
            <?php echo htmlspecialchars($_SESSION['mensagem_sucesso']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php unset($_SESSION['mensagem_sucesso']); ?>
    <?php endif; ?>

    <div class="vl-header-card d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h2 class="h5 fw-bold mb-1"><i class="bi bi-table me-2"></i><?php echo htmlspecialchars($secao['nome']); ?></h2>
            <p class="mb-0 opacity-90 small">Planilha de lançamentos · <?php echo number_format($total_itens, 0, ',', '.'); ?> registro(s)</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if (tem_permissao('form_' . $portal_id, 'lancar')): ?>
                <a href="lancar_dados.php?portal_id=<?php echo (int) $portal_id; ?>" class="btn btn-light rounded-pill px-4 shadow-sm fw-bold">
                    <i class="bi bi-plus-circle me-1"></i> Novo lançamento
                </a>
            <?php endif; ?>
            <a href="criar_secoes.php" class="btn btn-outline-light border border-white border-opacity-50 rounded-pill px-3 fw-semibold">
                <i class="bi bi-arrow-left me-1"></i> Voltar às seções
            </a>
        </div>
    </div>

    <div class="card vl-table-card">
        <div class="card-body p-0 vl-table-wrap">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="col-exercicio">Exercício</th>
                            <th class="col-mes">Mês</th>
                            <th>Unidade gestora</th>
                            <th>Tipo documento</th>
                            <?php foreach ($campos_dinamicos as $campo): ?>
                                <th><?php echo htmlspecialchars($campo['nome_campo']); ?></th>
                            <?php endforeach; ?>
                            <th class="text-end col-acoes">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registros_base)): ?>
                            <tr>
                                <td colspan="<?php echo (int) $colunas_totais; ?>" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                    Nenhum registro encontrado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($registros_base as $registro): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) $registro['exercicio']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $registro['mes']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $registro['unidade_gestora']); ?></td>
                                    <td><?php
                                    $idTipo = $registro['id_tipo_documento'] ?? null;
                                    $idTipoInt = ($idTipo !== null && $idTipo !== '') ? (int) $idTipo : 0;
                                    $nomeTipo = '';
                                    if ($idTipoInt > 0) {
                                        $nomeTipo = $map_nome_tipo_documento[$idTipoInt] ?? '';
                                    }
                                    if ($nomeTipo === '' && isset($registro['nome_documento'])) {
                                        $nomeTipo = trim((string) $registro['nome_documento']);
                                    }
                                    if ($nomeTipo === '') {
                                        $nomeTipo = $idTipoInt > 0 ? 'Tipo não cadastrado' : 'Não se aplica';
                                    }
                                    echo htmlspecialchars($nomeTipo);
                                    ?></td>
                                    <?php
                                    $valores_deste_registro = $valores_dinamicos[$registro['id']] ?? [];
                                    foreach ($campos_dinamicos as $campo) {
                                        $valor = $valores_deste_registro[$campo['id']] ?? '';
                                        if ($campo['tipo_campo'] === 'anexo' && !empty($valor)) {
                                            echo '<td><a href="../' . htmlspecialchars($valor) . '" target="_blank" class="btn-anexo"><i class="bi bi-file-earmark-pdf"></i> Ver</a></td>';
                                        } elseif ($campo['tipo_campo'] === 'data' && !empty($valor)) {
                                            $data_objeto = date_create($valor);
                                            $valor_formatado = $data_objeto ? date_format($data_objeto, 'd/m/Y') : $valor;
                                            echo '<td>' . htmlspecialchars($valor_formatado) . '</td>';
                                        } else {
                                            echo '<td>' . htmlspecialchars(mb_strimwidth((string) $valor, 0, 120, '…', 'UTF-8')) . '</td>';
                                        }
                                    }
                                    ?>
                                    <td class="text-end">
                                        <?php if (tem_permissao('form_' . $portal_id, 'editar')): ?>
                                            <a href="editar_lancamento.php?registro_id=<?php echo (int) $registro['id']; ?>" class="btn btn-primary btn-sm vl-btn-action" data-bs-toggle="tooltip" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (tem_permissao('form_' . $portal_id, 'excluir')): ?>
                                            <form method="POST" action="excluir_lancamento.php" class="d-inline ms-1" onsubmit="return confirm('Tem certeza que deseja excluir este lançamento?');">
                                                <input type="hidden" name="registro_id" value="<?php echo (int) $registro['id']; ?>">
                                                <input type="hidden" name="portal_id" value="<?php echo (int) $portal_id; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm vl-btn-action" data-bs-toggle="tooltip" title="Excluir">
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
        </div>
        <?php if ($total_paginas > 1): ?>
            <div class="card-footer bg-white border-top py-3">
                <nav>
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <li class="page-item <?php if ($pagina_atual <= 1) {
                            echo 'disabled';
                        } ?>">
                            <?php
                            $query_params = $_GET;
                            $query_params['page'] = $pagina_atual - 1;
                            ?>
                            <a class="page-link rounded-pill shadow-none" href="?<?php echo http_build_query($query_params); ?>">Anterior</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <?php
                            $query_params = $_GET;
                            $query_params['page'] = $i;
                            ?>
                            <li class="page-item <?php if ($pagina_atual == $i) {
                                echo 'active';
                            } ?>">
                                <a class="page-link rounded-pill shadow-none" href="?<?php echo http_build_query($query_params); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php if ($pagina_atual >= $total_paginas) {
                            echo 'disabled';
                        } ?>">
                            <?php
                            $query_params = $_GET;
                            $query_params['page'] = $pagina_atual + 1;
                            ?>
                            <a class="page-link rounded-pill shadow-none" href="?<?php echo http_build_query($query_params); ?>">Próximo</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
