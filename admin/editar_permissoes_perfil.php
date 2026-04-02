<?php
// /admin/editar_permissoes_perfil.php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Apenas administradores podem gerenciar permissões
if ($_SESSION['admin_user_perfil'] !== 'admin') { header("Location: dashboard.php"); exit; }

$perfil_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$perfil_id) { header("Location: gerenciar_perfis.php"); exit; }

// Busca o perfil
$stmt = $pdo->prepare("SELECT id, nome FROM perfis WHERE id = ?");
$stmt->execute([$perfil_id]);
$perfil = $stmt->fetch();
if (!$perfil) { header("Location: gerenciar_perfis.php"); exit; }

// Lógica de Salvamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_permissoes'])) {
    
    // Limpa permissões antigas e reinicia (ou usa replace)
    $stmt_del = $pdo->prepare("DELETE FROM permissoes_perfil WHERE id_perfil = ?");
    $stmt_del->execute([$perfil_id]);
    
    $recursos_post = $_POST['permissoes'] ?? []; // Array associativo [recurso][ação]
    $stmt_ins = $pdo->prepare("INSERT INTO permissoes_perfil (id_perfil, recurso, p_ver, p_lancar, p_editar, p_excluir) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($recursos_post as $recurso => $acoes) {
        $p_ver = isset($acoes['ver']) ? 1 : 0;
        $p_lancar = isset($acoes['lancar']) ? 1 : 0;
        $p_editar = isset($acoes['editar']) ? 1 : 0;
        $p_excluir = isset($acoes['excluir']) ? 1 : 0;
        $stmt_ins->execute([$perfil_id, $recurso, $p_ver, $p_lancar, $p_editar, $p_excluir]);
    }

    $nome_perfil = $perfil['nome'];
    registrar_log($pdo, 'EDIÇÃO', 'permissoes_perfil', "Atualizou as permissões do perfil: $nome_perfil (ID: $perfil_id)");

    $_SESSION['mensagem_sucesso'] = "Permissões do perfil '{$perfil['nome']}' atualizadas com sucesso!";
    header("Location: gerenciar_perfis.php");
    exit;
}

// Busca permissões atuais do perfil
$stmt_perms = $pdo->prepare("SELECT * FROM permissoes_perfil WHERE id_perfil = ?");
$stmt_perms->execute([$perfil_id]);
$permissoes_raw = $stmt_perms->fetchAll();
$permissoes = [];
foreach ($permissoes_raw as $p) {
    $permissoes[$p['recurso']] = $p;
}

// Busca recursos dinâmicos (Seções/Portais) agrupados por Categoria
$sql_secoes = "
    SELECT p.id, p.nome as portal_nome, c.nome as categoria_nome 
    FROM portais p 
    LEFT JOIN categorias c ON p.id_categoria = c.id 
    ORDER BY c.ordem ASC, c.nome ASC, p.nome ASC
";
$secoes_raw = $pdo->query($sql_secoes)->fetchAll();
$secoes_agrupadas = [];
foreach ($secoes_raw as $s) {
    $cat_nome = $s['categoria_nome'] ?? 'Outros / Sem Categoria';
    $secoes_agrupadas[$cat_nome][] = $s;
}

