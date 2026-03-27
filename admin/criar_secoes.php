<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Apenas admins podem acessar
if ($_SESSION['admin_user_perfil'] !== 'admin') {
    $_SESSION['mensagem_sucesso'] = "Acesso negado.";
    header("Location: dashboard.php");
    exit;
}

// Busca dados para os dropdowns
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY ordem ASC")->fetchAll();
$paginas = $pdo->query("SELECT slug, titulo FROM paginas ORDER BY titulo ASC")->fetchAll();

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        // Verifica qual modo foi escolhido
        $link_externo = isset($_POST['link_pagina_externa']);
        $link_pagina_conteudo = isset($_POST['link_pagina_conteudo']);
        $link_pagina_sistema = isset($_POST['link_pagina_sistema']);

        // --- Dados do Card (comuns a todos) ---
        $id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
        $card_titulo = trim($_POST['card_titulo'] ?? '');
        $card_subtitulo = trim($_POST['card_subtitulo'] ?? '');
        $card_ordem = (int)($_POST['card_ordem'] ?? 0);
        $tipo_icone = $_POST['tipo_icone'] ?? 'imagem';

        if (empty($card_titulo)) {
            throw new Exception("O título do card é obrigatório.");
        }
        
        $caminho_icone = '';
        if ($tipo_icone === 'imagem') {
            if (!isset($_FILES['card_icone']) || $_FILES['card_icone']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Para o tipo 'Imagem', o upload do arquivo é obrigatório.");
            }
            $upload_dir = '../uploads/';
            $nome_arquivo = 'card-' . uniqid() . '-' . basename($_FILES['card_icone']['name']);
            $caminho_destino = $upload_dir . $nome_arquivo;
            if (!move_uploaded_file($_FILES['card_icone']['tmp_name'], $caminho_destino)) {
                throw new Exception("Falha ao fazer upload do ícone do card.");
            }
            $caminho_icone = $caminho_destino;
        } else {
            $caminho_icone = trim($_POST['icone_bootstrap'] ?? 'bi-info-circle');
        }

        $id_secao = null;
        $link_url = null;

        if ($link_externo) {
            $link_url = trim($_POST['card_link_externo'] ?? '');
            if (!filter_var($link_url, FILTER_VALIDATE_URL)) { throw new Exception("O link externo fornecido não é uma URL válida."); }
        } elseif ($link_pagina_conteudo) {
            $pagina_slug = $_POST['pagina_slug'] ?? '';
            if (empty($pagina_slug)) { throw new Exception("Você precisa selecionar uma página de conteúdo."); }
            $link_url = 'pagina.php?slug=' . $pagina_slug;
        } elseif ($link_pagina_sistema) {
            $pagina_sistema = $_POST['pagina_sistema'] ?? '';
            if (empty($pagina_sistema)) { throw new Exception("Você precisa selecionar uma página do sistema."); }
            $link_url = $pagina_sistema; // Salva o nome do arquivo diretamente (ex: 'estrutura.php')
        } else {
            // Modo padrão: Cria uma nova seção
            $secao_nome = trim($_POST['secao_nome'] ?? '');
            if (empty($secao_nome)) { throw new Exception("O nome da seção é obrigatório para este tipo de card."); }
            $secao_descricao = trim($_POST['secao_descricao'] ?? '');
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $secao_nome)));
            
            $stmt_secao = $pdo->prepare("INSERT INTO portais (nome, descricao, slug, id_categoria) VALUES (?, ?, ?, ?)");
            $stmt_secao->execute([$secao_nome, $secao_descricao, $slug, $id_categoria]);
            $id_secao = $pdo->lastInsertId();
        }
        
        $stmt_card = $pdo->prepare("INSERT INTO cards_informativos (id_categoria, id_secao, link_url, titulo, subtitulo, caminho_icone, tipo_icone, ordem) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_card->execute([$id_categoria, $id_secao, $link_url, $card_titulo, $card_subtitulo, $caminho_icone, $tipo_icone, $card_ordem]);
        
        $pdo->commit();
        $_SESSION['mensagem_sucesso'] = "Card criado com sucesso!";
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = '<div class="alert alert-danger">Erro: ' . $e->getMessage() . '</div>';
    }
}

