<?php
// /admin/gerenciar_perfis.php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Apenas administradores podem acessar a gestão de perfis
if ($_SESSION['admin_user_perfil'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$id_prefeitura = $_SESSION['id_prefeitura'];
$mensagem = '';
$erro = '';

// Lógica para cadastrar novo perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_perfil'])) {
    $nome = trim($_POST['nome']);
    if (empty($nome)) {
        $erro = "O nome do perfil é obrigatório.";
    } else {
        // Agora o check é por prefeitura
        $stmt_check = $pdo->prepare("SELECT id FROM perfis WHERE nome = ? AND (id_prefeitura = ? OR id_prefeitura IS NULL)");
        $stmt_check->execute([$nome, $id_prefeitura]);
        if ($stmt_check->fetch()) {
            $erro = "Este perfil já existe para sua prefeitura.";
        } else {
            $stmt_insert = $pdo->prepare("INSERT INTO perfis (nome, id_prefeitura) VALUES (?, ?)");
            $stmt_insert->execute([$nome, $id_prefeitura]);
            $perfil_id = $pdo->lastInsertId();
            
            registrar_log($pdo, 'ADIÇÃO', 'perfis', "Criou novo perfil de acesso: $nome (ID: $perfil_id) para prefeitura ID: $id_prefeitura");
            
            $_SESSION['mensagem_sucesso'] = "Perfil '$nome' criado! Agora configure as permissões.";
            header("Location: gerenciar_perfis.php");
            exit;
        }
    }
}

// Busca os perfis existentes filtrados pela prefeitura
$sql = "
    SELECT id, nome, 
    (SELECT COUNT(*) FROM usuarios_admin WHERE id_perfil = perfis.id AND id_prefeitura = :pref_id1) as total_usuarios 
    FROM perfis 
    WHERE id_prefeitura = :pref_id2 OR id_prefeitura IS NULL
    ORDER BY nome ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':pref_id1' => $id_prefeitura, ':pref_id2' => $id_prefeitura]);
$perfis = $stmt->fetchAll();


$page_title_for_header = 'Gerenciar Perfis';
include 'admin_header.php';
?>

