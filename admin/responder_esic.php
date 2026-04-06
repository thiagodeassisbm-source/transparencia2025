<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

$solicitacao_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$solicitacao_id) {
    header("Location: sic_inbox.php");
    exit;
}

// Processa o formulário de resposta quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_resposta'])) {
    require_once '../includes/functions_email.php';
    $novo_status = $_POST['status'];
    $nova_resposta = $_POST['resposta'];
    
    $pref_id = $_SESSION['id_prefeitura'];
    $stmt = $pdo->prepare("UPDATE sic_solicitacoes SET status = ?, resposta = ?, data_resposta = NOW() WHERE id = ? AND id_prefeitura = ?");
    $stmt->execute([$novo_status, $nova_resposta, $solicitacao_id, $pref_id]);
    
    // Busca dados para o log e e-mail
    $st_info = $pdo->prepare('SELECT protocolo, email, nome_solicitante FROM sic_solicitacoes WHERE id = ? AND id_prefeitura = ?');
    $st_info->execute([$solicitacao_id, $pref_id]);
    $sol = $st_info->fetch(PDO::FETCH_ASSOC);
    
    registrar_log(
        $pdo,
        'EDIÇÃO',
        'sic_solicitacoes',
        'Atualizou resposta/status e-SIC — protocolo ' . ($sol['protocolo'] ?: '#' . $solicitacao_id) . " (status: $novo_status)."
    );

    // DISPARA E-MAIL SE FOR RESPOSTA OU FINALIZAÇÃO
    if (($novo_status === 'Respondida' || $novo_status === 'Finalizada') && !empty($sol['email'])) {
        enviar_email_resposta($pdo, $sol['email'], $sol['nome_solicitante'], $sol['protocolo'], 'e-SIC (Acesso à Informação)', $pref_id);
    }

    $_SESSION['mensagem_sucesso'] = "Solicitação respondida com sucesso!";
    header("Location: sic_inbox.php");
    exit;
}

