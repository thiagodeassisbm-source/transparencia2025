<?php
require_once 'conexao.php';
require_once 'bootstrap_portal.php';
$page_title = "Ouvidoria Municipal";

// Lógica para estatísticas (Específicas da prefeitura se necessário)
$stats = [];
$total_manifestacoes = 0;
try {
    // Aqui usaremos o ID da prefeitura ativa se as estatísticas forem separadas
    $curr_pref_id = $id_prefeitura_ativa ?? 0;
    
    // Por enquanto buscamos o global ou podemos filtrar pela prefeitura se a tabela permitir
    $stmt_stats = $pdo->prepare("SELECT tipo_manifestacao, COUNT(id) as total FROM ouvidoria_manifestacoes WHERE id_prefeitura = ? GROUP BY tipo_manifestacao");
    $stmt_stats->execute([$curr_pref_id]);
    $stats_raw = $stmt_stats->fetchAll(PDO::FETCH_KEY_PAIR);

    $tipos_possiveis = ['Elogio', 'Sugestão', 'Reclamação', 'Denúncia', 'Solicitação'];
    foreach ($tipos_possiveis as $tipo) {
        $stats[$tipo] = $stats_raw[$tipo] ?? 0;
    }
    $total_manifestacoes = array_sum($stats);
}
catch (Exception $e) {
    $stats = [];
    $total_manifestacoes = 0;
}

