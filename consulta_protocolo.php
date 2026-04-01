<?php
require_once 'conexao.php';
require_once 'bootstrap_portal.php';

$page_title = 'Consulta de Protocolo (Ouvidoria)';
$protocolo_busca = trim($_GET['protocolo'] ?? '');
$manifestacao = null;

if ($protocolo_busca !== '') {
    $id_pref = (int)($id_prefeitura_ativa ?? 0);
    $stmt = $pdo->prepare(
        'SELECT * FROM ouvidoria_manifestacoes WHERE protocolo = ? AND id_prefeitura = ?'
    );
    $stmt->execute([$protocolo_busca, $id_pref]);
    $manifestacao = $stmt->fetch(PDO::FETCH_ASSOC);
}

/** Badge de status — cores alinhadas à caixa de entrada admin */
function ouvidoria_badge_class(string $status): string
{
    $s = mb_strtolower(trim($status), 'UTF-8');
    if (strpos($s, 'finaliz') !== false) {
        return 'bg-success';
    }
    if (strpos($s, 'respond') !== false) {
        return 'bg-info text-dark';
    }
    if (strpos($s, 'análise') !== false || strpos($s, 'analise') !== false) {
        return 'bg-warning text-dark';
    }
    if (strpos($s, 'receb') !== false) {
        return 'bg-primary';
    }
    return 'bg-secondary';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Portal da Transparência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/style.css?v=<?php echo time(); ?>">
    <style>
        .card-detail { border-radius: 20px !important; border: none !important; box-shadow: 0 10px 30px rgba(0,0,0,0.05) !important; overflow: hidden; }
        .detail-header { background: #f8f9fa; border-bottom: 1px solid #f1f1f1; padding: 25px; }
        .label-custom { font-size: 13px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 0.02em; display: block; margin-bottom: 5px; }
        .value-custom { font-size: 16px; color: #333; font-weight: 500; margin-bottom: 0; }
        .status-badge { padding: 10px 20px; border-radius: 50px; font-weight: 700; }
        /* Tom teal — distinto do azul do e-SIC */
        .consulta-ouvidoria .response-box-ouv {
            background: rgba(13, 148, 136, 0.06);
            border: 1px dashed #0d9488;
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
        }
        .consulta-ouvidoria .ouv-icon { color: #0d9488 !important; }
        .consulta-ouvidoria .ouv-ico-circle {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%) !important;
        }
    </style>
    <script>
    (function () {
        try {
            var n = parseInt(localStorage.getItem('fontSize'), 10);
            if (!isNaN(n) && n >= 12 && n <= 32) document.documentElement.style.fontSize = n + 'px';
            if (localStorage.getItem('highContrast') === 'true') document.documentElement.classList.add('high-contrast');
        } catch (e) {}
    })();
    </script>
</head>
<body class="bg-light consulta-ouvidoria">

<?php include 'header_publico.php'; ?>

<div class="container-fluid py-5">
    <div class="row">
        <div class="col-md-3 col-lg-2 d-none d-md-block px-0 ps-3 mb-4">
            <?php include 'menu.php'; ?>
        </div>

        <main class="col-md-9 ms-auto col-lg-10 px-md-5">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>portal/<?php echo htmlspecialchars($slug_pref_header); ?>/" class="text-decoration-none">Início</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>portal/<?php echo htmlspecialchars($slug_pref_header); ?>/ouvidoria.php" class="text-decoration-none">Ouvidoria</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Consulta de Protocolo</li>
                </ol>
            </nav>

            <div class="d-flex align-items-center mb-5 border-bottom pb-3">
                <h2 class="fw-bold text-dark mb-0">Detalhes da Manifestação</h2>
            </div>

            <?php if ($manifestacao):
                $dt_criacao = $manifestacao['data_criacao'] ?? null;
                $dt_criacao_fmt = $dt_criacao ? date('d/m/Y \à\s H:i', strtotime($dt_criacao)) : '—';
                $badge_cls = ouvidoria_badge_class((string)($manifestacao['status'] ?? ''));
            ?>
                <div class="card card-detail mb-5 animate__animated animate__fadeIn">
                    <div class="detail-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <span class="label-custom">Protocolo</span>
                            <h4 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($manifestacao['protocolo']); ?></h4>
                        </div>
                        <div class="text-md-end">
                            <span class="label-custom">Status atual</span>
                            <span class="badge <?php echo $badge_cls; ?> status-badge fs-6 shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i><?php echo htmlspecialchars($manifestacao['status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <div class="row g-4 mb-5">
                            <div class="col-md-4 text-start">
                                <span class="label-custom">Recebido em</span>
                                <p class="value-custom"><i class="bi bi-calendar3 ouv-icon me-2"></i><?php echo htmlspecialchars($dt_criacao_fmt); ?></p>
                            </div>
                            <div class="col-md-4 text-start">
                                <span class="label-custom">Cidadão</span>
                                <p class="value-custom"><i class="bi bi-person-fill ouv-icon me-2"></i><?php echo htmlspecialchars($manifestacao['nome_cidadao'] ?? '—'); ?></p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <span class="label-custom">Tipo de manifestação</span>
                                <p class="value-custom"><i class="bi bi-tag-fill ouv-icon me-2"></i><?php echo htmlspecialchars($manifestacao['tipo_manifestacao'] ?? '—'); ?></p>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6 text-start">
                                <span class="label-custom">E-mail de retorno</span>
                                <p class="value-custom"><i class="bi bi-envelope-fill ouv-icon me-2"></i><?php
                                    $email_m = trim((string)($manifestacao['email'] ?? ''));
                                    echo htmlspecialchars($email_m !== '' ? $email_m : 'Não informado');
                                ?></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="label-custom">Telefone / WhatsApp</span>
                                <p class="value-custom"><i class="bi bi-whatsapp ouv-icon me-2"></i><?php echo htmlspecialchars(!empty($manifestacao['telefone']) ? $manifestacao['telefone'] : 'Não informado'); ?></p>
                            </div>
                        </div>

                        <div class="mb-2">
                            <span class="label-custom">Assunto</span>
                            <p class="value-custom mb-4"><?php echo htmlspecialchars($manifestacao['assunto'] ?? '—'); ?></p>
                        </div>

                        <div class="mb-5">
                            <span class="label-custom">Descrição da manifestação</span>
                            <div class="bg-light p-4 rounded-4 text-muted" style="line-height: 1.8;">
                                <?php echo nl2br(htmlspecialchars($manifestacao['descricao'] ?? '')); ?>
                            </div>
                        </div>

                        <div class="response-box-ouv">
                            <div class="d-flex align-items-center mb-3">
                                <div class="text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center ouv-ico-circle" style="width: 40px; height: 40px;">
                                    <i class="bi bi-chat-left-text-fill"></i>
                                </div>
                                <h5 class="fw-bold text-dark mb-0">Resposta da Ouvidoria</h5>
                            </div>

                            <div class="text-dark" style="line-height: 1.7;">
                                <?php if (!empty($manifestacao['resposta'])): ?>
                                    <div class="p-2">
                                        <?php echo nl2br(htmlspecialchars($manifestacao['resposta'])); ?>
                                        <?php if (!empty($manifestacao['data_resposta'])): ?>
                                            <p class="mt-3 small text-muted border-top pt-2 mb-0" style="font-style: italic;"><i class="bi bi-clock-history me-1"></i>Respondido em: <?php echo date('d/m/Y H:i', strtotime($manifestacao['data_resposta'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="mb-0 text-muted" style="font-style: italic;">Sua manifestação está em análise. Por favor, aguarde.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-5 text-center">
                            <a href="<?php echo $base_url; ?>portal/<?php echo htmlspecialchars($slug_pref_header); ?>/ouvidoria.php" class="btn btn-outline-dynamic fw-bold px-5 rounded-pill shadow-sm">
                                <i class="bi bi-arrow-left me-2"></i>Voltar à Ouvidoria
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-4 p-5 text-center mb-5">
                    <i class="bi bi-exclamation-triangle-fill display-1 mb-4 opacity-25"></i>
                    <h4 class="fw-bold">Protocolo não localizado</h4>
                    <p class="mb-4">Nenhuma manifestação foi encontrada com o protocolo <strong><?php echo htmlspecialchars($protocolo_busca); ?></strong> nesta prefeitura.</p>
                    <a href="<?php echo $base_url; ?>portal/<?php echo htmlspecialchars($slug_pref_header); ?>/ouvidoria.php" class="btn btn-danger px-4 rounded-pill fw-bold">Tentar novamente</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php
$custom_container_class = 'container-custom-padding';
include 'footer_publico.php';
?>

<style>
    .btn-outline-dynamic { border: 1px solid #ddd; color: #555; }
    .btn-outline-dynamic:hover { border-color: #0d9488; background: rgba(13, 148, 136, 0.08); color: #134e4a; }
</style>
</body>
</html>