$page_title_for_header = 'Criar Nova Seção/Card'; 
include 'admin_header.php'; 
?>


<div class="container-fluid container-custom-padding">
    <div class="row"><div class="col-12">
        <?php echo $mensagem; ?>
        <form method="POST" action="criar_secoes.php" enctype="multipart/form-data">
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-white py-3">1. Dados do Card (Atalho na Página Inicial)</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="card_titulo" class="form-label">Título do Card*</label><input type="text" class="form-control" id="card_titulo" name="card_titulo" required></div>
                        <div class="col-md-6 mb-3"><label for="card_subtitulo" class="form-label">Subtítulo do Card*</label><input type="text" class="form-control" id="card_subtitulo" name="card_subtitulo" required></div>
                        <div class="col-md-6 mb-3"><label for="id_categoria" class="form-label">Categoria*</label><select class="form-select" id="id_categoria" name="id_categoria" required><option value="">-- Selecione uma Categoria --</option><?php foreach ($categorias as $categoria): ?><option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6 mb-3"><label for="card_ordem" class="form-label">Ordem de Exibição</label><input type="number" class="form-control" id="card_ordem" name="card_ordem" value="0"></div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Tipo de Ícone</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_icone" id="tipo_imagem" value="imagem" checked>
                                    <label class="form-check-label" for="tipo_imagem">Imagem (Upload)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_icone" id="tipo_bootstrap" value="bootstrap">
                                    <label class="form-check-label" for="tipo_bootstrap">Ícone do Sistema (Bootstrap)</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3" id="campo_upload_icone">
                            <label for="card_icone" class="form-label">Arquivo de Ícone*</label>
                            <input type="file" class="form-control" id="card_icone" name="card_icone" accept="image/*">
                        </div>
                        <div class="col-md-6 mb-3" id="campo_bootstrap_icone" style="display: none;">
                            <label for="icone_bootstrap" class="form-label">Classe do Ícone Bootstrap (ex: bi-star)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-info-circle" id="preview_bi"></i></span>
                                <input type="text" class="form-control" id="icone_bootstrap" name="icone_bootstrap" placeholder="bi-gear">
                            </div>
                            <small class="text-muted"><a href="https://icons.getbootstrap.com/" target="_blank">Ver catálogo de ícones</a></small>
                        </div>
                    </div>
                    </div>
                    <hr>
                    <p class="fw-bold">Tipo de Link do Card:</p>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="link_pagina_conteudo" name="link_pagina_conteudo">
                        <label class="form-check-label" for="link_pagina_conteudo">Direcionar para uma Página de Conteúdo (criada no editor)</label>
                    </div>
                    <div id="campo_pagina_conteudo" class="mb-3" style="display: none;">
                        <label for="pagina_slug" class="form-label">Selecione a Página</label>
                        <select class="form-select" id="pagina_slug" name="pagina_slug">
                            <option value="">-- Selecione uma página --</option>
                            <?php foreach($paginas as $pagina): ?>
                                <option value="<?php echo htmlspecialchars($pagina['slug']); ?>"><?php echo htmlspecialchars($pagina['titulo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="link_pagina_sistema" name="link_pagina_sistema">
                        <label class="form-check-label" for="link_pagina_sistema">Direcionar para uma Página do Sistema (ex: Estrutura, Ouvidoria)</label>
                    </div>
                    <div id="campo_pagina_sistema" class="mb-3" style="display: none;">
                        <label for="pagina_sistema" class="form-label">Selecione a Página do Sistema</label>
                        <select class="form-select" id="pagina_sistema" name="pagina_sistema">
                            <option value="">-- Selecione --</option>
                            <option value="estrutura.php">Estrutura Organizacional</option>
                            <option value="ouvidoria.php">Ouvidoria</option>
                             <option value="sic.php">e-Sic</option>
                            <option value="faq.php">Perguntas Frequentes (FAQ)</option>
                            <option value="relatorio_publicacoes.php">Relatório de Publicações</option>
                        </select>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="link_pagina_externa" name="link_pagina_externa">
                        <label class="form-check-label" for="link_pagina_externa">Direcionar para um Link Externo?</label>
                    </div>
                    <div id="campo_link_externo" class="mb-3" style="display: none;">
                        <label for="card_link_externo" class="form-label">URL Externa*</label>
                        <input type="text" class="form-control" id="card_link_externo" name="card_link_externo" placeholder="https://www.exemplo.com">
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0" id="card_dados_secao">
                <div class="card-header bg-white py-3">2. Dados da Seção (Página de Destino com Tabela de Dados)</div>
                <div class="card-body">
                    <p class="text-muted">Preencha os campos abaixo apenas se o card precisar levar a uma nova seção com tabela de dados (opção padrão).</p>
                    <div class="row">
                        <div class="col-md-6 mb-3" id="group_secao_nome"><label for="secao_nome" class="form-label">Nome da Seção*</label><input type="text" class="form-control" id="secao_nome" name="secao_nome"></div>
                        <div class="col-md-6 mb-3" id="group_secao_descricao"><label for="secao_descricao" class="form-label">Descrição da Seção</label><textarea class="form-control" id="secao_descricao" name="secao_descricao" rows="2"></textarea></div>
                    </div>
                </div>
            </div>
            <div class="mt-4 pb-5"><button type="submit" class="btn btn-primary btn-lg px-5">Salvar</button><a href="index.php" class="btn btn-light btn-lg border ms-2 px-4">Cancelar</a></div>
        </form>
    </div></div>
</div>

<?php include 'admin_footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const switchConteudo = document.getElementById('link_pagina_conteudo');
    const switchSistema = document.getElementById('link_pagina_sistema');
    const switchExterno = document.getElementById('link_pagina_externa');
    
    const campoConteudo = document.getElementById('campo_pagina_conteudo');
    const campoSistema = document.getElementById('campo_pagina_sistema');
    const campoExterno = document.getElementById('campo_link_externo');
    const cardSecao = document.getElementById('card_dados_secao');

    function updateFormState() {
        const isConteudo = switchConteudo.checked;
        const isSistema = switchSistema.checked;
        const isExterno = switchExterno.checked;

        if (isConteudo || isSistema || isExterno) {
            cardSecao.style.opacity = '0.5';
            cardSecao.querySelectorAll('input, textarea').forEach(el => el.disabled = true);
        } else {
            cardSecao.style.opacity = '1';
            cardSecao.querySelectorAll('input, textarea').forEach(el => el.disabled = false);
        }

        campoConteudo.style.display = isConteudo ? 'block' : 'none';
        campoConteudo.querySelector('select').required = isConteudo;
        
        campoSistema.style.display = isSistema ? 'block' : 'none';
        campoSistema.querySelector('select').required = isSistema;

        campoExterno.style.display = isExterno ? 'block' : 'none';
        campoExterno.querySelector('input').required = isExterno;
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
    const inputUpload = document.getElementById('card_icone');
    const previewBI = document.getElementById('preview_bi');

    radioImagem.addEventListener('change', () => {
        fieldUpload.style.display = 'block';
        fieldBootstrap.style.display = 'none';
        inputUpload.required = true;
        inputBootstrap.required = false;
    });

    radioBootstrap.addEventListener('change', () => {
        fieldUpload.style.display = 'none';
        fieldBootstrap.style.display = 'block';
        inputUpload.required = false;
        inputBootstrap.required = true;
    });

    inputBootstrap.addEventListener('input', (e) => {
        const val = e.target.value.trim();
        previewBI.className = 'bi ' + (val || 'bi-info-circle');
    });
});
</script>
</body>
</html>