<style>
    .gp-page-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        overflow: hidden;
    }
    .gp-hero-card {
        background: linear-gradient(135deg, var(--sidebar-header-bg, #36c0d3) 0%, #2d9fb0 100%);
        color: #fff;
        border-radius: 15px;
        border: none;
        box-shadow: 0 8px 24px rgba(54, 192, 211, 0.22);
    }
    .gp-table-wrap .table thead th {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 11px;
        font-weight: 700;
        color: #555;
        border-bottom: 2px solid #eee;
        padding: 12px 14px;
        white-space: nowrap;
    }
    .gp-table-wrap .table tbody td {
        padding: 12px 14px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f1f1;
    }
</style>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-12">

            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <h3 class="fw-bold text-dark mb-1">Gerenciar Perfis</h3>
                    <p class="text-muted small mb-0"><i class="bi bi-shield-lock me-1"></i> Crie perfis de acesso e configure permissões por seção do painel.</p>
                </div>
            </div>

            <?php
            if (isset($_SESSION['mensagem_sucesso'])) {
                echo '<div class="alert alert-success border-0 shadow-sm alert-dismissible fade show rounded-4 mb-4" role="alert">'
                   . '<i class="bi bi-check-circle-fill me-2"></i>'
                   . htmlspecialchars($_SESSION['mensagem_sucesso'])
                   . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div>';
                unset($_SESSION['mensagem_sucesso']);
            }
            if (isset($_SESSION['mensagem_erro'])) {
                echo '<div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show rounded-4 mb-4" role="alert">'
                   . '<i class="bi bi-exclamation-triangle-fill me-2"></i>'
                   . htmlspecialchars($_SESSION['mensagem_erro'])
                   . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div>';
                unset($_SESSION['mensagem_erro']);
            }
            if ($erro) {
                echo '<div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4" role="alert">'
                   . '<i class="bi bi-exclamation-triangle-fill me-2"></i>' . htmlspecialchars($erro) . '</div>';
            }
            ?>

            <div class="card gp-hero-card mb-4">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="me-4 d-none d-md-block">
                        <div class="bg-white bg-opacity-20 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                            <i class="bi bi-person-badge fs-2"></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1 text-white">Perfis e permissões</h5>
                        <p class="mb-0 opacity-90 small">
                            Cada perfil pode ter regras distintas (ver, lançar, editar). Use o ícone <i class="bi bi-shield-lock"></i> para abrir o editor de permissões após criar o perfil.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card gp-page-card h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-person-plus me-2 text-primary"></i>Novo perfil de acesso
                            </h6>
                        </div>
                        <div class="card-body bg-light bg-opacity-10 px-4 py-4">
                            <form method="POST" class="d-grid gap-3">
                                <div>
                                    <label for="nome_perfil" class="form-label fw-bold small text-muted">Nome do perfil</label>
                                    <input type="text" id="nome_perfil" name="nome" class="form-control border-0 shadow-sm" placeholder="Ex.: Moderador, Consulta" required style="border-radius: 10px;">
                                    <div class="form-text small">Nome exibido na lista de usuários e nas permissões.</div>
                                </div>
                                <button type="submit" name="add_perfil" class="btn btn-primary shadow-sm rounded-3 py-2 fw-semibold">
                                    <i class="bi bi-plus-lg me-2"></i>Criar perfil
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card gp-page-card h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-people me-2 text-primary"></i>Perfis cadastrados
                            </h6>
                            <span class="badge bg-light text-dark border rounded-pill px-3"><?php echo count($perfis); ?> total</span>
                        </div>
                        <div class="table-responsive gp-table-wrap">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nome do perfil</th>
                                        <th class="text-center" style="width: 110px;">Usuários</th>
                                        <th class="text-center" style="width: 140px;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($perfis as $perfil): ?>
                                    <tr>
                                        <td><span class="fw-semibold"><?php echo htmlspecialchars($perfil['nome']); ?></span></td>
                                        <td class="text-center"><span class="badge bg-light text-dark border rounded-pill"><?php echo (int) $perfil['total_usuarios']; ?></span></td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="editar_permissoes_perfil.php?id=<?php echo (int) $perfil['id']; ?>" class="btn btn-outline-primary btn-sm rounded-3 d-flex align-items-center justify-content-center shadow-none" style="width: 34px; height: 34px;" title="Configurar permissões">
                                                    <i class="bi bi-shield-lock"></i>
                                                </a>
                                                <?php if ($perfil['nome'] !== 'Administrador'): ?>
                                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-3 d-flex align-items-center justify-content-center shadow-none" style="width: 34px; height: 34px;" onclick="prepararExclusao(<?php echo (int) $perfil['id']; ?>, '<?php echo addslashes($perfil['nome']); ?>', <?php echo (int) $perfil['total_usuarios']; ?>)" title="Excluir perfil">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
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
</div>

<!-- Modal de Exclusão -->
<div class="modal fade" id="modalExcluirPerfil" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form action="excluir_perfil.php" method="POST">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Excluir Perfil</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="perfil_id" id="inputPerfilId">
          
          <p id="pConfirmacaoPadrao" class="fs-5">Deseja realmente excluir o perfil <strong id="strongNomePerfil"></strong>?</p>

          <div id="divAvisoUsuarios" class="alert alert-warning border-warning shadow-sm d-none">
            <i class="bi bi-info-circle-fill me-2"></i>
            Este perfil possui <strong id="strongTotalUsuarios"></strong> usuários vinculados. 
            <hr>
            <label class="form-label fw-bold">Transferir usuários para:</label>
            <select name="transferir_para_id" class="form-select border-warning" id="selectTransferir">
                <option value="">-- Selecionar Perfil --</option>
                <?php foreach ($perfis as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nome']); ?></option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted d-block mt-2">Escolha para qual perfil os usuários atuais serão migrados antes da exclusão.</small>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger px-4">Confirmar e Excluir</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function prepararExclusao(id, nome, totalUsuarios) {
    document.getElementById('inputPerfilId').value = id;
    document.getElementById('strongNomePerfil').innerText = nome;
    
    const divAviso = document.getElementById('divAvisoUsuarios');
    const select = document.getElementById('selectTransferir');
    
    if (totalUsuarios > 0) {
        divAviso.classList.remove('d-none');
        document.getElementById('strongTotalUsuarios').innerText = totalUsuarios;
        select.setAttribute('required', 'required');
        
        // Esconde o próprio perfil na lista de transferência
        Array.from(select.options).forEach(option => {
            if (option.value == id) {
                option.style.display = 'none';
            } else {
                option.style.display = 'block';
            }
        });
    } else {
        divAviso.classList.add('d-none');
        select.removeAttribute('required');
    }
    
    const myModal = new bootstrap.Modal(document.getElementById('modalExcluirPerfil'));
    myModal.show();
}
</script>

<?php include 'admin_footer.php'; ?>
