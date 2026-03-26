<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Variáveis de sessão do usuário
$perfil_usuario = $_SESSION['admin_user_perfil'];
$id_usuario_logado = $_SESSION['admin_user_id'];
$nome_usuario_logado = $_SESSION['admin_user_nome'] ?? 'Usuário';

$secao_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$secao_id) { header("Location: index.php"); exit; }

// Processa o formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        // --- Atualiza os dados da Seção ---
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
        if (empty($id_categoria)) { $id_categoria = null; }
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nome)));
        $stmt_secao = $pdo->prepare("UPDATE portais SET nome = ?, descricao = ?, slug = ?, id_categoria = ? WHERE id = ?");
        $stmt_secao->execute([$nome, $descricao, $slug, $id_categoria, $secao_id]);

        // --- Gerencia o Card associado ---
        $manter_card = isset($_POST['manter_card']);
        $card_titulo = trim($_POST['card_titulo']);
        $card_subtitulo = trim($_POST['card_subtitulo']);
        $card_ordem = (int)$_POST['card_ordem'];
        $caminho_icone_antigo = $_POST['caminho_icone_antigo'];

        $stmt_find_card = $pdo->prepare("SELECT id, caminho_icone FROM cards_informativos WHERE id_secao = ?");
        $stmt_find_card->execute([$secao_id]);
        $card_existente = $stmt_find_card->fetch();

        if ($manter_card) { // O usuário quer que esta seção tenha um card
            $caminho_icone_final = $caminho_icone_antigo;
            if (isset($_FILES['card_icone']) && $_FILES['card_icone']['error'] === UPLOAD_ERR_OK) {
                if (!empty($caminho_icone_antigo) && file_exists($caminho_icone_antigo)) { unlink($caminho_icone_antigo); }
                $upload_dir = '../uploads/';
                $nome_arquivo = 'card-' . uniqid() . '-' . basename($_FILES['card_icone']['name']);
                $caminho_destino = $upload_dir . $nome_arquivo;
                move_uploaded_file($_FILES['card_icone']['tmp_name'], $caminho_destino);
                $caminho_icone_final = $caminho_destino;
            }

            if ($card_existente) { // Se o card já existe, atualiza (UPDATE)
                $stmt_card = $pdo->prepare("UPDATE cards_informativos SET id_categoria = ?, titulo = ?, subtitulo = ?, caminho_icone = ?, ordem = ? WHERE id = ?");
                $stmt_card->execute([$id_categoria, $card_titulo, $card_subtitulo, $caminho_icone_final, $card_ordem, $card_existente['id']]);
            } else { // Se não existe, cria (INSERT)
                $stmt_card = $pdo->prepare("INSERT INTO cards_informativos (id_secao, id_categoria, titulo, subtitulo, caminho_icone, ordem) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_card->execute([$secao_id, $id_categoria, $card_titulo, $card_subtitulo, $caminho_icone_final, $card_ordem]);
            }
        } else { // O usuário NÃO quer que esta seção tenha um card
            if ($card_existente) { // Se existe um card, apaga
                if (!empty($card_existente['caminho_icone']) && file_exists($card_existente['caminho_icone'])) { unlink($card_existente['caminho_icone']); }
                $stmt_del_card = $pdo->prepare("DELETE FROM cards_informativos WHERE id = ?");
                $stmt_del_card->execute([$card_existente['id']]);
            }
        }

        $pdo->commit();
        $_SESSION['mensagem_sucesso'] = "Seção atualizada com sucesso!";
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['mensagem_sucesso'] = "Erro ao atualizar: " . $e->getMessage();
        header("Location: editar_secao.php?id=" . $secao_id);
        exit;
    }
}

// Busca os dados para preencher o formulário
$stmt_secao = $pdo->prepare("SELECT * FROM portais WHERE id = ?");
$stmt_secao->execute([$secao_id]);
$secao_atual = $stmt_secao->fetch();
if (!$secao_atual) { header("Location: index.php"); exit; }

$stmt_card = $pdo->prepare("SELECT * FROM cards_informativos WHERE id_secao = ?");
$stmt_card->execute([$secao_id]);
$card_atual = $stmt_card->fetch();

$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Seção e Card - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">

<?php
// Define as variáveis para o cabeçalho reutilizável
$page_title_for_header = 'Editar Seção: ' . htmlspecialchars($secao_atual['nome']);
$active_breadcrumb = 'Editar Seção';
// Como é uma sub-página, nenhum link principal da navegação fica ativo
$active_nav_link = ''; 
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-12 pt-4">
            <form method="POST" action="editar_secao.php?id=<?php echo $secao_id; ?>" enctype="multipart/form-data">
                <div class="card mb-4">
                    <div class="card-header">1. Dados da Seção</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome" class="form-label">Nome da Seção</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($secao_atual['nome']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="id_categoria" class="form-label">Categoria</label>
                                <select class="form-select" id="id_categoria" name="id_categoria">
                                    <option value="">-- Nenhuma --</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>" <?php echo ($categoria['id'] == $secao_atual['id_categoria']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição (Opcional)</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="2"><?php echo htmlspecialchars($secao_atual['descricao']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">2. Dados do Card da Página Inicial</div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="manter_card" name="manter_card" <?php echo $card_atual ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="manter_card"><strong>Manter/Criar um card de atalho para esta seção?</strong></label>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="card_titulo" class="form-label">Título do Card</label>
                                <input type="text" class="form-control" id="card_titulo" name="card_titulo" value="<?php echo htmlspecialchars($card_atual['titulo'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="card_subtitulo" class="form-label">Subtítulo do Card</label>
                                <input type="text" class="form-control" id="card_subtitulo" name="card_subtitulo" value="<?php echo htmlspecialchars($card_atual['subtitulo'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ícone Atual</label><br>
                                <?php if ($card_atual && !empty($card_atual['caminho_icone'])): ?>
                                    <img src="<?php echo htmlspecialchars($card_atual['caminho_icone']); ?>" alt="Ícone Atual" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: contain;">
                                <?php else: ?>
                                    <small class="text-muted">Nenhum ícone cadastrado.</small>
                                <?php endif; ?>
                                <input type="hidden" name="caminho_icone_antigo" value="<?php echo htmlspecialchars($card_atual['caminho_icone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="card_icone" class="form-label">Enviar Novo Ícone</label>
                                <input type="file" class="form-control" id="card_icone" name="card_icone" accept="image/*">
                                <small class="form-text text-muted">Envie apenas se desejar substituir.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="card_ordem" class="form-label">Ordem de Exibição do Card</label>
                                <input type="number" class="form-control" id="card_ordem" name="card_ordem" value="<?php echo htmlspecialchars($card_atual['ordem'] ?? '0'); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Salvar Alterações</button>
                    <a href="index.php" class="btn btn-secondary btn-lg">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<footer class="text-center p-3 bg-light mt-4">
    &copy; <?php echo date('Y'); ?> - Todos os direitos reservados.
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>