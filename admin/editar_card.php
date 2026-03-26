<?php
require_once 'auth_check.php';
require_once '../conexao.php';

if ($_SESSION['admin_user_perfil'] !== 'admin') { header("Location: index.php"); exit; }

$card_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$card_id) { header("Location: gerenciar_cards.php"); exit; }

// Busca dados para os dropdowns
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC")->fetchAll();
$secoes = $pdo->query("SELECT id, nome FROM portais ORDER BY nome ASC")->fetchAll();
$paginas = $pdo->query("SELECT slug, titulo FROM paginas ORDER BY titulo ASC")->fetchAll();

// Processa o formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        $link_externo = isset($_POST['link_pagina_externa']);
        $link_pagina_conteudo = isset($_POST['link_pagina_conteudo']);
        $link_pagina_sistema = isset($_POST['link_pagina_sistema']);

        $id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
        $titulo = trim($_POST['titulo']);
        $subtitulo = trim($_POST['subtitulo']);
        $ordem = (int)$_POST['ordem'];
        $caminho_icone_antigo = $_POST['caminho_icone_antigo'];
        $caminho_icone_final = $caminho_icone_antigo;

        if (empty($id_categoria)) { $id_categoria = null; }

        $id_secao = null;
        $link_url = null;

        if ($link_externo) {
            $link_url = trim($_POST['link_url'] ?? '');
            if (!empty($link_url) && !filter_var($link_url, FILTER_VALIDATE_URL)) { throw new Exception("O link externo fornecido não é uma URL válida."); }
        } elseif ($link_pagina_conteudo) {
            $pagina_slug = $_POST['pagina_slug'] ?? '';
            if (empty($pagina_slug)) { throw new Exception("Você precisa selecionar uma página de conteúdo."); }
            $link_url = 'pagina.php?slug=' . $pagina_slug;
        } elseif ($link_pagina_sistema) {
            $pagina_sistema = $_POST['pagina_sistema'] ?? '';
            if (empty($pagina_sistema)) { throw new Exception("Você precisa selecionar uma página do sistema."); }
            $link_url = $pagina_sistema;
        } else {
            $id_secao = filter_input(INPUT_POST, 'id_secao', FILTER_VALIDATE_INT);
            if (empty($id_secao)) { $id_secao = null; }
        }

        if (isset($_FILES['icone_upload']) && $_FILES['icone_upload']['error'] === UPLOAD_ERR_OK) {
            if (!empty($caminho_icone_antigo) && file_exists($caminho_icone_antigo)) { unlink($caminho_icone_antigo); }
            $upload_dir = '../uploads/';
            $nome_arquivo = 'card-' . uniqid() . '-' . basename($_FILES['icone_upload']['name']);
            $caminho_destino = $upload_dir . $nome_arquivo;
            move_uploaded_file($_FILES['icone_upload']['tmp_name'], $caminho_destino);
            $caminho_icone_final = $caminho_destino;
        }
        
        $stmt = $pdo->prepare("UPDATE cards_informativos SET id_categoria = ?, id_secao = ?, link_url = ?, titulo = ?, subtitulo = ?, caminho_icone = ?, ordem = ? WHERE id = ?");
        $stmt->execute([$id_categoria, $id_secao, $link_url, $titulo, $subtitulo, $caminho_icone_final, $ordem, $card_id]);
        
        $pdo->commit();
        $_SESSION['mensagem_sucesso'] = "Card atualizado com sucesso!";
        header("Location: gerenciar_cards.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['mensagem_sucesso'] = "Erro ao atualizar: " . $e->getMessage();
        header("Location: editar_card.php?id=" . $card_id);
        exit;
    }
}

// Busca os dados atuais do card para preencher o formulário
$stmt = $pdo->prepare("SELECT * FROM cards_informativos WHERE id = ?");
$stmt->execute([$card_id]);
$card = $stmt->fetch();
if (!$card) { header("Location: gerenciar_cards.php"); exit; }

