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

<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-12 pt-4">
            <?php
            if (isset($_SESSION['mensagem_sucesso'])) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . 
                     htmlspecialchars($_SESSION['mensagem_sucesso']) . 
                     '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                unset($_SESSION['mensagem_sucesso']);
            }
            if (isset($_SESSION['mensagem_erro'])) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . 
                     htmlspecialchars($_SESSION['mensagem_erro']) . 
                     '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                unset($_SESSION['mensagem_erro']);
            }
            if ($erro) {
                echo '<div class="alert alert-danger">' . $erro . '</div>';
            }

            ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Novo Perfil de Acesso</h5></div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nome do Perfil</label>
                                    <input type="text" name="nome" class="form-control" placeholder="Ex: Moderador, Consulta" required>
                                </div>
                                <button type="submit" name="add_perfil" class="btn btn-primary w-100">Criar Perfil</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Perfis Cadastrados</h5></div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nome do Perfil</th>
                                        <th class="text-center">Usuários</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($perfis as $perfil): ?>
                                    <tr>
                                        <td><span class="fw-bold fs-5"><?php echo htmlspecialchars($perfil['nome']); ?></span></td>
                                        <td class="text-center"><span class="badge bg-light text-dark border"><?php echo $perfil['total_usuarios']; ?></span></td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="editar_permissoes_perfil.php?id=<?php echo $perfil['id']; ?>" class="btn btn-outline-primary btn-sm rounded-3 d-flex align-items-center justify-content-center shadow-none" style="width: 32px; height: 32px;" title="Configurar Permissões">
                                                    <i class="bi bi-shield-lock"></i>
                                                </a>
                                                <?php if ($perfil['nome'] !== 'Administrador'): ?>
                                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-3 d-flex align-items-center justify-content-center shadow-none" style="width: 32px; height: 32px;" onclick="prepararExclusao(<?php echo $perfil['id']; ?>, '<?php echo addslashes($perfil['nome']); ?>', <?php echo $perfil['total_usuarios']; ?>)" title="Excluir Perfil">
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
