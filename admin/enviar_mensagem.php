<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Bloqueia se não for superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pref = filter_input(INPUT_POST, 'id_prefeitura', FILTER_VALIDATE_INT);
    $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_SPECIAL_CHARS);
    $mensagem = filter_input(INPUT_POST, 'mensagem', FILTER_SANITIZE_SPECIAL_CHARS);
    $cor = filter_input(INPUT_POST, 'cor', FILTER_SANITIZE_SPECIAL_CHARS);

    $stmt = $pdo->prepare("INSERT INTO mensagens_sistema (id_prefeitura, titulo, mensagem, cor) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id_pref === 0 ? null : $id_pref, $titulo, $mensagem, $cor]);

    registrar_log($pdo, 'SUPERADMIN', 'ENVIAR_MENSAGEM', "Nova mensagem de avisos criada: $titulo");
    header("Location: gerenciar_mensagens.php");
    exit;
}

// Busca prefeituras ativas para seleção
$prefeituras = $pdo->query("SELECT id, nome FROM prefeituras WHERE status = 'ativo' ORDER BY nome ASC")->fetchAll();

$page_title_for_header = 'Enviar Nova Mensagem / Aviso';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-dark text-white p-4 rounded-top-4">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-chat-left-text me-2"></i> Enviar Notificação Interna</h5>
                    <p class="mb-0 text-white-50 opacity-75 small">Este comunicado aparecerá imediatamente no dashboard da(s) prefeitura(s) selecionada(s).</p>
                </div>
                <div class="card-body p-4">
                    <form method="POST" class="row g-4">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Destinatário</label>
                            <select name="id_prefeitura" class="form-select" required>
                                <option value="0">🌎 Todas as Prefeituras (Global)</option>
                                <?php foreach ($prefeituras as $p): ?>
                                    <option value="<?php echo $p['id']; ?>">🏢 <?php echo htmlspecialchars($p['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label small fw-bold text-muted">Título do Comunicado</label>
                            <input type="text" name="titulo" class="form-control" required placeholder="Ex: Manutenção Programada ou Nova Funcionalidade">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted">Aparência do Alerta</label>
                            <select name="cor" class="form-select" required>
                                <option value="primary" class="text-primary">Informativo (Azul)</option>
                                <option value="warning" class="text-warning text-bold">Aviso/Importante (Amarelo)</option>
                                <option value="danger" class="text-danger fw-bold">Urgente/Crítico (Vermelho)</option>
                                <option value="success" class="text-success">Sucesso/Novidade (Verde)</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted">Mensagem</label>
                            <textarea name="mensagem" class="form-control" rows="5" required placeholder="Digite aqui o conteúdo da mensagem..."></textarea>
                        </div>

                        <div class="col-12 mt-5 text-end d-flex justify-content-between align-items-center">
                            <a href="gerenciar_mensagens.php" class="btn btn-outline-secondary rounded-pill px-4">Voltar</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow">
                                <i class="bi bi-send me-2"></i> Enviar Aviso Agora
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