$page_title_for_header = "Permissões: " . $perfil['nome'];
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding pb-5">
    <div class="row pt-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="gerenciar_perfis.php">Perfis</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($perfil['nome']); ?></li>
              </ol>
            </nav>

            <form method="POST">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 fw-bold text-success"><i class="bi bi-shield-check me-2"></i>Controle de Acessos</h4>
                        <button type="submit" name="salvar_permissoes" class="btn btn-success"><i class="bi bi-save me-1"></i> Salvar Alterações</button>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <!-- MÓDULOS GLOBAIS -->
                            <div class="col-12 mb-5">
                                <h5 class="fw-bold mb-4 pb-2 border-bottom text-primary"><i class="bi bi-grid-fill me-2"></i>Módulos do Sistema (Menu Superior/Lateral)</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 40%;">Módulo</th>
                                                <th class="text-center">Acesso (Ver)</th>
                                                <th class="text-center">Gerenciar (Lançar/Editar)</th>
                                                <th class="text-center">Excluir</th>
                                                <th class="text-center">Configurações/Tudo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $modulos = [
                                                'dashboard' => 'Painel de Controle (Dashboard)',
                                                'secoes' => 'Menu: Lançamentos + Estrutura (Lista e Gestão de Seções)',
                                                'sic' => 'Módulo: E-Sic (Inbox e Resp.)',
                                                'ouvidoria' => 'Módulo: Ouvidoria',
                                                'configuracoes' => 'Módulo: Prefeitura / Configurações',
                                                'relatorios' => 'Módulo: Relatórios',
                                                'usuarios' => 'Módulo: Usuários e Perfis',
                                                // Legado: mantém visível para migrar perfis antigos
                                                'prefeitura' => 'LEGADO: Prefeitura (compatibilidade)',
                                                'estrutura' => 'LEGADO: Estrutura (compatibilidade)'
                                            ];
                                            foreach($modulos as $slug => $label): 
                                                $p = $permissoes[$slug] ?? ['p_ver'=>0,'p_lancar'=>0,'p_editar'=>0,'p_excluir'=>0];
                                            ?>
                                            <tr>
                                                <td><strong><?php echo $label; ?></strong></td>
                                                <td class="text-center"><div class="form-check d-flex justify-content-center"><input class="form-check-input" type="checkbox" name="permissoes[<?php echo $slug; ?>][ver]" value="1" <?php if($p['p_ver']){ echo 'checked'; } ?>></div></td>
                                                <td class="text-center"><div class="form-check d-flex justify-content-center"><input class="form-check-input" type="checkbox" name="permissoes[<?php echo $slug; ?>][lancar]" value="1" <?php if($p['p_lancar']){ echo 'checked'; } ?>></div></td>
                                                <td class="text-center"><div class="form-check d-flex justify-content-center"><input class="form-check-input" type="checkbox" name="permissoes[<?php echo $slug; ?>][editar]" value="1" <?php if($p['p_editar']){ echo 'checked'; } ?>></div></td>
                                                <td class="text-center"><div class="form-check d-flex justify-content-center"><input class="form-check-input" type="checkbox" name="permissoes[<?php echo $slug; ?>][excluir]" value="1" <?php if($p['p_excluir']){ echo 'checked'; } ?>></div></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- FORMULÁRIOS DINÂMICOS (SEÇÕES) -->
                            <div class="col-12">
                                <h5 class="fw-bold mb-4 pb-2 border-bottom text-success"><i class="bi bi-stack me-2"></i>Formulários de Lançamento (Seções de Dados)</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 40%;">Seção (Formulário)</th>
                                                <th class="text-center">Ver</th>
                                                <th class="text-center">Lançar</th>
                                                <th class="text-center">Editar</th>
                                                <th class="text-center">Excluir</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($secoes_agrupadas as $cat_nome => $lista_secoes): ?>
                                                <tr class="table-secondary">
                                                    <td colspan="5" class="fw-bold text-uppercase small py-2"><?php echo htmlspecialchars($cat_nome); ?></td>
                                                </tr>
                                                <?php foreach ($lista_secoes as $s): 
                                                    $slug_secao = 'form_' . $s['id'];
                                                    $p = $permissoes[$slug_secao] ?? ['p_ver'=>0,'p_lancar'=>0,'p_editar'=>0,'p_excluir'=>0];
                                                ?>
                                                <tr>
                                                    <td class="ps-4"><i class="bi bi-file-earmark-text text-muted me-2"></i><?php echo htmlspecialchars($s['portal_nome']); ?></td>
                                                    <td class="text-center"><div class="form-check d-flex justify-content-center"><input class="form-check-input" type="checkbox" name="permissoes[<?php echo $slug_secao; ?>][ver]" value="1" <?php if($p['p_ver']){ echo 'checked'; } ?>></div></td>
                                                    <td class="text-center"><div class="form-check d-flex justify-content-center"><input class="form-check-input" type="checkbox" name="permissoes[<?php echo $slug_secao; ?>][lancar]" value="1" <?php if($p['p_lancar']){ echo 'checked'; } ?>></div></td>
                                                    <td class="text-center"><div class="form-check d-flex justify-content-center"><input class="form-check-input" type="checkbox" name="permissoes[<?php echo $slug_secao; ?>][editar]" value="1" <?php if($p['p_editar']){ echo 'checked'; } ?>></div></td>
                                                    <td class="text-center"><div class="form-check d-flex justify-content-center"><input class="form-check-input" type="checkbox" name="permissoes[<?php echo $slug_secao; ?>][excluir]" value="1" <?php if($p['p_excluir']){ echo 'checked'; } ?>></div></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div> <!-- .row -->
                    </div> <!-- .card-body -->
                </div> <!-- .card -->
                <input type="hidden" name="salvar_permissoes" value="1">
            </form>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
