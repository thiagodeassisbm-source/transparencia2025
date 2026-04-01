<?php
// /faq.php
require_once 'conexao.php';
require_once 'bootstrap_portal.php';

$page_title = "Perguntas Frequentes";

// Encontra o ID da seção "Perguntas Frequentes"
$stmt_portal = $pdo->prepare("SELECT id FROM portais WHERE nome = 'Perguntas Frequentes' LIMIT 1");
$stmt_portal->execute();
$id_portal = $stmt_portal->fetchColumn();

$faqs = [];
$campos_pesquisaveis = [];

if ($id_portal) {
    // --- LÓGICA DOS FILTROS ---
    $stmt_pesquisaveis = $pdo->prepare("SELECT id, nome_campo, tipo_campo FROM campos_portal WHERE id_portal = ? AND pesquisavel = 1 ORDER BY ordem");
    $stmt_pesquisaveis->execute([$id_portal]);
    $campos_pesquisaveis = $stmt_pesquisaveis->fetchAll();

    $filtros_ativos = $_GET['filtros'] ?? [];
    $params = [$id_portal];
    $sql_where_extra = "";

    foreach ($campos_pesquisaveis as $campo) {
        $id_campo_filtro = $campo['id'];
        if (isset($filtros_ativos[$id_campo_filtro]) && $filtros_ativos[$id_campo_filtro] !== '') {
            $valor_filtro = $filtros_ativos[$id_campo_filtro];
            $sql_where_extra .= " AND EXISTS (SELECT 1 FROM valores_registros vr WHERE vr.id_registro = r.id AND vr.id_campo = ? AND vr.valor LIKE ?)";
            $params[] = $id_campo_filtro;
            $params[] = '%' . $valor_filtro . '%';
        }
    }

    // Busca os registros (FAQs) desta seção, aplicando os filtros
    $sql_registros = "SELECT id FROM registros r WHERE id_portal = ? $sql_where_extra ORDER BY id";
    $stmt_registros = $pdo->prepare($sql_registros);
    $stmt_registros->execute($params);
    $registros_ids = $stmt_registros->fetchAll(PDO::FETCH_COLUMN);

    if ($registros_ids) {
        // Busca todos os valores para os FAQs encontrados
        $placeholders = implode(',', array_fill(0, count($registros_ids), '?'));
        $stmt_valores = $pdo->prepare(
            "SELECT r.id as registro_id, cp.nome_campo, vr.valor
             FROM registros r
             LEFT JOIN valores_registros vr ON r.id = vr.id_registro
             LEFT JOIN campos_portal cp ON vr.id_campo = cp.id
             WHERE r.id IN ($placeholders)
             ORDER BY r.id, cp.ordem"
        );
        $stmt_valores->execute($registros_ids);
        $valores_raw = $stmt_valores->fetchAll(PDO::FETCH_ASSOC);

        $dados_organizados = [];
        foreach ($valores_raw as $valor) {
            $dados_organizados[$valor['registro_id']][$valor['nome_campo']] = $valor['valor'];
        }
        foreach ($registros_ids as $id) {
            $faqs[] = array_merge(['id' => $id], $dados_organizados[$id] ?? []);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - Portal da Transparência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/style.css?v=<?php echo time(); ?>">
    <style>
        .faq-card .accordion-button:not(.collapsed) {
            background-color: var(--cor-principal, #2ca444);
            color: white;
        }
        .faq-card .accordion-button:after {
            filter: grayscale(1) invert(1);
        }
    </style>
</head>
<body class="bg-light">

<?php include 'header_publico.php'; ?>

<div class="container-fluid py-5">
    <div class="row">
        <div class="col-md-3 col-lg-2 d-none d-md-block px-0 ps-3 mb-4">
            <?php include 'menu.php'; ?>
        </div>
        <main class="col-md-9 ms-auto col-lg-10 px-md-5">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0 fw-bold"><?php echo htmlspecialchars($page_title); ?></h2>
            </div>
            
            <?php if (!empty($campos_pesquisaveis)): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-funnel-fill me-2"></i> Filtros de Pesquisa</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="faq.php">
                        <div class="row">
                            <?php foreach ($campos_pesquisaveis as $campo): ?>
                                <div class="col-md-4 mb-3">
                                    <label for="filtro_<?php echo $campo['id']; ?>" class="form-label small fw-bold text-muted"><?php echo htmlspecialchars($campo['nome_campo']); ?></label>
                                    <?php
                                    $valor_filtro_atual = $filtros_ativos[$campo['id']] ?? '';
                                    if ($campo['tipo_campo'] == 'select') {
                                        $stmt_opcoes = $pdo->prepare("SELECT opcoes_campo FROM campos_portal WHERE id = ?");
                                        $stmt_opcoes->execute([$campo['id']]);
                                        $opcoes_str = $stmt_opcoes->fetchColumn();
                                        echo '<select class="form-select" name="filtros[' . $campo['id'] . ']" id="filtro_' . $campo['id'] . '">';
                                        echo '<option value="">-- Todos --</option>';
                                        $opcoes = array_map('trim', explode(',', $opcoes_str));
                                        foreach ($opcoes as $opcao) {
                                            if (!empty($opcao)) {
                                                $selected = ($valor_filtro_atual == $opcao) ? 'selected' : '';
                                                echo '<option value="' . htmlspecialchars($opcao) . '" ' . $selected . '>' . htmlspecialchars($opcao) . '</option>';
                                            }
                                        }
                                        echo '</select>';
                                    } else {
                                        echo '<input type="text" class="form-control" name="filtros[' . $campo['id'] . ']" id="filtro_' . $campo['id'] . '" value="' . htmlspecialchars($valor_filtro_atual) . '">';
                                    }
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-dynamic-primary px-4 shadow-sm"><i class="bi bi-search me-2"></i>Filtrar</button>
                            <a href="faq.php" class="btn btn-light border px-4 shadow-sm"><i class="bi bi-eraser-fill me-2"></i>Limpar</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="accordion faq-card shadow-sm rounded-3 border-0 bg-white" id="faqAccordion">
                <?php if (!empty($faqs)): ?>
                    <?php foreach ($faqs as $index => $faq): ?>
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header" id="heading-<?php echo $faq['id']; ?>">
                                <button class="accordion-button <?php if ($index > 0) echo 'collapsed'; ?> py-3 px-4 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $faq['id']; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse-<?php echo $faq['id']; ?>">
                                    <i class="bi bi-patch-question me-3 opacity-50"></i> <?php echo htmlspecialchars($faq['Pergunta'] ?? 'Pergunta não disponível'); ?>
                                </button>
                            </h2>
                            <div id="collapse-<?php echo $faq['id']; ?>" class="accordion-collapse collapse <?php if ($index === 0) echo 'show'; ?>" aria-labelledby="heading-<?php echo $faq['id']; ?>" data-bs-parent="#faqAccordion">
                                <div class="accordion-body px-4 py-4 text-muted border-top bg-light-subtle">
                                    <?php echo $faq['Resposta'] ?? 'Resposta não disponível.'; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-5 text-center bg-white rounded-3">
                        <i class="bi bi-info-circle display-4 text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">Nenhuma pergunta encontrada para os filtros selecionados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php 
$custom_container_class = "container-custom-padding";
include 'footer_publico.php'; 
?>
</body>
</html>