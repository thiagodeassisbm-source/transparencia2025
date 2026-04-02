<?php
// /admin/editar_usuario.php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

$usuario_id_para_editar = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$usuario_id_para_editar) { header("Location: dashboard.php"); exit; }

// Pega informações do usuário logado
$id_logado = $_SESSION['admin_user_id'];

// TRAVA DE SEGURANÇA: Apenas admin ou o próprio usuário podem editar
if ($_SESSION['admin_user_perfil'] !== 'admin' && $usuario_id_para_editar != $id_logado) {
    header("Location: dashboard.php");
    exit;
}

$erro = '';

// Processa o formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $nome = trim($_POST['nome']);
    $senha = $_POST['senha'];
    $senha_confirma = $_POST['senha_confirma'];
    
    // Se o usuário logado for admin, pega o id_perfil do post. Senão, mantém o atual.
    $id_perfil = filter_input(INPUT_POST, 'id_perfil', FILTER_VALIDATE_INT);

    if (empty($usuario)) { 
        $erro = "O nome de usuário não pode ser vazio."; 
    } elseif (empty($nome)) {
        $erro = "O seu nome completo é obrigatório.";
    } else {
        // Verifica se o novo nome de usuário já está em uso por OUTRO usuário
        $stmt_check = $pdo->prepare("SELECT id FROM usuarios_admin WHERE usuario = ? AND id != ?");
        $stmt_check->execute([$usuario, $usuario_id_para_editar]);
        if ($stmt_check->fetch()) {
            $erro = "Este nome de usuário já está em uso.";
        } else {
            // Lógica de atualização
            $sql = "UPDATE usuarios_admin SET usuario = ?, nome = ?" . (!empty($senha) ? ", senha = ?" : "");
            $params = [$usuario, $nome];
            if (!empty($senha)) {
                if ($senha !== $senha_confirma) {
                    $erro = "As novas senhas não coincidem.";
                } else {
                    $params[] = password_hash($senha, PASSWORD_DEFAULT);
                }
            }
            
            if (!$erro) {
                // Adiciona id_perfil apenas se for admin editando
                if ($_SESSION['admin_user_perfil'] === 'admin' && $id_perfil) {
                    $sql .= ", id_perfil = ?";
                    $params[] = $id_perfil;
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $usuario_id_para_editar;
                
                $stmt_update = $pdo->prepare($sql);
                $stmt_update->execute($params);
                
                registrar_log($pdo, 'EDIÇÃO', 'usuarios_admin', "Atualizou o usuário: $usuario (ID: $usuario_id_para_editar)");
                
                // Atualiza sessão se for o próprio usuário
                if ($usuario_id_para_editar == $id_logado) {
                    $_SESSION['admin_user_nome'] = $usuario;
                    if ($id_perfil) $_SESSION['admin_user_id_perfil'] = $id_perfil;
                }
                
                $_SESSION['mensagem_sucesso'] = "Perfil atualizado com sucesso!";
                header("Location: editar_usuario.php?id=" . $usuario_id_para_editar);
                exit;
            }
        }
    }
}

// Busca os dados atuais do usuário (Aplicando Travas SaaS)
$sql_user = "SELECT id, usuario, nome, id_perfil, is_superadmin FROM usuarios_admin WHERE id = ?";
$params_user = [$usuario_id_para_editar];

if (!$_SESSION['is_superadmin']) {
    $sql_user .= " AND id_prefeitura = ?";
    $params_user[] = $_SESSION['id_prefeitura'];
}

$stmt = $pdo->prepare($sql_user);
$stmt->execute($params_user);
$usuario_atual = $stmt->fetch();
if (!$usuario_atual) { header("Location: dashboard.php"); exit; }

// Busca perfis para o select
$perfis = $pdo->query("SELECT id, nome FROM perfis ORDER BY nome ASC")->fetchAll();

$page_title_for_header = 'Editar Perfil'; 
include 'admin_header.php'; 

$url_voltar = (!empty($_SESSION['is_superadmin']) && (int) $_SESSION['is_superadmin'] === 1)
    ? 'super_dashboard.php'
    : 'gerenciar_usuarios.php';
?>

<div class="container-fluid container-custom-padding">
    <div class="row pt-4">
        <div class="col-lg-8 mx-auto">
            <?php 
                if (isset($_SESSION['mensagem_sucesso'])) {
                    echo '<div class="alert alert-success border-0 shadow-sm">' . htmlspecialchars($_SESSION['mensagem_sucesso']) . '</div>';
                    unset($_SESSION['mensagem_sucesso']);
                }
                if ($erro) { 
                    echo '<div class="alert alert-danger border-0 shadow-sm">' . $erro . '</div>'; 
                } 
            ?>
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white pt-4 px-4 border-0">
                    <h5 class="mb-0 fw-bold">Informações da Conta</h5>
                    <p class="text-muted small mt-1">Gerencie seu nome real, usuário de acesso e preferências de segurança.</p>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label fw-bold">Nome Completo</label>
                                <input type="text" class="form-control" name="nome" value="<?php echo htmlspecialchars($usuario_atual['nome'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Usuário de Acesso</label>
                                <input type="text" class="form-control" name="usuario" value="<?php echo htmlspecialchars($usuario_atual['usuario'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label fw-bold">Perfil de Acesso</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-check"></i></span>
                                    <select class="form-select bg-light border-start-0" name="id_perfil" <?php echo ($usuario_atual['is_superadmin'] == 1) ? 'disabled' : ''; ?>>
                                        <?php if ($usuario_atual['is_superadmin'] == 1): ?>
                                            <option value="0" selected>Super Administrador</option>
                                        <?php else: ?>
                                            <?php foreach ($perfis as $p): ?>
                                                <option value="<?php echo $p['id']; ?>" <?php echo ($usuario_atual['id_perfil'] == $p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['nome']); ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <?php if ($usuario_atual['is_superadmin'] == 1): ?>
                                    <div class="form-text text-primary small mt-1"><i class="bi bi-info-circle me-1"></i> Perfil mestre do sistema (SaaS).</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="bg-light p-4 rounded-3 mb-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock me-2"></i> Alteração de Senha</h6>
                            <p class="text-muted small mb-4">Deixe os campos abaixo vazios caso não queira alterar.</p>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold text-uppercase">Nova Senha</label>
                                    <input type="password" class="form-control" name="senha" placeholder="••••••••">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold text-uppercase">Confirmar Nova Senha</label>
                                    <input type="password" class="form-control" name="senha_confirma" placeholder="••••••••">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4 py-2">Salvar Alterações</button>
                            <a href="<?php echo $url_voltar; ?>" class="btn btn-light px-4 py-2 border">Voltar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>