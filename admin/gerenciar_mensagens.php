<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Bloqueia se não for superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

// 1. Busca as mensagens enviadas
$stmt = $pdo->query("
    SELECT m.*, p.nome as prefeitura_nome,
    (SELECT COUNT(*) FROM mensagens_vistas v WHERE v.id_mensagem = m.id) as total_vistas
    FROM mensagens_sistema m
    LEFT JOIN prefeituras p ON m.id_prefeitura = p.id
    ORDER BY m.criado_em DESC
");
$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title_for_header = 'Gestão de Avisos e Comunicados';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-megaphone me-2 text-primary"></i> Comunicação SaaS</h4>
            <p class="text-muted small mb-0">Envie avisos que aparecerão no topo do painel das prefeituras.</p>
        </div>
        <a href="enviar_mensagem.php" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
            <i class="bi bi-plus-circle me-2"></i> Enviar Novo Aviso
        </a>
    </div>

    <!-- Lista de Mensagens -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light bg-opacity-50 border-bottom">
                        <tr>
                            <th class="ps-4 py-3 text-muted small text-uppercase">Destinatário</th>
                            <th class="py-3 text-muted small text-uppercase">Título / Assunto</th>
                            <th class="py-3 text-muted small text-uppercase text-center">Status / Visualizações</th>
                            <th class="py-3 text-muted small text-uppercase text-center">Data</th>
                            <th class="py-3 text-muted small text-uppercase text-center" style="width: 120px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mensagens as $m): ?>
                        <tr>
                            <td class="ps-4">
                                <?php if ($m['id_prefeitura']): ?>
                                    <span class="badge bg-info-subtle text-info rounded-pill px-3">
                                        <i class="bi bi-building me-1"></i> <?php echo htmlspecialchars($m['prefeitura_nome']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-primary rounded-pill px-3">
                                        <i class="bi bi-globe me-1"></i> Todas as Prefeituras
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($m['titulo']); ?></div>
                                <div class="text-muted small text-truncate" style="max-width: 300px;"><?php echo htmlspecialchars($m['mensagem']); ?></div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center">
                                    <div class="bg-light rounded-pill px-3 py-1 border small">
                                        <i class="bi bi-eye-fill text-primary me-1"></i> <strong><?php echo $m['total_vistas']; ?></strong> visualizações
                                    </div>
                                    <?php if ($m['ativa']): ?>
                                        <span class="ms-2 badge bg-success-subtle text-success small">Ativa</span>
                                    <?php else: ?>
                                        <span class="ms-2 badge bg-danger-subtle text-danger small">Inativa</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center text-muted small">
                                <?php echo date('d/m/Y H:i', strtotime($m['criado_em'])); ?>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group shadow-sm rounded-pill overflow-hidden bg-white border">
                                    <a href="excluir_mensagem.php?id=<?php echo $m['id']; ?>" class="btn btn-white btn-sm px-3" onclick="return confirm('Deseja remover este aviso?')">
                                        <i class="bi bi-trash text-danger"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($mensagens)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Nenhuma mensagem enviada ainda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.btn-white:hover { background: #f8f9fa; }
</style>

<?php include 'admin_footer.php'; ?>
