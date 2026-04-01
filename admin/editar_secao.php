<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Variáveis de sessão do usuário
$perfil_usuario = $_SESSION['admin_user_perfil'];
$id_usuario_logado = $_SESSION['admin_user_id'];
$nome_usuario_logado = $_SESSION['admin_user_nome'] ?? 'Usuário';

$secao_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$card_id = filter_input(INPUT_GET, 'card_id', FILTER_VALIDATE_INT);

if (!$card_id && !$secao_id) { header("Location: criar_secoes.php"); exit; }

// Se veio apenas o Portal ID, tenta achar o Card ID
if (!$card_id && $secao_id) {
    $stmt_c = $pdo->prepare("SELECT id FROM cards_informativos WHERE id_secao = ?");
    $stmt_c->execute([$secao_id]);
    $card_id = $stmt_c->fetchColumn();
}

// Processa o formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        $id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
        $card_titulo = trim($_POST['card_titulo'] ?? '');
        $card_subtitulo = trim($_POST['card_subtitulo'] ?? '');
        $card_ordem = (int)($_POST['card_ordem'] ?? 0);
        $tipo_icone = $_POST['tipo_icone'] ?? 'imagem';
        $caminho_icone_antigo = $_POST['caminho_icone_antigo'] ?? '';

        if (empty($card_titulo)) { throw new Exception("O título do card é obrigatório."); }
        
        // --- Gerencia o Ícone ---
        $caminho_icone = $caminho_icone_antigo;
        if ($tipo_icone === 'imagem') {
            if (isset($_FILES['card_icone']) && $_FILES['card_icone']['error'] === UPLOAD_ERR_OK) {
                // Remove antigo se era imagem
                if (!empty($caminho_icone_antigo) && strpos($caminho_icone_antigo, 'bi-') === false && file_exists($caminho_icone_antigo)) { 
                    unlink($caminho_icone_antigo); 
                }
                $upload_dir = '../uploads/';
                $nome_arquivo = 'card-' . uniqid() . '-' . basename($_FILES['card_icone']['name']);
                $caminho_destino = $upload_dir . $nome_arquivo;
                move_uploaded_file($_FILES['card_icone']['tmp_name'], $caminho_destino);
                $caminho_icone = $caminho_destino;
            }
        } else {
            $caminho_icone = trim($_POST['icone_bootstrap'] ?? 'bi-info-circle');
        }

        // --- Gerencia o Destino ---
        $link_externo = isset($_POST['link_pagina_externa']);
        $link_pagina_conteudo = isset($_POST['link_pagina_conteudo']);
        $link_pagina_sistema = isset($_POST['link_pagina_sistema']);
        $link_url = null;

        if ($link_externo) {
            $link_url = trim($_POST['card_link_externo'] ?? '');
        } elseif ($link_pagina_conteudo) {
            $pagina_slug = $_POST['pagina_slug'] ?? '';
            $link_url = 'pagina.php?slug=' . $pagina_slug;
        } elseif ($link_pagina_sistema) {
            $link_url = $_POST['pagina_sistema'] ?? '';
        }

        // --- Atualiza os dados da Seção (Portal) se existir ---
        if ($secao_id) {
            $secao_nome = trim($_POST['secao_nome'] ?? '');
            $secao_descricao = trim($_POST['secao_descricao'] ?? '');
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $secao_nome)));
            
            $stmt_secao = $pdo->prepare("UPDATE portais SET nome = ?, descricao = ?, slug = ?, id_categoria = ? WHERE id = ?");
            $stmt_secao->execute([$secao_nome, $secao_descricao, $slug, $id_categoria, $secao_id]);
        }

        // --- Atualiza o Card ---
        $stmt_card = $pdo->prepare("UPDATE cards_informativos SET id_categoria = ?, link_url = ?, titulo = ?, subtitulo = ?, caminho_icone = ?, tipo_icone = ?, ordem = ? WHERE id = ?");
        $stmt_card->execute([$id_categoria, $link_url, $card_titulo, $card_subtitulo, $caminho_icone, $tipo_icone, $card_ordem, $card_id]);

        $pdo->commit();
        registrar_log($pdo, 'EDIÇÃO', 'portais', "Atualizou a seção/card: $secao_nome (ID: $secao_id)");
        $_SESSION['mensagem_sucesso'] = "Seção e Card atualizados com sucesso!";
        header("Location: criar_secoes.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $erro = $e->getMessage();
    }
}

