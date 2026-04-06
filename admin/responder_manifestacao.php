<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

$manifestacao_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$manifestacao_id) { header("Location: ouvidoria_inbox.php"); exit; }

// Processa o formulário de resposta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_resposta'])) {
    require_once '../includes/functions_email.php';
    $novo_status = $_POST['status'];
    $nova_resposta = $_POST['resposta'];
    
    $pref_id = $_SESSION['id_prefeitura'];
    $stmt = $pdo->prepare("UPDATE ouvidoria_manifestacoes SET status = ?, resposta = ?, data_resposta = NOW() WHERE id = ? AND id_prefeitura = ?");
    $stmt->execute([$novo_status, $nova_resposta, $manifestacao_id, $pref_id]);
    
    // Busca informações para o e-mail e log
    $st_info = $pdo->prepare('SELECT protocolo, email, nome_cidadao FROM ouvidoria_manifestacoes WHERE id = ? AND id_prefeitura = ?');
    $st_info->execute([$manifestacao_id, $pref_id]);
    $man = $st_info->fetch(PDO::FETCH_ASSOC);

    registrar_log(
        $pdo,
        'EDIÇÃO',
        'ouvidoria_manifestacoes',
        'Atualizou resposta/status ouvidoria — protocolo ' . ($man['protocolo'] ?: '#' . $manifestacao_id) . " (status: $novo_status)."
    );

    // DISPARA E-MAIL SE FOR RESPOSTA OU FINALIZAÇÃO
    if (($novo_status === 'Respondida' || $novo_status === 'Finalizada') && !empty($man['email'])) {
        enviar_email_resposta($pdo, $man['email'], $man['nome_cidadao'], $man['protocolo'], 'Ouvidoria Municipal', $pref_id);
    }

    $_SESSION['mensagem_sucesso'] = "Manifestação respondida com sucesso!";
    header("Location: ouvidoria_inbox.php");
    exit;
}