// Busca as configurações da ouvidoria (SaaS Overrides: id_prefeitura > 0 > FALLBACK)
$config_ouvidoria = [];
try {
    $id_pref_config = $id_prefeitura_ativa ?? 0;
    $stmt_config = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE (id_prefeitura = ? OR id_prefeitura = 0) AND chave LIKE 'ouvidoria_%' ORDER BY id_prefeitura ASC");
    $stmt_config->execute([$id_pref_config]);
    $config_ouvidoria = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {}

// Fallbacks de contato e textos dos cards (admin: config_ouvidoria.php)
$config_ouvidoria['ouvidoria_setor'] = $config_ouvidoria['ouvidoria_setor'] ?? 'Ouvidoria Municipal';
$config_ouvidoria['ouvidoria_endereco'] = $config_ouvidoria['ouvidoria_endereco'] ?? 'Sede Administrativa';
$config_ouvidoria['ouvidoria_email'] = $config_ouvidoria['ouvidoria_email'] ?? 'ouvidoria@municipio.gov.br';
$config_ouvidoria['ouvidoria_telefone'] = $config_ouvidoria['ouvidoria_telefone'] ?? '(62) 0000-0000';
$config_ouvidoria['ouvidoria_pagina_intro'] = $config_ouvidoria['ouvidoria_pagina_intro'] ?? 'A ouvidoria é o seu canal direto com a gestão municipal. Aqui você pode registrar elogios, sugestões, reclamações, solicitações ou denúncias de forma segura e transparente.';
$config_ouvidoria['ouvidoria_manifestar_descricao'] = $config_ouvidoria['ouvidoria_manifestar_descricao'] ?? '';
$config_ouvidoria['ouvidoria_consultar_descricao'] = $config_ouvidoria['ouvidoria_consultar_descricao'] ?? 'Acompanhe sua manifestação digitando o número do protocolo abaixo.';
$config_ouvidoria['ouvidoria_consultar_botao_label'] = $config_ouvidoria['ouvidoria_consultar_botao_label'] ?? 'Acompanhar Manifestação';
$config_ouvidoria['ouvidoria_relatorio_descricao'] = $config_ouvidoria['ouvidoria_relatorio_descricao'] ?? '';
$config_ouvidoria['ouvidoria_relatorio_ver_mais_texto'] = $config_ouvidoria['ouvidoria_relatorio_ver_mais_texto'] ?? 'Ver Relatório Completo Detalhado';
$config_ouvidoria['ouvidoria_relatorio_ver_mais_link'] = $config_ouvidoria['ouvidoria_relatorio_ver_mais_link'] ?? '';

/**
 * Link do botão Manifestar: vazio = formulário padrão do portal; senão URL absoluta ou caminho.
 */
function ouvidoria_href_manifesto(string $base_url, string $slug, $link_custom, string $tipo_query): string {
    $link_custom = trim((string)$link_custom);
    if ($link_custom === '') {
        return rtrim($base_url, '/') . '/portal/' . rawurlencode($slug) . '/abrir_manifestacao.php?' . http_build_query(['tipo' => $tipo_query]);
    }
    if (preg_match('#^https?://#i', $link_custom)) {
        return $link_custom;
    }
    return rtrim($base_url, '/') . '/' . ltrim($link_custom, '/');
}

function ouvidoria_href_opcional(string $base_url, $link_custom, string $path_padrao): string {
    $link_custom = trim((string)$link_custom);
    if ($link_custom === '') {
        return rtrim($base_url, '/') . '/' . ltrim($path_padrao, '/');
    }
    if (preg_match('#^https?://#i', $link_custom)) {
        return $link_custom;
    }
    return rtrim($base_url, '/') . '/' . ltrim($link_custom, '/');
}

// Slug do portal (bootstrap); header_publico.php ainda não rodou — não usar $slug_pref_header aqui
$slug_portal = $slug_prefeitura_ativa ?? 'home';

$manifestar_tipos = [
    ['slug' => 'sugestao', 'tipo' => 'Sugestão', 'icon' => 'bi-lightbulb-fill me-2 text-warning', 'default_label' => 'Sugestão'],
    ['slug' => 'elogio', 'tipo' => 'Elogio', 'icon' => 'bi-hand-thumbs-up-fill me-2 text-success', 'default_label' => 'Elogio'],
    ['slug' => 'solicitacao', 'tipo' => 'Solicitação', 'icon' => 'bi-chat-left-dots-fill me-2 text-info', 'default_label' => 'Solicitação'],
    ['slug' => 'reclamacao', 'tipo' => 'Reclamação', 'icon' => 'bi-exclamation-triangle-fill me-2 text-warning', 'default_label' => 'Reclamação'],
    ['slug' => 'denuncia', 'tipo' => 'Denúncia', 'icon' => 'bi-shield-fill-exclamation me-2 text-danger', 'default_label' => 'Denúncia'],
];

$href_relatorio_ver_mais = ouvidoria_href_opcional(
    $base_url,
    $config_ouvidoria['ouvidoria_relatorio_ver_mais_link'],
    'portal/' . $slug_portal . '/ouvidoria_relatorio.php'
);
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
        /* Mesmo padrão tipográfico do ESIC (sic.php) */
        .sic-card { border-radius: 15px !important; border: none !important; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; }
        .sic-card-title { font-size: 24px !important; font-weight: 700 !important; color: #1a1a1a; margin-bottom: 25px; display: block; line-height: 1.3; }
        .sic-info-item { border-bottom: 1px solid #f0f0f0; padding: 15px 0; }
        .sic-info-item:last-child { border-bottom: none; }
        .sic-info-label { display: block; font-weight: 700; color: #333; font-size: 14px; margin-bottom: 5px; }
        .sic-info-value { font-size: 15px; color: #555; margin-bottom: 0; font-weight: 400; }
        .section-desc-sac { font-size: 13px; color: #777; margin-bottom: 1.25rem; display: block; line-height: 1.5; }
        .section-title-sac { font-weight: 700; font-size: 18px; color: #222; margin-bottom: 0.35rem; display: block; }
        .btn-outline-dynamic {
            border: 1px solid #eee;
            color: #444;
            font-size: 15px;
            font-weight: 500;
        }
        .btn-outline-dynamic:hover {
            border-color: var(--cor-principal);
            background: rgba(var(--cor-principal-rgb), 0.05);
        }
        .sic-relatorio-head .sic-card-title { margin-bottom: 0 !important; }
    </style>
</head>
<body class="bg-light">

<?php 
include 'header_publico.php'; 
?>

<div class="container-fluid py-5">
    <div class="row">
        <!-- Menu Lateral -->
        <div class="col-md-3 col-lg-2 d-none d-md-block px-0 ps-3 mb-4">
            <?php include 'menu.php'; ?>
        </div>

        <!-- Conteúdo Principal -->
        <main class="col-md-9 ms-auto col-lg-10 px-md-5">
            <div class="d-flex align-items-center justify-content-between mb-5 border-bottom pb-3">
                <h2 class="fw-bold text-dark mb-0">Ouvidoria Municipal</h2>
            </div>
            
            <p class="section-desc-sac mb-4"><?php echo nl2br(htmlspecialchars($config_ouvidoria['ouvidoria_pagina_intro'])); ?></p>

            <div class="row g-4 mb-4">
                <!-- Informações de Atendimento -->
                <div class="col-lg-4">
                    <div class="card h-100 sic-card p-4">
                        <span class="sic-card-title"><i class="bi bi-headset text-primary me-2"></i>Atendimento</span>
                        <div class="card-body p-0">
                            <div class="sic-info-item">
                                <span class="sic-info-label">Responsável</span>
                                <p class="sic-info-value"><?php echo htmlspecialchars($config_ouvidoria['ouvidoria_setor']); ?></p>
                            </div>
                            <div class="sic-info-item">
                                <span class="sic-info-label">Endereço</span>
                                <p class="sic-info-value"><?php echo htmlspecialchars($config_ouvidoria['ouvidoria_endereco']); ?></p>
                            </div>
                            <div class="sic-info-item">
                                <span class="sic-info-label">E-mail</span>
                                <p class="sic-info-value"><a href="mailto:<?php echo htmlspecialchars($config_ouvidoria['ouvidoria_email']); ?>" class="text-decoration-none" style="color: var(--cor-principal);"><?php echo htmlspecialchars($config_ouvidoria['ouvidoria_email']); ?></a></p>
                            </div>
                            <div class="sic-info-item">
                                <span class="sic-info-label">Telefone</span>
                                <p class="sic-info-value"><?php echo htmlspecialchars($config_ouvidoria['ouvidoria_telefone']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="col-lg-4">
                    <div class="card h-100 sic-card p-4 bg-white">
                        <span class="sic-card-title"><i class="bi bi-pencil-fill text-success me-2"></i>Manifestar</span>
                        <div class="card-body p-0 d-grid gap-2">
                            <?php if (trim($config_ouvidoria['ouvidoria_manifestar_descricao']) !== ''): ?>
                                <p class="section-desc-sac mb-3"><?php echo nl2br(htmlspecialchars($config_ouvidoria['ouvidoria_manifestar_descricao'])); ?></p>
                            <?php endif; ?>
                            <?php foreach ($manifestar_tipos as $mt):
                                $lk = 'ouvidoria_btn_' . $mt['slug'] . '_label';
                                $ln = 'ouvidoria_btn_' . $mt['slug'] . '_link';
                                $btn_label = trim($config_ouvidoria[$lk] ?? '');
                                if ($btn_label === '') {
                                    $btn_label = $mt['default_label'];
                                }
                                $btn_href = ouvidoria_href_manifesto($base_url, $slug_pref_header, $config_ouvidoria[$ln] ?? '', $mt['tipo']);
                            ?>
                            <a href="<?php echo htmlspecialchars($btn_href); ?>" class="btn btn-outline-dynamic text-start py-2 px-3"<?php if (preg_match('#^https?://#i', $btn_href)) { echo ' target="_blank" rel="noopener noreferrer"'; } ?>><i class="bi <?php echo htmlspecialchars($mt['icon']); ?>"></i> <?php echo htmlspecialchars($btn_label); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Consulta de Protocolo -->
                <div class="col-lg-4">
                    <div class="card h-100 sic-card p-4">
                        <span class="sic-card-title"><i class="bi bi-search text-info me-2"></i>Consultar</span>
                        <div class="card-body p-0">
                            <p class="section-desc-sac mb-4"><?php echo nl2br(htmlspecialchars($config_ouvidoria['ouvidoria_consultar_descricao'])); ?></p>
                            <form action="<?php echo $base_url; ?>portal/<?php echo $slug_pref_header; ?>/consulta_protocolo.php" method="GET">
                                <div class="mb-3">
                                    <input type="text" name="protocolo" class="form-control rounded-start-pill rounded-end-pill px-4" placeholder="Ex: 2024.030.001" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-dynamic-primary rounded-pill py-2 fw-bold shadow-sm"><?php echo htmlspecialchars($config_ouvidoria['ouvidoria_consultar_botao_label']); ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Estatístico -->
            <?php if ($total_manifestacoes > 0): ?>
            <div class="card sic-card p-4">
                <div class="sic-relatorio-head d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-3 border-bottom">
                    <span class="sic-card-title"><i class="bi bi-bar-chart-fill text-dark me-2"></i>Relatório em Tempo Real</span>
                    <span class="badge bg-light text-dark fw-normal border px-3 py-2 sic-info-value">Total: <?php echo $total_manifestacoes; ?> manifestações</span>
                </div>
                <div class="card-body p-0 pt-2">
                    <?php if (trim($config_ouvidoria['ouvidoria_relatorio_descricao']) !== ''): ?>
                        <p class="section-desc-sac mb-3"><?php echo nl2br(htmlspecialchars($config_ouvidoria['ouvidoria_relatorio_descricao'])); ?></p>
                    <?php endif; ?>
                    <div class="row">
                        <?php foreach ($stats as $tipo => $quantidade): 
                            $percentual = ($quantidade / $total_manifestacoes) * 100;
                            $cor_barra = 'bg-primary'; 
                            if($tipo == 'Denúncia') $cor_barra = 'bg-danger';
                            if($tipo == 'Elogio') $cor_barra = 'bg-success';
                            if($tipo == 'Reclamação') $cor_barra = 'bg-warning';
                            if($tipo == 'Sugestão') $cor_barra = 'bg-warning-subtle';
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="d-flex justify-content-between mb-1 align-items-baseline">
                                <span class="sic-info-label mb-0"><?php echo htmlspecialchars($tipo); ?></span>
                                <span class="sic-info-value"><?php echo (int)$quantidade; ?> <span class="text-muted">(<?php echo round($percentual, 1); ?>%)</span></span>
                            </div>
                            <div class="progress" style="height: 10px; border-radius: 5px;">
                                <div class="progress-bar <?php echo $cor_barra; ?> shadow-sm" style="width: <?php echo $percentual; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3 pt-3 border-top">
                        <a href="<?php echo htmlspecialchars($href_relatorio_ver_mais); ?>" class="text-decoration-none fw-bold sic-info-value" style="color: var(--cor-principal);"<?php if (preg_match('#^https?://#i', $href_relatorio_ver_mais)) { echo ' target="_blank" rel="noopener noreferrer"'; } ?>><?php echo htmlspecialchars($config_ouvidoria['ouvidoria_relatorio_ver_mais_texto']); ?> <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php if (!empty($_GET['protocolo'])):
    $protocolo_ok = htmlspecialchars($_GET['protocolo'], ENT_QUOTES, 'UTF-8');
?>
<!-- Modal sucesso — mesmo padrão visual do e-SIC (sic.php) -->
<div class="modal fade" id="modalSucessoOuvidoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg p-3">
            <div class="modal-body text-center p-4">
                <div class="mb-3 text-success display-1">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                </div>
                <h3 class="fw-bold text-dark mb-2">Manifestação registrada!</h3>
                <p class="text-muted mb-4">Sua manifestação foi enviada com sucesso. Utilize o protocolo abaixo para acompanhar o andamento.</p>
                <div class="bg-light p-3 rounded-4 border border-dashed mb-4">
                    <span class="d-block small text-muted text-uppercase fw-bold mb-1">Número do protocolo</span>
                    <h2 class="fw-bold mb-0" style="color: var(--cor-principal);"><?php echo $protocolo_ok; ?></h2>
                </div>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-secondary rounded-pill py-2" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('modalSucessoOuvidoria');
    if (el && typeof bootstrap !== 'undefined') {
        var modal = new bootstrap.Modal(el);
        modal.show();
    }
});
</script>
<?php endif; ?>

<?php include 'footer_publico.php'; ?>
</body>
</html>