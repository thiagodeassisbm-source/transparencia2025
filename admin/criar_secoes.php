<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Apenas admins podem acessar
if ($_SESSION['admin_user_perfil'] !== 'admin') {
    $_SESSION['mensagem_sucesso'] = "Acesso negado.";
    header("Location: dashboard.php");
    exit;
}

// Busca dados para os dropdowns da criação
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY ordem ASC")->fetchAll();
$paginas = $pdo->query("SELECT slug, titulo FROM paginas ORDER BY titulo ASC")->fetchAll();

$action = $_GET['action'] ?? 'list';
$mensagem = '';

// --- LÓGICA DE SALVAMENTO (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    $pdo->beginTransaction();
    try {
        // ... (Mesma lógica de salvamento existente) ...
        $link_externo = isset($_POST['link_pagina_externa']);
        $link_pagina_conteudo = isset($_POST['link_pagina_conteudo']);
        $link_pagina_sistema = isset($_POST['link_pagina_sistema']);

        $id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
        $card_titulo = trim($_POST['card_titulo'] ?? '');
        $card_subtitulo = trim($_POST['card_subtitulo'] ?? '');
        $card_ordem = (int)($_POST['card_ordem'] ?? 0);
        $tipo_icone = $_POST['tipo_icone'] ?? 'imagem';

        if (empty($card_titulo)) { throw new Exception("O título do card é obrigatório."); }
        
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
        } elseif ($link_pagina_conteudo) {
            $pagina_slug = $_POST['pagina_slug'] ?? '';
            $link_url = 'pagina.php?slug=' . $pagina_slug;
        } elseif ($link_pagina_sistema) {
            $link_url = $_POST['pagina_sistema'] ?? '';
        } else {
            $secao_nome = trim($_POST['secao_nome'] ?? '');
            if (empty($secao_nome)) { throw new Exception("O nome da seção é obrigatório."); }
            $secao_descricao = trim($_POST['secao_descricao'] ?? '');
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $secao_nome)));
            
            $stmt_secao = $pdo->prepare("INSERT INTO portais (nome, descricao, slug, id_categoria, id_prefeitura) VALUES (?, ?, ?, ?, ?)");
            $stmt_secao->execute([$secao_nome, $secao_descricao, $slug, $id_categoria, ($_SESSION['id_prefeitura'] ?? 0)]);
            $id_secao = $pdo->lastInsertId();
        }
        
        $stmt_card = $pdo->prepare("INSERT INTO cards_informativos (id_categoria, id_secao, link_url, titulo, subtitulo, caminho_icone, tipo_icone, ordem, id_prefeitura) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_card->execute([$id_categoria, $id_secao, $link_url, $card_titulo, $card_subtitulo, $caminho_icone, $tipo_icone, $card_ordem, ($_SESSION['id_prefeitura'] ?? 0)]);
        
        $pdo->commit();
        $_SESSION['mensagem_sucesso'] = "Nova seção/card criado com sucesso!";
        header("Location: criar_secoes.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = '<div class="alert alert-danger">Erro: ' . $e->getMessage() . '</div>';
    }
}