// Lógica para determinar o estado inicial dos switchers
$is_pagina_conteudo = !empty($card['link_url']) && strpos($card['link_url'], 'pagina.php?slug=') === 0;
// CORREÇÃO: Adicionado 'faq.php' à lista de páginas do sistema
$is_pagina_sistema = !empty($card['link_url']) && in_array($card['link_url'], ['estrutura.php', 'ouvidoria.php', 'relatorio_publicacoes.php', 'faq.php']);
$is_link_externo = !empty($card['link_url']) && !$is_pagina_conteudo && !$is_pagina_sistema;
$slug_pagina_atual = $is_pagina_conteudo ? str_replace('pagina.php?slug=', '', $card['link_url']) : '';
$pagina_sistema_atual = $is_pagina_sistema ? $card['link_url'] : '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Card - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">
<?php 
$page_title_for_header = 'Editar Card'; 
include 'admin_header.php'; 
?>
<div class="container-fluid container-custom-padding">
    <div class="row"><div class="col-12">
    <div class="card">
        <div class="card-header"><h4>Formulário de Edição de Card</h4></div>
        <div class="card-body">
            <form method="POST" action="editar_card.php?id=<?php echo $card_id; ?>" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3"><label for="titulo" class="form-label">Título Principal</label><input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($card['titulo']); ?>" required></div>
                    <div class="col-md-6 mb-3"><label for="subtitulo" class="form-label">Subtítulo</label><input type="text" class="form-control" id="subtitulo" name="subtitulo" value="<?php echo htmlspecialchars($card['subtitulo']); ?>" required></div>
                    <div class="col-md-6 mb-3"><label for="id_categoria" class="form-label">Categoria do Card</label><select class="form-select" id="id_categoria" name="id_categoria"><option value="">-- Sem Categoria --</option><?php foreach ($categorias as $categoria): ?><option value="<?php echo $categoria['id']; ?>" <?php echo ($categoria['id'] == $card['id_categoria']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($categoria['nome']); ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6 mb-3"><label for="ordem" class="form-label">Ordem de Exibição</label><input type="number" class="form-control" id="ordem" name="ordem" value="<?php echo htmlspecialchars($card['ordem']); ?>"></div>
                    <div class="col-md-6 mb-3"><label for="card_icone" class="form-label">Enviar Novo Ícone (Opcional)</label><input type="file" class="form-control" id="card_icone" name="icone_upload" accept="image/*"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Ícone Atual</label><br><img src="<?php echo htmlspecialchars($card['caminho_icone']); ?>" alt="Ícone atual" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: contain;"></div>
                </div>
                <hr>
                <p class="fw-bold">Tipo de Link do Card:</p>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="link_pagina_conteudo" name="link_pagina_conteudo" <?php if($is_pagina_conteudo) echo 'checked'; ?>>
                    <label class="form-check-label" for="link_pagina_conteudo">Direcionar para uma Página de Conteúdo (criada no editor)</label>
                </div>
                <div id="campo_pagina_conteudo" class="mb-3">
                    <label for="pagina_slug" class="form-label">Selecione a Página</label>
                    <select class="form-select" id="pagina_slug" name="pagina_slug">
                        <option value="">-- Selecione uma página --</option>
                        <?php foreach($paginas as $pagina): ?>
                            <option value="<?php echo htmlspecialchars($pagina['slug']); ?>" <?php echo ($pagina['slug'] == $slug_pagina_atual) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pagina['titulo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="link_pagina_sistema" name="link_pagina_sistema" <?php if($is_pagina_sistema) echo 'checked'; ?>>
                    <label class="form-check-label" for="link_pagina_sistema">Direcionar para uma Página do Sistema</label>
                </div>
                <div id="campo_pagina_sistema" class="mb-3">
                    <label for="pagina_sistema" class="form-label">Selecione a Página do Sistema</label>
                    <select class="form-select" id="pagina_sistema" name="pagina_sistema">
                        <option value="">-- Selecione --</option>
                        <option value="estrutura.php" <?php if($pagina_sistema_atual == 'estrutura.php') echo 'selected'; ?>>Estrutura Organizacional</option>
                        <option value="ouvidoria.php" <?php if($pagina_sistema_atual == 'ouvidoria.php') echo 'selected'; ?>>Ouvidoria</option>
                        <option value="faq.php" <?php if($pagina_sistema_atual == 'faq.php') echo 'selected'; ?>>Perguntas Frequentes (FAQ)</option>
                    </select>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="link_pagina_externa" name="link_pagina_externa" <?php if($is_link_externo) echo 'checked'; ?>>
                    <label class="form-check-label" for="link_pagina_externa">Direcionar para um Link Externo</label>
                </div>
                <div id="campo_link_externo" class="mb-3">
                    <label for="link_url" class="form-label">URL Externa*</label>
                    <input type="url" class="form-control" id="link_url" name="link_url" value="<?php echo htmlspecialchars($card['link_url']); ?>" placeholder="https://www.exemplo.com">
                </div>
                <div id="campo_secao_interna" class="mb-3">
                    <label for="id_secao" class="form-label">Seção de Destino (com Tabela de Dados)</label>
                    <select class="form-select" id="id_secao" name="id_secao">
                        <option value="">-- Nenhuma (Padrão) --</option>
                        <?php foreach($secoes as $secao): ?>
                            <option value="<?php echo $secao['id']; ?>" <?php echo ($secao['id'] == $card['id_secao']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($secao['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="caminho_icone_antigo" value="<?php echo htmlspecialchars($card['caminho_icone']); ?>">
                <div class="mt-4"><button type="submit" class="btn btn-primary">Salvar Alterações</button><a href="gerenciar_cards.php" class="btn btn-secondary">Cancelar</a></div>
            </form>
        </div>
    </div>
</div></div></div>
<?php include 'admin_footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Seu código JavaScript original para controlar os switchers
    const switchConteudo = document.getElementById('link_pagina_conteudo');
    const switchSistema = document.getElementById('link_pagina_sistema');
    const switchExterno = document.getElementById('link_pagina_externa');
    
    const campoConteudo = document.getElementById('campo_pagina_conteudo');
    const campoSistema = document.getElementById('campo_pagina_sistema');
    const campoExterno = document.getElementById('campo_link_externo');
    const campoSecao = document.getElementById('campo_secao_interna');

    function updateFormState() {
        if (switchConteudo.checked) {
            switchSistema.checked = false; switchExterno.checked = false;
            campoConteudo.style.display = 'block'; campoConteudo.querySelector('select').required = true;
            campoSistema.style.display = 'none'; campoSistema.querySelector('select').required = false;
            campoExterno.style.display = 'none'; campoExterno.querySelector('input').required = false;
            campoSecao.style.opacity = '0.5'; campoSecao.querySelector('select').disabled = true;
        } else if (switchSistema.checked) {
            switchConteudo.checked = false; switchExterno.checked = false;
            campoConteudo.style.display = 'none'; campoConteudo.querySelector('select').required = false;
            campoSistema.style.display = 'block'; campoSistema.querySelector('select').required = true;
            campoExterno.style.display = 'none'; campoExterno.querySelector('input').required = false;
            campoSecao.style.opacity = '0.5'; campoSecao.querySelector('select').disabled = true;
        } else if (switchExterno.checked) {
            switchConteudo.checked = false; switchSistema.checked = false;
            campoConteudo.style.display = 'none'; campoConteudo.querySelector('select').required = false;
            campoSistema.style.display = 'none'; campoSistema.querySelector('select').required = false;
            campoExterno.style.display = 'block'; campoExterno.querySelector('input').required = true;
            campoSecao.style.opacity = '0.5'; campoSecao.querySelector('select').disabled = true;
        } else { // Padrão (Seção)
            campoConteudo.style.display = 'none'; campoConteudo.querySelector('select').required = false;
            campoSistema.style.display = 'none'; campoSistema.querySelector('select').required = false;
            campoExterno.style.display = 'none'; campoExterno.querySelector('input').required = false;
            campoSecao.style.opacity = '1'; campoSecao.querySelector('select').disabled = false;
        }
    }

    switchConteudo.addEventListener('change', updateFormState);
    switchSistema.addEventListener('change', updateFormState);
    switchExterno.addEventListener('change', updateFormState);

    updateFormState(); // Inicializa o formulário no estado correto
});
</script>
</body>
</html>