// Busca os dados para preencher o formulário
$secao_atual = null;
if ($secao_id) {
    $stmt_secao = $pdo->prepare("SELECT * FROM portais WHERE id = ?");
    $stmt_secao->execute([$secao_id]);
    $secao_atual = $stmt_secao->fetch();
}

$stmt_card = $pdo->prepare("SELECT * FROM cards_informativos WHERE id = ?");
$stmt_card->execute([$card_id]);
$card_atual = $stmt_card->fetch();

if (!$card_atual) { header("Location: criar_secoes.php"); exit; }

$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY ordem ASC")->fetchAll();
$paginas = $pdo->query("SELECT slug, titulo FROM paginas ORDER BY titulo ASC")->fetchAll();

// Lógica para detectar o tipo de link atual
$current_link_type = 'tabela';
$current_link_val = $card_atual['link_url'] ?? '';
if (!empty($current_link_val)) {
    if (strpos($current_link_val, 'http') === 0) { $current_link_type = 'externo'; }
    elseif (strpos($current_link_val, 'pagina.php') === 0) { $current_link_type = 'conteudo'; }
    else { $current_link_type = 'sistema'; }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Seção e Card - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .bg-light-subtle { background-color: #f8fafc !important; }
        .card { border-radius: 12px; }
        .fw-bold { color: #2d3748; }
    </style>
</head>
<body class="bg-light-subtle">

<?php
$page_title_for_header = 'Editar Seção / Card';
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-xl-10 mx-auto">
            
            <div class="d-flex align-items-center mb-4">
                <a href="criar_secoes.php" class="btn btn-light border-0 shadow-sm me-3 text-muted"><i class="bi bi-arrow-left"></i> Voltar</a>
                <h4 class="mb-0 fw-bold">Editar Configurações: <?php echo htmlspecialchars($secao_atual['nome'] ?? $card_atual['titulo']); ?></h4>
            </div>

            <?php if (isset($erro)): ?>
                <div class="alert alert-danger shadow-sm"><?php echo $erro; ?></div>
            <?php endif; ?>

            <form method="POST" action="editar_secao.php?id=<?php echo $secao_id; ?>&card_id=<?php echo $card_id; ?>" enctype="multipart/form-data">
                
                <!-- 1. DADOS DO CARD -->
                <div class="card mb-4 shadow-sm border-0">
                    <div class="card-header bg-white py-3 fw-bold text-primary"><i class="bi bi-1-circle me-2"></i>1. Dados do Card (Atalho na Página Inicial)</div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Título do Card*</label>
                                <input type="text" class="form-control" name="card_titulo" value="<?php echo htmlspecialchars($card_atual['titulo'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subtítulo do Card*</label>
                                <input type="text" class="form-control" name="card_subtitulo" value="<?php echo htmlspecialchars($card_atual['subtitulo'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoria*</label>
                                <select class="form-select" name="id_categoria" required>
                                    <option value="">-- Selecione --</option>
                                    <?php foreach($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $card_atual['id_categoria']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ordem de Exibição</label>
                                <input type="number" class="form-control" name="card_ordem" value="<?php echo htmlspecialchars($card_atual['ordem'] ?? 0); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Tipo de Ícone</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="tipo_icone" id="tipo_imagem" value="imagem" <?php echo ($card_atual['tipo_icone'] ?? 'imagem') === 'imagem' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary" for="tipo_imagem"><i class="bi bi-image me-2"></i>Imagem (Upload)</label>

                                <input type="radio" class="btn-check" name="tipo_icone" id="tipo_bootstrap" value="bootstrap" <?php echo ($card_atual['tipo_icone'] ?? '') === 'bootstrap' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary" for="tipo_bootstrap"><i class="bi bi-bootstrap me-2"></i>Ícone do Sistema</label>
                            </div>
                        </div>

                        <!-- Upload de Imagem -->
                        <div id="campo_upload_icone" class="mb-3" style="display: <?php echo ($card_atual['tipo_icone'] ?? 'imagem') === 'imagem' ? 'block' : 'none'; ?>;">
                            <label class="form-label">Ícone Atual / Selecionar Novo (PNG/SVG)*</label>
                            <div class="d-flex align-items-center gap-3 p-3 border rounded bg-light">
                                <?php if($card_atual && ($card_atual['tipo_icone'] ?? 'imagem') === 'imagem' && !empty($card_atual['caminho_icone'])): ?>
                                    <img src="<?php echo $card_atual['caminho_icone']; ?>" style="width: 48px; height: 48px; object-fit: contain;">
                                <?php endif; ?>
                                <input type="file" class="form-control" name="card_icone">
                                <input type="hidden" name="caminho_icone_antigo" value="<?php echo htmlspecialchars($card_atual['caminho_icone'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Ícone Bootstrap -->
                        <div id="campo_bootstrap_icone" class="mb-3" style="display: <?php echo ($card_atual['tipo_icone'] ?? '') === 'bootstrap' ? 'block' : 'none'; ?>;">
                            <label class="form-label">Classe do Ícone (Ex: bi-star)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="<?php echo ($card_atual['tipo_icone'] ?? '') === 'bootstrap' ? 'bi '.$card_atual['caminho_icone'] : 'bi bi-info-circle'; ?> text-primary" id="preview_bi"></i></span>
                                <input type="text" class="form-control" id="icone_bootstrap" name="icone_bootstrap" value="<?php echo ($card_atual['tipo_icone'] ?? '') === 'bootstrap' ? htmlspecialchars($card_atual['caminho_icone']) : ''; ?>" placeholder="bi-gear">
                            </div>
                            <small class="text-muted"><a href="https://icons.getbootstrap.com/" target="_blank">Abrir Catálogo</a></small>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="fw-bold mb-3">Onde este card deve levar o usuário?</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="form-check p-3 border rounded h-100 bg-light-subtle">
                                    <input class="form-check-input" type="checkbox" id="link_pagina_conteudo" name="link_pagina_conteudo" <?php echo $current_link_type === 'conteudo' ? 'checked' : ''; ?>>
                                    <label class="form-check-label ms-2" for="link_pagina_conteudo">Página de Conteúdo (Texto/Imagens)</label>
                                    <div id="campo_pagina_conteudo" class="mt-2" style="display: <?php echo $current_link_type === 'conteudo' ? 'block' : 'none'; ?>;">
                                        <?php 
                                        $saved_slug = '';
                                        if($current_link_type === 'conteudo') {
                                            parse_str(parse_url($current_link_val, PHP_URL_QUERY), $query_params);
                                            $saved_slug = $query_params['slug'] ?? '';
                                        }
                                        ?>
                                        <select class="form-select form-select-sm" name="pagina_slug">
                                            <option value="">-- Selecionar --</option>
                                            <?php foreach($paginas as $p): ?>
                                                <option value="<?php echo $p['slug']; ?>" <?php echo $p['slug'] === $saved_slug ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['titulo']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="form-check p-3 border rounded h-100 bg-light-subtle">
                                    <input class="form-check-input" type="checkbox" id="link_pagina_sistema" name="link_pagina_sistema" <?php echo $current_link_type === 'sistema' ? 'checked' : ''; ?>>
                                    <label class="form-check-label ms-2" for="link_pagina_sistema">Página Pronta do Sistema</label>
                                    <div id="campo_pagina_sistema" class="mt-2" style="display: <?php echo $current_link_type === 'sistema' ? 'block' : 'none'; ?>;">
                                        <select class="form-select form-select-sm" name="pagina_sistema">
                                            <option value="">-- Selecionar --</option>
                                            <option value="estrutura.php" <?php echo $current_link_val === 'estrutura.php' ? 'selected' : ''; ?>>Estrutura Org.</option>
                                            <option value="ouvidoria.php" <?php echo $current_link_val === 'ouvidoria.php' ? 'selected' : ''; ?>>Ouvidoria</option>
                                            <option value="sic.php" <?php echo $current_link_val === 'sic.php' ? 'selected' : ''; ?>>e-Sic</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="form-check p-3 border rounded h-100 bg-light-subtle">
                                    <input class="form-check-input" type="checkbox" id="link_pagina_externa" name="link_pagina_externa" <?php echo $current_link_type === 'externo' ? 'checked' : ''; ?>>
                                    <label class="form-check-label ms-2" for="link_pagina_externa">Link Externo / URL</label>
                                    <div id="campo_link_externo" class="mt-2" style="display: <?php echo $current_link_type === 'externo' ? 'block' : 'none'; ?>;">
                                        <input type="text" class="form-control form-control-sm" name="card_link_externo" value="<?php echo $current_link_type === 'externo' ? htmlspecialchars($current_link_val) : ''; ?>" placeholder="https://...">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($secao_id && $secao_atual): ?>
                <!-- 2. DADOS DA SEÇÃO -->
                <div class="card mb-4 shadow-sm border-0" id="card_dados_secao">
                    <div class="card-header bg-white py-3 fw-bold text-success"><i class="bi bi-2-circle me-2"></i>2. Dados da Seção (Tabela de Dados)</div>
                    <div class="card-body p-4">
                        <p class="text-muted small">Configurações para a planilha e lançamentos desta seção.</p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome da Seção*</label>
                                <input type="text" class="form-control" name="secao_nome" id="secao_nome" value="<?php echo htmlspecialchars($secao_atual['nome']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Descrição Breve</label>
                                <textarea class="form-control" name="secao_descricao" rows="2"><?php echo htmlspecialchars($secao_atual['descricao']); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="d-flex gap-2 pb-5">
                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm">Salvar Alterações</button>
                    <a href="criar_secoes.php" class="btn btn-light border btn-lg px-4">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const switchConteudo = document.getElementById('link_pagina_conteudo');
    const switchSistema = document.getElementById('link_pagina_sistema');
    const switchExterno = document.getElementById('link_pagina_externa');
    
    const campoConteudo = document.getElementById('campo_pagina_conteudo');
    const campoSistema = document.getElementById('campo_pagina_sistema');
    const campoExterno = document.getElementById('campo_link_externo');
    const cardSecao = document.getElementById('card_dados_secao');
    const secaoNomeInput = document.getElementById('secao_nome');

    function updateFormState() {
        const isConteudo = switchConteudo.checked;
        const isSistema = switchSistema.checked;
        const isExterno = switchExterno.checked;

        if (isConteudo || isSistema || isExterno) {
            cardSecao.style.opacity = '0.5';
            cardSecao.querySelectorAll('input, textarea').forEach(el => el.disabled = true);
            secaoNomeInput.required = false;
        } else {
            cardSecao.style.opacity = '1';
            cardSecao.querySelectorAll('input, textarea').forEach(el => el.disabled = false);
            secaoNomeInput.required = true;
        }

        campoConteudo.style.display = isConteudo ? 'block' : 'none';
        campoSistema.style.display = isSistema ? 'block' : 'none';
        campoExterno.style.display = isExterno ? 'block' : 'none';
    }

    switchConteudo.addEventListener('change', function() { if (this.checked) { switchSistema.checked = false; switchExterno.checked = false; } updateFormState(); });
    switchSistema.addEventListener('change', function() { if (this.checked) { switchConteudo.checked = false; switchExterno.checked = false; } updateFormState(); });
    switchExterno.addEventListener('change', function() { if (this.checked) { switchConteudo.checked = false; switchSistema.checked = false; } updateFormState(); });

    updateFormState();

    // Lógica para Troca de Tipo de Ícone
    const radioImagem = document.getElementById('tipo_imagem');
    const radioBootstrap = document.getElementById('tipo_bootstrap');
    const fieldUpload = document.getElementById('campo_upload_icone');
    const fieldBootstrap = document.getElementById('campo_bootstrap_icone');
    const inputBootstrap = document.getElementById('icone_bootstrap');
    const previewBI = document.getElementById('preview_bi');

    radioImagem.addEventListener('change', () => { fieldUpload.style.display = 'block'; fieldBootstrap.style.display = 'none'; });
    radioBootstrap.addEventListener('change', () => { fieldUpload.style.display = 'none'; fieldBootstrap.style.display = 'block'; });

    inputBootstrap.addEventListener('input', (e) => {
        const val = e.target.value.trim();
        previewBI.className = 'bi ' + (val || 'bi-info-circle');
    });
});
</script>
</body>
</html>