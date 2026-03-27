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

// Busca recursos dinâmicos (Seções/Portais)
$secoes = $pdo->query("SELECT id, nome FROM portais ORDER BY nome")->fetchAll();

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
                            <!-- MENUS E RECURSOS GLOBAIS -->
                            <div class="col-lg-6">

                            <!-- FORMULÁRIOS DINÂMICOS (SEÇÕES) -->
                            <div class="col-lg-6 border-start ps-lg-4">
                                <h5 class="fw-bold mb-4 pb-2 border-bottom">Formulários de Lançamento (Seções)</h5>
                                <div class="accordion" id="accordionSecoes">
                                    <div class="table-responsive">
                                        <table class="table align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Seção (Formulário)</th>
                                                    <th class="text-center">Ver</th>
                                                    <th class="text-center">Lançar</th>
                                                    <th class="text-center">Editar</th>
                                                    <th class="text-center">Excluir</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($secoes as $s): 
                                                    $slug_secao = 'form_' . $s['id'];
                                                    $p = $permissoes[$slug_secao] ?? ['p_ver'=>0,'p_lancar'=>0,'p_editar'=>0,'p_excluir'=>0];
                                                ?>
                                                <tr>
                                                    <td><i class="bi bi-file-earmark-text text-muted me-2"></i><?php echo htmlspecialchars($s['nome']); ?></td>
                                                    <td class="text-center"><div class="form-check"><input class="form-check-input mx-auto" type="checkbox" name="permissoes[<?php echo $slug_secao; ?>][ver]" value="1" <?php if($p['p_ver']){ echo 'checked'; } ?>></div></td>
                                                    <td class="text-center"><div class="form-check"><input class="form-check-input mx-auto" type="checkbox" name="permissoes[<?php echo $slug_secao; ?>][lancar]" value="1" <?php if($p['p_lancar']){ echo 'checked'; } ?>></div></td>
                                                    <td class="text-center"><div class="form-check"><input class="form-check-input mx-auto" type="checkbox" name="permissoes[<?php echo $slug_secao; ?>][editar]" value="1" <?php if($p['p_editar']){ echo 'checked'; } ?>></div></td>
                                                    <td class="text-center"><div class="form-check"><input class="form-check-input mx-auto" type="checkbox" name="permissoes[<?php echo $slug_secao; ?>][excluir]" value="1" <?php if($p['p_excluir']){ echo 'checked'; } ?>></div></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <input type="hidden" name="salvar_permissoes" value="1">
            </form>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