// Busca a manifestação para exibir no formulário
$pref_id = $_SESSION['id_prefeitura'];
$stmt = $pdo->prepare("SELECT * FROM ouvidoria_manifestacoes WHERE id = ? AND id_prefeitura = ?");
$stmt->execute([$manifestacao_id, $pref_id]);
$manifestacao = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$manifestacao) { header("Location: ouvidoria_inbox.php"); exit; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responder Manifestação - Ouvidoria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .card-admin { border-radius: 15px !important; border: none !important; box-shadow: 0 5px 20px rgba(0,0,0,0.05) !important; }
        .label-admin { font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; display: block; margin-bottom: 5px; }
        .value-admin { font-size: 14px; color: #333; font-weight: 500; margin-bottom: 0; }
        .status-badge-admin { padding: 8px 16px; border-radius: 50px; font-weight: 700; font-size: 12px; }
        .section-separator { border-top: 1px solid #f1f1f1; margin: 30px 0; }
        .form-control-response { border-radius: 12px; border: 1px solid #e0e0e0; padding: 15px; }
    </style>
</head>
<body class="bg-light-subtle">

<?php 
$page_title_for_header = 'Ouvidoria - Resposta Oficial'; 
include 'admin_header.php'; 
?>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-12">
            
            <!-- Card Informativo -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #10b981 0%, #064e3b 100%);">
                <div class="card-body p-4 p-md-5 text-white">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="badge bg-white bg-opacity-20 mb-3 px-3 py-2 rounded-pill">
                                <i class="bi bi- megaphone-fill me-1"></i> Ouvidoria Municipal Inteligente
                            </div>
                            <h2 class="fw-bold mb-2">Responder Manifestação Oficial</h2>
                            <p class="lead mb-0 opacity-75">Sempre que você atualizar o status para <strong>Respondida</strong>, notificaremos o cidadão por e-mail para que ele acompanhe a decisão da prefeitura.</p>
                        </div>
                        <div class="col-md-4 text-end d-none d-md-block text-white-50">
                            <i class="bi bi-chat-right-check" style="font-size: 8rem; opacity: 0.2;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-4 px-2">
                <a href="ouvidoria_inbox.php" class="btn btn-light rounded-pill px-4 shadow-sm border fw-bold text-muted">
                    <i class="bi bi-arrow-left me-1"></i> Voltar para Inbox
                </a>
                <div class="text-end">
                    <span class="label-admin">Protocolo Ouvidoria</span>
                    <h4 class="fw-bold mb-0 text-success"><?php echo htmlspecialchars($manifestacao['protocolo']); ?></h4>
                </div>
            </div>

            <div class="card card-admin">
                <div class="card-header bg-white py-4 border-0 d-flex justify-content-between align-items-center px-4">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-file-earmark-person-fill text-success me-2"></i> Detalhes do Cidadão e Manifestação</h5>
                    <?php 
                        $status_class = 'bg-primary';
                        if($manifestacao['status'] == 'Finalizada') $status_class = 'bg-success';
                        if($manifestacao['status'] == 'Respondida') $status_class = 'bg-info';
                    ?>
                    <span class="badge status-badge-admin <?php echo $status_class; ?> shadow-sm"><?php echo htmlspecialchars($manifestacao['status']); ?></span>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <!-- Dados do Solicitante -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <span class="label-admin">Nome do Cidadão</span>
                            <p class="value-admin"><i class="bi bi-person me-2 text-muted"></i> <?php echo htmlspecialchars($manifestacao['nome_cidadao'] ?: 'Anônimo'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <span class="label-admin">E-mail para Contato</span>
                            <p class="value-admin"><i class="bi bi-envelope me-2 text-muted"></i> <?php echo htmlspecialchars($manifestacao['email'] ?: 'Não informado'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <span class="label-admin">WhatsApp / Celular</span>
                            <p class="value-admin"><i class="bi bi-whatsapp me-2 text-muted"></i> <?php echo htmlspecialchars($manifestacao['telefone'] ?: 'Não informado'); ?></p>
                        </div>
                        <div class="col-md-4 mt-3">
                            <span class="label-admin">Tipo / Natureza</span>
                            <div class="badge bg-light text-dark border px-3 py-2 rounded-pill"><?php echo htmlspecialchars($manifestacao['tipo_manifestacao']); ?></div>
                        </div>
                        <div class="col-md-8 mt-3">
                            <span class="label-admin">Assunto Selecionado</span>
                            <p class="value-admin fw-bold"><i class="bi bi-bookmark-fill me-2 text-success"></i> <?php echo htmlspecialchars($manifestacao['assunto']); ?></p>
                        </div>
                    </div>

                    <div class="section-separator"></div>

                    <!-- Conteúdo -->
                    <div class="mb-5">
                        <div class="d-flex justify-content-between mb-3 align-items-center">
                            <h6 class="fw-bold text-dark"><i class="bi bi-text-left text-success me-2"></i> Relato do Cidadão</h6>
                            <span class="small text-muted"><i class="bi bi-calendar3 me-1"></i> Registrado em: <?php echo date('d/m/Y H:i', strtotime($manifestacao['data_cadastro'])); ?></span>
                        </div>
                        <div class="bg-light p-4 rounded-4 text-secondary border" style="line-height: 1.8; font-size: 15px;">
                            <?php echo nl2br(htmlspecialchars($manifestacao['descricao'])); ?>
                        </div>
                    </div>

                    <!-- Formulário de Resposta -->
                    <div class="border rounded-4 p-4 p-md-5 bg-light-subtle">
                        <h5 class="fw-bold text-dark mb-4"><i class="bi bi-journal-check text-success me-2"></i> Redigir Resposta da Ouvidoria</h5>
                        
                        <form method="POST" action="responder_manifestacao.php?id=<?php echo $manifestacao_id; ?>">
                            <div class="row g-4">
                                <div class="col-md-5">
                                    <label for="status" class="form-label fw-bold small text-muted">Status do Atendimento</label>
                                    <select id="status" name="status" class="form-select rounded-pill border-2 shadow-sm py-2">
                                        <?php $status_atual = $manifestacao['status']; ?>
                                        <option <?php if($status_atual == 'Recebida') echo 'selected'; ?>>Recebida</option>
                                        <option <?php if($status_atual == 'Em Análise') echo 'selected'; ?>>Em Análise</option>
                                        <option <?php if($status_atual == 'Respondida') echo 'selected'; ?>>Respondida</option>
                                        <option <?php if($status_atual == 'Finalizada') echo 'selected'; ?>>Finalizada</option>
                                    </select>
                                </div>
                                
                                <div class="col-12 mt-4">
                                    <label for="resposta" class="form-label fw-bold small text-muted">Resposta para o Cidadão</label>
                                    <textarea id="resposta" name="resposta" class="form-control form-control-response border-2" rows="8" placeholder="Digite aqui o parecer oficial da ouvidoria..."><?php echo htmlspecialchars($manifestacao['resposta'] ?? ''); ?></textarea>
                                </div>

                                <div class="col-12 mt-5 text-end pt-4 border-top">
                                    <input type="hidden" name="salvar_resposta" value="1">
                                    <button type="submit" class="btn btn-success btn-lg rounded-pill px-5 py-3 fw-bold shadow transition-all hover-lift">
                                        <i class="bi bi-check2-circle me-2"></i> Salvar Resposta Oficial
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<style>
    .hover-lift:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important; }
    .transition-all { transition: all 0.3s ease; }
</style>
</body>
</html>