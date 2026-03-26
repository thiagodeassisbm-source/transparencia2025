<?php
require_once 'auth_check.php';
require_once '../conexao.php';

$usuario_id_para_editar = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$usuario_id_para_editar) { header("Location: dashboard.php"); exit; }

// Pega informações do usuário logado
$perfil_logado = $_SESSION['admin_user_perfil'];
$id_logado = $_SESSION['admin_user_id'];

// TRAVA DE SEGURANÇA: Editor só pode editar a si mesmo
if ($perfil_logado === 'editor' && $usuario_id_para_editar != $id_logado) {
    $_SESSION['mensagem_sucesso'] = "Acesso negado: Editores só podem alterar o próprio perfil.";
    header("Location: dashboard.php");
    exit;
}

$erro = '';

// Processa o formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $nome = trim($_POST['nome']); // Novo campo
    $senha = $_POST['senha'];
    $senha_confirma = $_POST['senha_confirma'];
    
    // Se o usuário logado for admin, pega o perfil do post. Senão, mantém o perfil como 'editor'.
    $perfil = ($perfil_logado === 'admin') ? ($_POST['perfil'] ?? 'editor') : 'editor';

    if (empty($usuario)) { 
        $erro = "O nome de usuário não pode ser vazio."; 
    } elseif (empty($nome)) {
        $erro = "O seu nome completo é obrigatório para exibir a saudação.";
    } elseif (!in_array($perfil, ['admin', 'editor'])) {
        $erro = "Perfil inválido selecionado.";
    } else {
        // Trava de segurança para impedir que o admin se rebaixe
        if ($usuario_id_para_editar == $id_logado && $perfil_logado === 'admin' && $perfil !== 'admin') {
            $erro = "Você não pode remover seu próprio status de Administrador.";
        } else {
            // Verifica se o novo nome de usuário já está em uso por OUTRO usuário
            $stmt_check = $pdo->prepare("SELECT id FROM usuarios_admin WHERE usuario = ? AND id != ?");
            $stmt_check->execute([$usuario, $usuario_id_para_editar]);
            if ($stmt_check->fetch()) {
                $erro = "Este nome de usuário já está em uso por outra conta.";
            } else {
                // Lógica de atualização
                if (!empty($senha)) {
                    if ($senha !== $senha_confirma) {
                        $erro = "As novas senhas não coincidem.";
                    } else {
                        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                        $stmt_update = $pdo->prepare("UPDATE usuarios_admin SET usuario = ?, nome = ?, senha = ?, perfil = ? WHERE id = ?");
                        $stmt_update->execute([$usuario, $nome, $senha_hash, $perfil, $usuario_id_para_editar]);
                        
                        // Atualiza sessão se for o próprio usuário
                        if ($usuario_id_para_editar == $id_logado) {
                            $_SESSION['admin_user_nome_real'] = $nome;
                            $_SESSION['admin_user_nome'] = $usuario;
                        }
                        
                        $_SESSION['mensagem_sucesso'] = "Perfil e senha atualizados com sucesso!";
                        header("Location: editar_usuario.php?id=" . $usuario_id_para_editar);
                        exit;
                    }
                } else {
                    $stmt_update = $pdo->prepare("UPDATE usuarios_admin SET usuario = ?, nome = ?, perfil = ? WHERE id = ?");
                    $stmt_update->execute([$usuario, $nome, $perfil, $usuario_id_para_editar]);
                    
                    // Atualiza sessão se for o próprio usuário
                    if ($usuario_id_para_editar == $id_logado) {
                        $_SESSION['admin_user_nome_real'] = $nome;
                        $_SESSION['admin_user_nome'] = $usuario;
                    }

                    $_SESSION['mensagem_sucesso'] = "Perfil atualizado com sucesso!";
                    header("Location: editar_usuario.php?id=" . $usuario_id_para_editar);
                    exit;
                }
            }
        }
    }
}

// Busca os dados atuais do usuário para preencher o formulário
$stmt = $pdo->prepare("SELECT id, usuario, nome, perfil FROM usuarios_admin WHERE id = ?");
$stmt->execute([$usuario_id_para_editar]);
$usuario_atual = $stmt->fetch();

if (!$usuario_atual) { header("Location: dashboard.php"); exit; }

$page_title_for_header = 'Editar Perfil'; 
include 'admin_header.php'; 
?>

<div class="container-fluid">
    <div class="row">
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
                    <form method="POST" action="editar_usuario.php?id=<?php echo $usuario_id_para_editar; ?>">
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="nome" class="form-label fw-bold">Seu Nome (Exibido na Saudação)</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario_atual['nome'] ?? ''); ?>" placeholder="Digite seu nome completo..." required>
                            </div>
                            <div class="col-md-6">
                                <label for="usuario" class="form-label fw-bold">Usuário de Acesso</label>
                                <input type="text" class="form-control" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuario_atual['usuario']); ?>" required>
                            </div>
                        </div>
                        
                        <?php if ($perfil_logado === 'admin'): ?>
                        <div class="mb-4">
                            <label for="perfil" class="form-label fw-bold">Perfil de Acesso</label>
                            <select class="form-select" id="perfil" name="perfil" <?php if ($usuario_atual['id'] == $id_logado) echo 'disabled'; ?>>
                                <option value="editor" <?php echo ($usuario_atual['perfil'] == 'editor') ? 'selected' : ''; ?>>Editor</option>
                                <option value="admin" <?php echo ($usuario_atual['perfil'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                            <?php if ($usuario_atual['id'] == $id_logado): ?>
                                <small class="text-muted d-block mt-1"><i class="bi bi-info-circle me-1"></i> Você não pode alterar o seu próprio perfil de acesso.</small>
                                <input type="hidden" name="perfil" value="admin" />
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="bg-light p-4 rounded-3 mb-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock me-2"></i> Alteração de Senha</h6>
                            <p class="text-muted small mb-4">Deixe os campos abaixo vazios caso não queira alterar sua senha atual.</p>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="senha" class="form-label small fw-bold text-uppercase">Nova Senha</label>
                                    <input type="password" class="form-control" id="senha" name="senha" placeholder="••••••••">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="senha_confirma" class="form-label small fw-bold text-uppercase">Confirmar Nova Senha</label>
                                    <input type="password" class="form-control" id="senha_confirma" name="senha_confirma" placeholder="••••••••">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4 py-2 fw-600">
                                <i class="bi bi-check2-circle me-2"></i> Salvar Alterações
                            </button>
                            <a href="<?php echo ($perfil_logado === 'admin' ? 'gerenciar_usuarios.php' : 'dashboard.php'); ?>" class="btn btn-light px-4 py-2 border">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>