// Busca a solicitação para exibir no formulário
$pref_id = $_SESSION['id_prefeitura'];
$stmt = $pdo->prepare("SELECT * FROM sic_solicitacoes WHERE id = ? AND id_prefeitura = ?");
$stmt->execute([$solicitacao_id, $pref_id]);
$solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$solicitacao) {
    $_SESSION['mensagem_sucesso'] = "Solicitação não encontrada.";
    header("Location: sic_inbox.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responder Solicitação SIC - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .card-admin { border-radius: 15px !important; border: none !important; box-shadow: 0 5px 20px rgba(0,0,0,0.05) !important; }
        .label-admin { font-size: 11px; font-weight: 700; color: #888; text-uppercase: true; display: block; margin-bottom: 5px; }
        .value-admin { font-size: 14px; color: #333; font-weight: 500; margin-bottom: 0; }
        .status-badge-admin { padding: 8px 16px; border-radius: 50px; font-weight: 700; font-size: 12px; }
        .section-separator { border-top: 1px solid #f1f1f1; margin: 30px 0; }
        .form-control-response { border-radius: 12px; border: 1px solid #e0e0e0; padding: 15px; }
        .form-control-response:focus { border-color: var(--cor-principal); box-shadow: 0 0 0 0.25rem rgba(var(--cor-principal-rgb), 0.1); }
    </style>
</head>
<body class="bg-light-subtle">

<?php 
$page_title_for_header = 'SIC - Detalhes da Solicitação'; 
include 'admin_header.php'; 
?>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-lg-10 col-xl-8 mx-auto">
            
            <div class="d-flex align-items-center justify-content-between mb-4">
                <a href="sic_inbox.php" class="btn btn-light rounded-pill px-3 shadow-sm border fw-bold small">
                    <i class="bi bi-arrow-left me-1"></i> Voltar
                </a>
                <div class="text-end">
                    <span class="label-admin">Protocolo</span>
                    <h5 class="fw-bold mb-0 text-primary"><?php echo htmlspecialchars($solicitacao['protocolo']); ?></h5>
                </div>
            </div>

            <div class="card card-admin">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-info-circle-fill text-primary me-2"></i> Informações do Pedido</h5>
                    <?php 
                        $status_class = 'bg-primary';
                        if($solicitacao['status'] == 'Finalizada') $status_class = 'bg-success';
                        if($solicitacao['status'] == 'Respondida') $status_class = 'bg-info';
                    ?>
                    <span class="badge status-badge-admin <?php echo $status_class; ?> shadow-sm"><?php echo htmlspecialchars($solicitacao['status']); ?></span>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <!-- Dados do Solicitante -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <span class="label-admin">Solicitante</span>
                            <p class="value-admin"><i class="bi bi-person me-2 text-muted"></i> <?php echo htmlspecialchars($solicitacao['nome_solicitante']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <span class="label-admin">CPF/CNPJ</span>
                            <p class="value-admin"><i class="bi bi-card-text me-2 text-muted"></i> <?php echo htmlspecialchars($solicitacao['numero_documento'] ?: 'Não informado'); ?></p>
                        </div>
                        <div class="col-md-6 mt-3">
                            <span class="label-admin">E-mail</span>
                            <p class="value-admin"><i class="bi bi-envelope me-2 text-muted"></i> <?php echo htmlspecialchars($solicitacao['email'] ?: 'Não informado'); ?></p>
                        </div>
                        <div class="col-md-6 mt-3">
                            <span class="label-admin">WhatsApp / Telefone</span>
                            <p class="value-admin"><i class="bi bi-whatsapp me-2 text-muted"></i> <?php echo htmlspecialchars($solicitacao['telefone'] ?: 'Não informado'); ?></p>
                        </div>
                    </div>

                    <div class="section-separator"></div>

                    <!-- Conteúdo do Pedido -->
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-3">
                            <h6 class="fw-bold text-dark"><i class="bi bi-chat-left-dots text-primary me-2"></i> Descrição do Pedido</h6>
                            <span class="small text-muted"><i class="bi bi-calendar-event me-1"></i> Recebido em: <?php echo date('d/m/Y H:i', strtotime($solicitacao['data_solicitacao'])); ?></span>
                        </div>
                        <div class="bg-light p-4 rounded-4 text-muted border border-dashed" style="line-height: 1.8; font-size: 14px;">
                            <?php echo nl2br(htmlspecialchars($solicitacao['descricao_pedido'])); ?>
                        </div>
                    </div>

                    <div class="section-separator"></div>

                    <!-- Formulário de Resposta -->
                    <h5 class="fw-bold text-dark mb-4"><i class="bi bi-reply-all-fill text-success me-2"></i> Elaborar Resposta Oficial</h5>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $solicitacao_id; ?>">
                        <div class="row g-4">
                            <div class="col-md-5">
                                <label for="status" class="form-label fw-bold text-muted small">Status do Atendimento</label>
                                <select id="status" name="status" class="form-select rounded-pill border shadow-sm">
                                    <?php $status_atual = $solicitacao['status']; ?>
                                    <option value="Recebido" <?php if($status_atual == 'Recebido') echo 'selected'; ?>>Recebido</option>
                                    <option value="Em Análise" <?php if($status_atual == 'Em Análise') echo 'selected'; ?>>Em Análise</option>
                                    <option value="Respondida" <?php if($status_atual == 'Respondida') echo 'selected'; ?>>Respondida</option>
                                    <option value="Finalizada" <?php if($status_atual == 'Finalizada') echo 'selected'; ?>>Finalizada</option>
                                </select>
                            </div>
                            
                            <div class="col-12 mt-4">
                                <label for="resposta" class="form-label fw-bold text-muted small">Conteúdo da Resposta</label>
                                <textarea id="resposta" name="resposta" class="form-control form-control-response" rows="10" placeholder="Escreva aqui a resposta técnica ou as informações solicitadas pelo cidadão..."><?php echo htmlspecialchars($solicitacao['resposta'] ?? ''); ?></textarea>
                                <div class="form-text mt-2 small italic text-muted">
                                    <i class="bi bi-info-circle me-1"></i> Esta resposta ficará visível para o cidadão no portal público através da consulta de protocolo.
                                </div>
                            </div>

                            <div class="col-12 mt-5 text-end pt-4 border-top">
                                <input type="hidden" name="salvar_resposta" value="1">
                                <button type="submit" class="btn btn-success rounded-pill px-5 py-2 fw-bold shadow-sm">
                                    <i class="bi bi-send-check me-2"></i> Salvar e Atualizar Solicitação
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>