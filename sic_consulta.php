<?php
require_once 'conexao.php';
require_once 'bootstrap_portal.php';

$page_title = "Consulta de Solicitação (e-SIC)";
$protocolo_busca = trim($_GET['protocolo'] ?? '');
$solicitacao = null;

if (!empty($protocolo_busca)) {
    $id_pref = $id_prefeitura_ativa ?? 0;
    // Busca pela prefeitura ativa para garantir o SaaS de isolamento
    $stmt = $pdo->prepare("SELECT * FROM sic_solicitacoes WHERE protocolo = ? AND id_prefeitura = ?");
    $stmt->execute([$protocolo_busca, $id_pref]);
    $solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Portal da Transparência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/style.css?v=<?php echo time(); ?>">
    <style>
        .card-detail { border-radius: 20px !important; border: none !important; box-shadow: 0 10px 30px rgba(0,0,0,0.05) !important; overflow: hidden; }
        .detail-header { background: #f8f9fa; border-bottom: 1px solid #f1f1f1; padding: 25px; }
        .label-custom { font-size: 13px; font-weight: 700; color: #888; text-uppercase: true; display: block; margin-bottom: 5px; }
        .value-custom { font-size: 16px; color: #333; font-weight: 500; margin-bottom: 0; }
        .status-badge { padding: 10px 20px; border-radius: 50px; font-weight: 700; }
        .response-box { background: rgba(var(--cor-principal-rgb), 0.03); border: 1px dashed var(--cor-principal); border-radius: 15px; padding: 25px; margin-top: 20px; }
    </style>
</head>
<body class="bg-light">

<?php include 'header_publico.php'; ?>

<div class="container-fluid py-5">
    <div class="row">
        <!-- Menu Lateral -->
        <div class="col-md-3 col-lg-2 d-none d-md-block px-0 ps-3 mb-4">
            <?php include 'menu.php'; ?>
        </div>

        <!-- Conteúdo Principal -->
        <main class="col-md-9 ms-auto col-lg-10 px-md-5">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>portal/<?php echo $slug_pref_header; ?>/" class="text-decoration-none">Início</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>portal/<?php echo $slug_pref_header; ?>/sic.php" class="text-decoration-none">SIC</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Consulta de Protocolo</li>
                </ol>
            </nav>

            <div class="d-flex align-items-center mb-5 border-bottom pb-3">
               <h2 class="fw-bold text-dark mb-0">Detalhes da Solicitação</h2>
            </div>

            <?php if ($solicitacao): ?>
                <div class="card card-detail mb-5 animate__animated animate__fadeIn">
                    <div class="detail-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <span class="label-custom">Protocolo</span>
                            <h4 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($solicitacao['protocolo']); ?></h4>
                        </div>
                        <div class="text-md-end">
                            <span class="label-custom">Status Atual</span>
                            <span class="badge bg-primary status-badge fs-6 shadow-sm"><i class="bi bi-clock-history me-1"></i> <?php echo htmlspecialchars($solicitacao['status']); ?></span>
                        </div>
                    </div>
                    
                    <div class="card-body p-4 p-md-5">
                        <div class="row g-4 mb-5">
                            <div class="col-md-4 text-start">
                                <span class="label-custom">Recebido em</span>
                                <p class="value-custom"><i class="bi bi-calendar3 text-primary me-2"></i> <?php echo date('d/m/Y \à\s H:i', strtotime($solicitacao['data_solicitacao'])); ?></p>
                            </div>
                            <div class="col-md-4 text-start">
                                <span class="label-custom">Solicitante</span>
                                <p class="value-custom"><i class="bi bi-person-fill text-primary me-2"></i> <?php echo htmlspecialchars($solicitacao['nome_solicitante']); ?></p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <span class="label-custom">Documento (<?php echo htmlspecialchars($solicitacao['tipo_documento']); ?>)</span>
                                <p class="value-custom"><i class="bi bi-card-text text-primary me-2"></i> <?php echo htmlspecialchars($solicitacao['numero_documento']); ?></p>
                            </div>
                        </div>

                        <div class="row g-4 mb-5">
                            <div class="col-md-6 text-start">
                                <span class="label-custom">E-mail de Retorno</span>
                                <p class="value-custom"><i class="bi bi-envelope-fill text-primary me-2"></i> <?php echo htmlspecialchars($solicitacao['email'] ?: 'Não informado'); ?></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="label-custom">Telefone / WhatsApp</span>
                                <p class="value-custom"><i class="bi bi-whatsapp text-primary me-2"></i> <?php echo htmlspecialchars($solicitacao['telefone'] ?: 'Não informado'); ?></p>
                            </div>
                        </div>

                        <div class="mb-5">
                            <span class="label-custom">Descrição Detalhada do Pedido</span>
                            <div class="bg-light p-4 rounded-4 text-muted" style="line-height: 1.8;">
                                <?php echo nl2br(htmlspecialchars($solicitacao['descricao_pedido'])); ?>
                            </div>
                        </div>

                        <div class="response-box">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-chat-left-text-fill"></i>
                                </div>
                                <h5 class="fw-bold text-dark mb-0">Resposta / Manifestação do SIC</h5>
                            </div>
                            
                            <div class="text-dark" style="line-height: 1.7;">
                                <?php if (!empty($solicitacao['resposta'])): ?>
                                    <div class="p-2">
                                        <?php echo nl2br(htmlspecialchars($solicitacao['resposta'])); ?>
                                        <?php if (!empty($solicitacao['data_resposta'])): ?>
                                            <p class="mt-3 small text-muted border-top pt-2 italic"><i class="bi bi-info-circle me-1"></i> Respondido em: <?php echo date('d/m/Y H:i', strtotime($solicitacao['data_resposta'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="mb-0 text-muted italic">Sua solicitação está em fase de análise pela nossa equipe. Retorne em breve para conferir a resposta oficial.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-5 text-center">
                            <a href="<?php echo $base_url; ?>portal/<?php echo $slug_pref_header; ?>/sic.php" class="btn btn-outline-dynamic fw-bold px-5 rounded-pill shadow-sm">
                                <i class="bi bi-arrow-left me-2"></i> Voltar ao e-SIC
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-4 p-5 text-center mb-5">
                    <i class="bi bi-exclamation-triangle-fill display-1 mb-4 opacity-25"></i>
                    <h4 class="fw-bold">Protocolo Não Localizado</h4>
                    <p class="mb-4">Nenhuma solicitação foi encontrada com o protocolo <strong><?php echo htmlspecialchars($protocolo_busca); ?></strong> nesta prefeitura.</p>
                    <a href="<?php echo $base_url; ?>portal/<?php echo $slug_pref_header; ?>/sic.php" class="btn btn-danger px-4 rounded-pill fw-bold">Tentar Novamente</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php 
$custom_container_class = "container-custom-padding";
include 'footer_publico.php'; 
?>

<style>
    .btn-outline-dynamic { border: 1px solid #ddd; color: #555; }
    .btn-outline-dynamic:hover { border-color: var(--cor-principal); background: rgba(var(--cor-principal-rgb), 0.05); color: #111; }
    .italic { font-style: italic; }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>