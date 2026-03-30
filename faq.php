<?php
require_once 'conexao.php';
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
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<header class="page-header">
    <div class="container-fluid container-custom-padding">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($page_title); ?></li>
                    </ol>
                </nav>
                <h1><?php echo htmlspecialchars($page_title); ?></h1>
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
                    <form method="GET" action="faq.php">
                        <div class="row">
                            <?php foreach ($campos_pesquisaveis as $campo): ?>
                                <div class="col-md-4 mb-3">
                                    <label for="filtro_<?php echo $campo['id']; ?>" class="form-label"><?php echo htmlspecialchars($campo['nome_campo']); ?></label>
                                    <?php
        $valor_filtro_atual = $filtros_ativos[$campo['id']] ?? '';

        if ($campo['tipo_campo'] == 'select') {
            $stmt_opcoes = $pdo->prepare("SELECT opcoes_campo FROM campos_portal WHERE id = ?");
            $stmt_opcoes->execute([$campo['id']]);
            $opcoes_str = $stmt_opcoes->fetchColumn();

            echo '<select class="form-select" name="filtros[' . $campo['id'] . ']" id="filtro_' . $campo['id'] . '">';
            echo '<option value="">-- Todos --</option>';

            $opcoes = [];
            if (strpos($opcoes_str, 'tabela:') === 0) {
                list(, $nome_tabela) = explode(':', $opcoes_str);
                $nome_tabela = trim($nome_tabela);

                $stmt_tabela_externa = $pdo->query("SELECT DISTINCT nome FROM " . preg_replace("/[^a-zA-Z0-9_]+/", "", $nome_tabela) . " ORDER BY nome");
                if ($stmt_tabela_externa) {
                    $opcoes_tabela = $stmt_tabela_externa->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($opcoes_tabela as $opt) {
                        $opcoes[] = trim($opt);
                    }
                }
            }
            else {
                $opcoes = array_map('trim', explode(',', $opcoes_str));
            }

            foreach ($opcoes as $opcao) {
                if (!empty($opcao)) {
                    $selected = ($valor_filtro_atual == $opcao) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($opcao) . '" ' . $selected . '>' . htmlspecialchars($opcao) . '</option>';
                }
            }
            echo '</select>';
        }
        else {
            echo '<input type="text" class="form-control" name="filtros[' . $campo['id'] . ']" id="filtro_' . $campo['id'] . '" value="' . htmlspecialchars($valor_filtro_atual) . '">';
        }
?>
                                </div>
                            <?php
    endforeach; ?>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
                        <a href="faq.php" class="btn btn-secondary ms-2"><i class="bi bi-eraser-fill"></i> Limpar Filtros</a>
                    </form>
                </div>
            </div>
            <?php
endif; ?>

            <div class="accordion" id="faqAccordion">
                <?php if (!empty($faqs)): ?>
                    <?php foreach ($faqs as $index => $faq): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-<?php echo $faq['id']; ?>">
                                <button class="accordion-button <?php if ($index > 0)
            echo 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $faq['id']; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse-<?php echo $faq['id']; ?>">
                                    <i class="bi bi-plus-circle me-2"></i><strong><?php echo htmlspecialchars($faq['Pergunta'] ?? 'Pergunta não disponível'); ?></strong>
                                    </button>
                            </h2>
                            <div id="collapse-<?php echo $faq['id']; ?>" class="accordion-collapse collapse <?php if ($index === 0)
            echo 'show'; ?>" aria-labelledby="heading-<?php echo $faq['id']; ?>" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <?php echo $faq['Resposta'] ?? 'Resposta não disponível.'; ?>
                                </div>
                            </div>
                        </div>
                    <?php
    endforeach; ?>
                <?php
else: ?>
                    <div class="alert alert-light">Nenhuma pergunta encontrada para os filtros selecionados.</div>
                <?php
endif; ?>
            </div>
        </main>
    </div>
</div>

<footer class="p-3 mt-4">
    <div class="container-fluid container-custom-padding">
        <div class="d-flex justify-content-between align-items-center" style="font-size: 14px;">
            <div>&copy; <?php echo date('Y'); ?> - Todos os direitos reservados.</div>
            <div>
                Desenvolvido por |
                <a href="https://www.upgyn.com.br" target="_blank" class="ms-2">
                    <img src="imagens/logo-up.png" alt="UPGYN" style="height: 40px; vertical-align: middle;">
                </a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const btnIncrease = document.getElementById('font-increase');
    const btnReset = document.getElementById('font-reset');
    const btnDecrease = document.getElementById('font-decrease');
    const btnContrast = document.getElementById('contrast-toggle');

    let currentFontSize = parseInt(localStorage.getItem('fontSize') || 16);
    let highContrast = localStorage.getItem('highContrast') === 'true';

    function applySettings() {
        body.style.fontSize = currentFontSize + 'px';
        if (highContrast) { body.classList.add('high-contrast'); } 
        else { body.classList.remove('high-contrast'); }
    }

    if(btnIncrease) { btnIncrease.addEventListener('click', function() { if (currentFontSize < 24) { currentFontSize += 2; localStorage.setItem('fontSize', currentFontSize); applySettings(); } }); }
    if(btnDecrease) { btnDecrease.addEventListener('click', function() { if (currentFontSize > 12) { currentFontSize -= 2; localStorage.setItem('fontSize', currentFontSize); applySettings(); } }); }
    if(btnReset) { btnReset.addEventListener('click', function() { currentFontSize = 16; localStorage.removeItem('fontSize'); applySettings(); }); }
    if(btnContrast) { btnContrast.addEventListener('click', function() { highContrast = !highContrast; localStorage.setItem('highContrast', highContrast); applySettings(); }); }
    
    applySettings();
});
</script>
</body>
</html>