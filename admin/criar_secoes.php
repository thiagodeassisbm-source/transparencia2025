<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';
require_once __DIR__ . '/includes/seed_informacoes_institucionais.php';

// Apenas admins podem acessar
if ($_SESSION['admin_user_perfil'] !== 'admin') {
    $_SESSION['mensagem_sucesso'] = "Acesso negado.";
    header("Location: dashboard.php");
    exit;
}

// Busca dados para os dropdowns da criação (escopo da prefeitura)
$pref_id_sess = (int) ($_SESSION['id_prefeitura'] ?? 0);
$stmt_cat_dd = $pdo->prepare('SELECT id, nome FROM categorias WHERE id_prefeitura = ? ORDER BY ordem ASC');
$stmt_cat_dd->execute([$pref_id_sess]);
$categorias = $stmt_cat_dd->fetchAll();
$paginas = $pdo->query('SELECT slug, titulo FROM paginas ORDER BY titulo ASC')->fetchAll();

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

            // Atribui automaticamente permissões totais para o perfil de quem está criando a seção.
            // Para não precisar ir gerenciar perfis logo de cara.
            $perfil_id_atual = $_SESSION['admin_user_id_perfil'] ?? 0;
            if ($perfil_id_atual > 0) {
                $stmt_perm = $pdo->prepare("INSERT INTO permissoes_perfil (id_perfil, recurso, p_ver, p_lancar, p_editar, p_excluir) VALUES (?, ?, 1, 1, 1, 1)");
                $stmt_perm->execute([$perfil_id_atual, 'form_' . $id_secao]);
                
                // Limpa o cache de sessão para refletir a mudança imediatamente
                unset($_SESSION['permissoes_sessao']);
            }
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