// --- LÓGICA DE LISTAGEM ---
$secoes_agrupadas = [];
if ($action === 'list') {
    $pref_id = $_SESSION['id_prefeitura'] ?? 0;
    $stmt = $pdo->prepare("
        SELECT p.id, p.nome, p.slug, c.nome as nome_categoria 
        FROM portais p
        LEFT JOIN categorias c ON p.id_categoria = c.id
        WHERE p.id_prefeitura = ?
        ORDER BY c.ordem, c.nome, p.nome ASC
    ");
    $stmt->execute([$pref_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $secoes_agrupadas[$r['nome_categoria'] ?? 'Sem Categoria'][] = $r;
    }
}

$page_title_for_header = ($action === 'new') ? 'Novo Card / Seção' : 'Estrutura de Seções e Cards'; 
include 'admin_header.php'; 
?>

<div class="container-fluid">
    <?php if ($action === 'list'): ?>
        <!-- MODO LISTAGEM MODERNA (INSPIRADO NOS ANEXOS) -->
        <style>
            .secao-avatar { width: 50px; height: 50px; border-radius: 50%; background: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.25rem; color: #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
            .category-card { transition: all 0.2s; border: 1px solid #f8d7da !important; background-color: #fff5f5 !important; border-radius: 12px !important; margin-bottom: 12px; }
            .category-card:hover { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(220, 53, 69, 0.1) !important; }
            .category-header { cursor: pointer; padding: 1.5rem !important; }
            .category-title { font-size: 1.1rem; font-weight: 700; color: #2d3748; margin-bottom: 0; }
            .category-subtitle { font-size: 0.85rem; color: #718096; }
            .btn-action-custom { border-radius: 8px; padding: 0.5rem 1rem; font-size: 0.8rem; font-weight: 600; transition: all 0.2s; border: none; display: inline-flex; align-items: center; gap: 6px; }
            .btn-ver { background-color: #7c3aed; color: #fff; }
            .btn-detalhes { background-color: #f1f5f9; color: #475569; }
            .btn-editar { background-color: #f8fafc; border: 1px solid #e2e8f0; color: #6366f1; }
            .btn-lancar { background-color: #ecfdf5; color: #059669; }
            .btn-excluir { background-color: #fff1f2; color: #e11d48; }
            .btn-action-custom:hover { opacity: 0.9; transform: scale(1.02); }
            .inner-section-item { background: #fff; border-radius: 10px; margin: 0 1.5rem 1.5rem 1.5rem; border: 1px solid #f1f5f9; padding: 1.25rem; }
        </style>

        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold text-dark">Lista de Seções</h3>
                    <a href="criar_secoes.php?action=new" class="btn btn-success px-4 py-2 shadow-sm rounded-pill">
                        <i class="bi bi-plus-circle me-1"></i> Criar Seção / Card
                    </a>
                </div>

                <!-- Card Informativo -->
                <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #36c0d3 0%, #2d9fb0 100%); color: #fff; border-radius: 12px;">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="me-4 d-none d-md-block">
                            <i class="bi bi-info-circle-fill" style="font-size: 3rem; opacity: 0.8;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">O que é uma Seção?</h5>
                            <p class="mb-0 opacity-90">As seções são as páginas de destino onde os lançamentos de dados acontecem. No portal público, elas aparecem como <strong>Cards Informativos</strong>. É aqui que você define qual tipo de dado será publicado e como ele será exibido para o cidadão.</p>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                    <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show"><?php echo $_SESSION['mensagem_sucesso']; unset($_SESSION['mensagem_sucesso']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <div class="accordion border-0" id="accordionCategorias">
                    <?php if (empty($secoes_agrupadas)): ?>
                        <div class="card p-5 text-center border-0 shadow-sm" style="border-radius: 15px;">
                            <div class="mb-3"><i class="bi bi-folder-x text-muted" style="font-size: 4rem;"></i></div>
                            <h5 class="text-muted">Nenhuma seção encontrada nesta prefeitura.</h5>
                            <p class="text-muted small">Clique no botão superior para criar sua primeira seção agora mesmo.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($secoes_agrupadas as $categoria => $secoes): ?>
                            <?php $id_cat = md5($categoria); ?>
                            <div class="card category-card border-0">
                                <div class="category-header d-flex justify-content-between align-items-center collapsed" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $id_cat; ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="secao-avatar me-3">
                                            <?php echo strtoupper(substr($categoria, 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h4 class="category-title"><?php echo htmlspecialchars($categoria); ?></h4>
                                            <span class="category-subtitle"><?php echo count($secoes); ?> Seção(ões) vinculada(s)</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-chevron-down opacity-50 ms-3 fs-5"></i>
                                    </div>
                                </div>
                                <div id="collapse-<?php echo $id_cat; ?>" class="collapse" data-bs-parent="#accordionCategorias">
                                    <?php foreach ($secoes as $s): ?>
                                        <div class="inner-section-item shadow-sm">
                                            <div class="d-flex flex-column">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <div class="d-flex align-items-center mb-1">
                                                            <h6 class="fw-bold mb-0 me-2" style="font-size: 1.1rem;"><?php echo htmlspecialchars($s['nome']); ?></h6>
                                                            <span class="badge bg-light text-success border border-success border-opacity-10 py-1">Ativo</span>
                                                        </div>
                                                        <small class="text-muted d-block mb-1">ID do Portal: #<?php echo str_pad($s['id'], 6, '0', STR_PAD_LEFT); ?></small>
                                                        
                                                        <a href="../portal.php?slug=<?php echo $s['slug']; ?>" target="_blank" class="text-info text-decoration-none small fw-bold">
                                                            <i class="bi bi-box-arrow-up-right me-1"></i> Ver Link Público
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="border-top pt-3 mt-1">
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <a href="ver_lancamentos.php?portal_id=<?php echo $s['id']; ?>" class="btn-action-custom btn-ver">
                                                            <i class="bi bi-file-earmark-bar-graph"></i> Planilha de Dados
                                                        </a>
                                                        <a href="lancar_dados.php?portal_id=<?php echo $s['id']; ?>" class="btn-action-custom btn-lancar">
                                                            <i class="bi bi-plus-square"></i> Novo Lançamento
                                                        </a>
                                                        <a href="gerenciar_campos.php?portal_id=<?php echo $s['id']; ?>" class="btn-action-custom btn-detalhes">
                                                            <i class="bi bi-pencil-square"></i> Gerenciar Campos
                                                        </a>
                                                        <a href="editar_secao.php?id=<?php echo $s['id']; ?>" class="btn-action-custom btn-editar">
                                                            <i class="bi bi-pencil"></i> Editar Nome
                                                        </a>
                                                        <form method="POST" action="excluir_secao.php" class="d-inline" onsubmit="return confirm('ATENÇÃO: Deseja realmente excluir esta seção?');">
                                                            <input type="hidden" name="portal_id" value="<?php echo $s['id']; ?>">
                                                            <button type="submit" class="btn-action-custom btn-excluir">
                                                                <i class="bi bi-trash"></i> Excluir
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php elseif ($action === 'new'): ?>
        <!-- MODO FORMULÁRIO (ANEXO) -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex align-items-center mb-4">
                    <a href="criar_secoes.php" class="btn btn-light border-0 shadow-sm me-3"><i class="bi bi-arrow-left"></i> Voltar</a>
                    <h4 class="mb-0">Cadastrar Novo Card / Seção</h4>
                </div>

                <?php echo $mensagem; ?>

                <form method="POST" action="criar_secoes.php?action=save" enctype="multipart/form-data">
                    <div class="card mb-4 shadow-sm border-0">
                        <div class="card-header bg-white py-3 fw-bold"><i class="bi bi-1-circle me-2 text-primary"></i>1. Dados do Card (Atalho na Página Inicial)</div>
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Título do Card*</label><input type="text" class="form-control" name="card_titulo" required placeholder="Ex: Decretos"></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Subtítulo do Card*</label><input type="text" class="form-control" name="card_subtitulo" required placeholder="Ex: Atos Normativos"></div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Categoria*</label>
                                    <select class="form-select" name="id_categoria" required>
                                        <option value="">-- Selecione --</option>
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nome']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3"><label class="form-label">Ordem de Exibição</label><input type="number" class="form-control" name="card_ordem" value="0"></div>
                                
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold d-block mb-2">Tipo de Ícone</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="tipo_icone" id="tipo_imagem" value="imagem" checked>
                                        <label class="btn btn-outline-primary" for="tipo_imagem"><i class="bi bi-image me-2"></i>Imagem (Upload)</label>
                                        
                                        <input type="radio" class="btn-check" name="tipo_icone" id="tipo_bootstrap" value="bootstrap">
                                        <label class="btn btn-outline-primary" for="tipo_bootstrap"><i class="bi bi-bootstrap me-2"></i>Ícone do Sistema</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3" id="campo_upload_icone">
                                    <label class="form-label">Selecionar Arquivo (PNG/SVG)*</label>
                                    <input type="file" class="form-control" name="card_icone" accept="image/*">
                                </div>
                                <div class="col-md-6 mb-3" id="campo_bootstrap_icone" style="display: none;">
                                    <label class="form-label">Classe do Ícone (Ex: bi-star)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-info-circle text-primary" id="preview_bi"></i></span>
                                        <input type="text" class="form-control" id="icone_bootstrap" name="icone_bootstrap" placeholder="bi-gear">
                                    </div>
                                    <small class="text-muted"><a href="https://icons.getbootstrap.com/" target="_blank">Abrir Catálogo</a></small>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <h6 class="fw-bold mb-3">Onde este card deve levar o usuário?</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-check p-3 border rounded h-100 bg-light-subtle">
                                        <input class="form-check-input" type="checkbox" id="link_pagina_conteudo" name="link_pagina_conteudo">
                                        <label class="form-check-label ms-2" for="link_pagina_conteudo">Página de Conteúdo (Texto/Imagens)</label>
                                        <div id="campo_pagina_conteudo" class="mt-2" style="display: none;">
                                            <select class="form-select form-select-sm" name="pagina_slug">
                                                <option value="">-- Selecionar --</option>
                                                <?php foreach($paginas as $p): ?>
                                                    <option value="<?php echo $p['slug']; ?>"><?php echo htmlspecialchars($p['titulo']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check p-3 border rounded h-100 bg-light-subtle">
                                        <input class="form-check-input" type="checkbox" id="link_pagina_sistema" name="link_pagina_sistema">
                                        <label class="form-check-label ms-2" for="link_pagina_sistema">Página Pronta do Sistema</label>
                                        <div id="campo_pagina_sistema" class="mt-2" style="display: none;">
                                            <select class="form-select form-select-sm" name="pagina_sistema">
                                                <option value="">-- Selecionar --</option>
                                                <option value="estrutura.php">Estrutura Org.</option>
                                                <option value="ouvidoria.php">Ouvidoria</option>
                                                <option value="sic.php">e-Sic</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check p-3 border rounded h-100 bg-light-subtle">
                                        <input class="form-check-input" type="checkbox" id="link_pagina_externa" name="link_pagina_externa">
                                        <label class="form-check-label ms-2" for="link_pagina_externa">Link Externo / URL</label>
                                        <div id="campo_link_externo" class="mt-2" style="display: none;">
                                            <input type="text" class="form-control form-control-sm" name="card_link_externo" placeholder="https://...">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4 shadow-sm border-0" id="card_dados_secao">
                        <div class="card-header bg-white py-3 fw-bold text-success"><i class="bi bi-2-circle me-2"></i>2. Dados da Seção (Tabela de Dados)</div>
                        <div class="card-body p-4">
                            <p class="text-muted small">Só preencha se NÃO selecionou nenhuma das opções acima. Isso criará uma nova tabela para lançamentos.</p>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label">Nome da Seção*</label><input type="text" class="form-control" name="secao_nome" id="secao_nome"></div>
                                <div class="col-md-6 mb-3"><label class="form-label">Descrição Breve</label><textarea class="form-control" name="secao_descricao" rows="2"></textarea></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 pb-5">
                        <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm">Criar Card / Seção</button>
                        <a href="criar_secoes.php" class="btn btn-light border btn-lg px-4">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

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
    <?php endif; ?>
</div>

<?php include 'admin_footer.php'; ?>
</html>
</body>
</html>