// --- LÓGICA DE LISTAGEM (mesma base que index.php: todas as seções/portais da prefeitura) ---
$secoes_agrupadas = [];
if ($action === 'list') {
    $pref_id = (int) ($_SESSION['id_prefeitura'] ?? 0);
    $perfil_id_sess = (int) ($_SESSION['admin_user_id_perfil'] ?? 0);

    if (ensure_informacoes_institucionais($pdo, $pref_id, $perfil_id_sess)) {
        unset($_SESSION['permissoes_sessao']);
    }

    // Recupera o slug da prefeitura para gerar os links do portal
    $stmt_slug_pref = $pdo->prepare('SELECT slug FROM prefeituras WHERE id = ?');
    $stmt_slug_pref->execute([$pref_id]);
    $pref_slug_contexto = $stmt_slug_pref->fetchColumn() ?: 'principal';

    $stmt = $pdo->prepare(
        'SELECT 
            ci.id as card_id, ci.titulo, ci.subtitulo, ci.caminho_icone, ci.tipo_icone, ci.link_url, ci.id_secao,
            p.id as portal_id, p.nome as portal_nome, p.slug as portal_slug,
            cat.nome as nome_categoria,
            (SELECT COUNT(*) FROM registros r WHERE r.id_portal = p.id) as total_registros,
            (SELECT MIN(exercicio) FROM registros r WHERE r.id_portal = p.id) as ano_min,
            (SELECT MAX(exercicio) FROM registros r WHERE r.id_portal = p.id) as ano_max,
            (SELECT COUNT(*) FROM valores_registros vr 
             JOIN campos_portal cp ON vr.id_campo = cp.id 
             WHERE cp.id_portal = p.id AND cp.tipo_campo = \'anexo\' AND vr.valor != \'\') as total_pdfs
        FROM portais p
        LEFT JOIN categorias cat ON p.id_categoria = cat.id
        LEFT JOIN (
            SELECT c1.* FROM cards_informativos c1
            INNER JOIN (
                SELECT id_secao, MIN(id) AS mid FROM cards_informativos GROUP BY id_secao
            ) pick ON c1.id = pick.mid
        ) ci ON ci.id_secao = p.id
        WHERE p.id_prefeitura = ?
        ORDER BY cat.ordem ASC, cat.nome ASC, p.ordem ASC, p.nome ASC'
    );
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
                                         <?php 
                                             $is_portal = !empty($s['portal_id']);
                                             $display_name = $s['portal_nome'] ?: ($s['titulo'] ?? '');
                                             $display_id = $s['portal_id'] ? str_pad((string) $s['portal_id'], 6, '0', STR_PAD_LEFT) : 'Card Link';
                                             if ($is_portal && !empty($s['portal_id'])) {
                                                 if (!empty($s['card_id'])) {
                                                     $edit_url = 'editar_secao.php?card_id=' . (int) $s['card_id'] . '&id=' . (int) $s['portal_id'];
                                                 } else {
                                                     $edit_url = 'editar_secao.php?id=' . (int) $s['portal_id'];
                                                 }
                                             } else {
                                                 $edit_url = 'editar_secao.php?card_id=' . (int) $s['card_id'];
                                             }
                                             $public_link = $is_portal && !empty($s['portal_slug']) ? '../portal/' . $pref_slug_contexto . '/' . $s['portal_slug'] : ($s['link_url'] ?? '#');
                                         ?>
                                         <div class="inner-section-item shadow-sm">
                                             <div class="d-flex flex-column">
                                                 <div class="d-flex justify-content-between align-items-start mb-3">
                                                     <div>
                                                         <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                             <h6 class="fw-bold mb-0 me-1" style="font-size: 1.15rem; color: #2d3748;"><?php echo htmlspecialchars($display_name); ?></h6>
                                                             <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" style="font-size: 0.7rem;">ATIVO</span>
                                                             
                                                             <?php if ($is_portal): ?>
                                                                 <?php if ($s['total_pdfs'] > 0): ?>
                                                                     <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-1 px-2" style="font-size: 0.7rem;">
                                                                         <i class="bi bi-file-earmark-pdf-fill me-1"></i> <?php echo $s['total_pdfs']; ?> PDF
                                                                     </span>
                                                                 <?php endif; ?>
                                                                 
                                                                 <span class="badge bg-info-subtle text-dark border border-info-subtle py-1 px-2" style="font-size: 0.7rem;">
                                                                     <i class="bi bi-calendar3 me-1"></i> <?php echo $s['ano_min'] ? ($s['ano_min'] == $s['ano_max'] ? $s['ano_min'] : $s['ano_min'].' - '.$s['ano_max']) : 'SEM DADOS'; ?>
                                                                 </span>
                                                             <?php else: ?>
                                                                 <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2" style="font-size: 0.7rem;">
                                                                     <i class="bi bi-link-45deg"></i> LINK DIRETO
                                                                 </span>
                                                             <?php endif; ?>
                                                         </div>
                                                         <small class="text-muted d-block" style="font-size: 0.85rem; opacity: 0.7;">
                                                             <?php echo $is_portal ? "Portal ID: #$display_id" : "Tipo: Redirecionamento"; ?>
                                                         </small>
                                                     </div>
                                                     
                                                     <?php if ($is_portal): ?>
                                                     <div class="text-center d-none d-sm-block ms-3" style="min-width: 140px;">
                                                         <div class="text-muted small fw-bold text-uppercase mb-0" style="font-size: 0.65rem; letter-spacing: 0.8px; opacity: 0.8;">Total Lançamentos</div>
                                                         <div class="fw-bold text-success" style="font-size: 1.8rem; line-height: 1.1;">
                                                             <?php echo number_format($s['total_registros'] ?: 0, 0, ',', '.'); ?>
                                                         </div>
                                                     </div>
                                                     <?php endif; ?>
                                                 </div>
 
                                                 <div class="border-top pt-3 mt-1">
                                                     <div class="d-flex flex-wrap gap-2">
                                                         <?php if ($is_portal): ?>
                                                             <a href="gerenciar_campos.php?portal_id=<?php echo $s['portal_id']; ?>" class="btn-action-custom btn-detalhes">
                                                                 <i class="bi bi-pencil-square"></i> Gerenciar Campos
                                                             </a>
                                                             <a href="lancar_dados.php?portal_id=<?php echo $s['portal_id']; ?>" class="btn-action-custom btn-lancar">
                                                                 <i class="bi bi-plus-square"></i> Novo Lançamento
                                                             </a>
                                                         <?php endif; ?>
 
                                                         <a href="<?php echo $edit_url; ?>" class="btn-action-custom btn-editar">
                                                             <i class="bi bi-pencil"></i> Editar
                                                         </a>
 
                                                         <?php if ($is_portal): ?>
                                                             <a href="ver_lancamentos.php?portal_id=<?php echo $s['portal_id']; ?>" class="btn-action-custom btn-ver">
                                                                 <i class="bi bi-file-earmark-bar-graph"></i> Planilha de Dados
                                                             </a>
                                                         <?php endif; ?>
 
                                                         <form method="POST" action="excluir_secao.php" class="d-inline" onsubmit="return confirm('ATENÇÃO: Deseja realmente excluir esta seção?');">
                                                             <?php if (!empty($s['card_id'])): ?>
                                                             <input type="hidden" name="card_id" value="<?php echo (int) $s['card_id']; ?>">
                                                             <?php endif; ?>
                                                             <?php if ($is_portal): ?>
                                                                 <input type="hidden" name="portal_id" value="<?php echo (int) $s['portal_id']; ?>">
                                                             <?php endif; ?>
                                                             <button type="submit" class="btn-action-custom btn-excluir">
                                                                 <i class="bi bi-trash"></i> Excluir
                                                             </button>
                                                         </form>
                                                         
                                                         <a href="<?php echo $public_link; ?>" target="_blank" class="btn-action-custom btn-detalhes ms-md-auto">
                                                             <i class="bi bi-box-arrow-up-right"></i> Ver Portal Público
                                                         </a>
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