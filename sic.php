<?php
require_once 'conexao.php';
require_once 'bootstrap_portal.php';

// Busca as configurações do SIC do banco de dados para a prefeitura ativa
$info_sic = [];
try {
    $id_pref = $id_prefeitura_ativa ?? 0;
    $stmt = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE (id_prefeitura = ? OR id_prefeitura = 0) AND chave LIKE 'sic_%' ORDER BY id_prefeitura ASC");
    $stmt->execute([$id_pref]);
    $info_sic = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
catch (Exception $e) {
    $info_sic = [];
}

// Fallbacks para o SIC
$info_sic['sic_setor'] = $info_sic['sic_setor'] ?? 'Controladoria Municipal';
$info_sic['sic_endereco'] = $info_sic['sic_endereco'] ?? 'Avenida das Nações, S/N - Centro';
$info_sic['sic_responsavel'] = $info_sic['sic_responsavel'] ?? 'João da Silva';
$info_sic['sic_email'] = $info_sic['sic_email'] ?? 'sic@municipio.gov.br';
$info_sic['sic_telefone'] = $info_sic['sic_telefone'] ?? '(62) 3123-4567';
$info_sic['sic_horario'] = $info_sic['sic_horario'] ?? 'Segunda a Sexta, das 08h às 17h';

$page_title = "Serviço de Informação ao Cidadão (SIC)";
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
        .sic-card { border-radius: 15px !important; border: none !important; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; }
        .sic-card-title { font-size: 24px !important; font-weight: 700 !important; color: #1a1a1a; margin-bottom: 25px; display: block; }
        
        /* Layout do SIC Físico */
        .sic-info-item { border-bottom: 1px solid #f0f0f0; padding: 15px 0; }
        .sic-info-item:last-child { border-bottom: none; }
        .sic-info-label { display: block; font-weight: 700; color: #333; font-size: 14px; margin-bottom: 5px; }
        .sic-info-value { font-size: 15px; color: #555; margin-bottom: 0; font-weight: 400; }

        /* Botões Solicitações */
        .btn-sac { 
            display: flex; 
            align-items: center; 
            width: 100%; 
            background: #fff; 
            border: 1px solid #e0e0e0; 
            border-radius: 12px; 
            margin-bottom: 20px; 
            text-decoration: none; 
            color: #333; 
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .btn-sac:hover { border-color: var(--cor-principal); transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.08); color: #1a1a1a; }
        .btn-sac-icon { 
            background: var(--cor-principal); 
            color: #fff; 
            padding: 22px 25px; 
            font-size: 30px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
        }
        .btn-sac-text { padding: 0 25px; font-weight: 700; font-size: 17px; }

        /* Box Informativo */
        .info-box-sac { 
            background: #fdfdfd; 
            border: 1px solid #f0f0f0;
            border-radius: 12px; 
            padding: 20px; 
            display: flex; 
            align-items: flex-start; 
            gap: 15px;
            margin-top: 15px;
        }
        .info-box-sac i { font-size: 26px; color: #ced4da; }
        .info-box-sac p { margin: 0; font-size: 13px; color: #6c757d; line-height: 1.6; }
        .info-box-sac strong { color: var(--cor-principal); }

        /* Listas Legislação */
        .legis-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #fff;
            border: 1px solid #eeeeee;
            border-radius: 12px;
            text-decoration: none;
            color: #444;
            margin-bottom: 15px;
            transition: all 0.2s;
        }
        .legis-item:hover { border-color: var(--cor-principal); background: #fffcf8; color: #1a1a1a; }
        .legis-item span { font-size: 15px; font-weight: 400; }
        .legis-item i.bi-chevron-right { font-size: 14px; color: #bbb; }
        
        .section-title-sac { font-weight: 700; font-size: 18px; color: #222; margin-top: 30px; margin-bottom: 12px; display: block; }
        .section-desc-sac { font-size: 13px; color: #777; margin-bottom: 20px; display: block; line-height: 1.5; }
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
            <div class="d-flex align-items-center justify-content-between mb-5 border-bottom pb-3">
                <h2 class="fw-bold text-dark mb-0">Serviço de Informação ao Cidadão (SIC)</h2>
            </div>
            
            <div class="row g-5">
                <!-- SIC Físico -->
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 sic-card p-4">
                        <span class="sic-card-title">SIC Físico</span>
                        <div class="card-body p-0">
                            <div class="sic-info-item">
                                <span class="sic-info-label">Setor</span>
                                <p class="sic-info-value"><?php echo htmlspecialchars($info_sic['sic_setor']); ?></p>
                            </div>
                            <div class="sic-info-item">
                                <span class="sic-info-label">Endereço</span>
                                <p class="sic-info-value"><?php echo htmlspecialchars($info_sic['sic_endereco']); ?></p>
                            </div>
                            <div class="sic-info-item">
                                <span class="sic-info-label">Responsável</span>
                                <p class="sic-info-value"><?php echo htmlspecialchars($info_sic['sic_responsavel']); ?></p>
                            </div>
                            <div class="sic-info-item">
                                <span class="sic-info-label">E-mail</span>
                                <p class="sic-info-value"><?php echo htmlspecialchars($info_sic['sic_email']); ?></p>
                            </div>
                            <div class="sic-info-item">
                                <span class="sic-info-label">Telefone</span>
                                <p class="sic-info-value"><?php echo htmlspecialchars($info_sic['sic_telefone']); ?></p>
                            </div>
                            <div class="sic-info-item">
                                <span class="sic-info-label">Horário de Funcionamento</span>
                                <p class="sic-info-value"><?php echo htmlspecialchars($info_sic['sic_horario']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Solicitações Online -->
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 sic-card p-4 shadow-sm border-0">
                        <span class="sic-card-title text-start">Solicitações</span>
                        <div class="card-body p-0">
                            <p class="section-desc-sac"><?php echo nl2br(htmlspecialchars($info_sic['sic_solicitacoes_descricao'] ?? 'Encaminhe aqui suas solicitações de acesso à informação e acompanhe pedidos em andamento.')); ?></p>
                            
                            <a href="<?php echo $base_url; ?>portal/<?php echo $slug_pref_header; ?>/solicitacao_sic.php" class="btn-sac">
                                <div class="btn-sac-icon"><i class="bi bi-file-earmark-plus"></i></div>
                                <div class="btn-sac-text">Fazer uma solicitação</div>
                            </a>

                            <a href="#" class="btn-sac" data-bs-toggle="modal" data-bs-target="#modalAcompanhar">
                                <div class="btn-sac-icon"><i class="bi bi-search"></i></div>
                                <div class="btn-sac-text">Acompanhar solicitação</div>
                            </a>

                            <div class="info-box-sac shadow-sm">
                                <i class="bi bi-info-circle"></i>
                                <p>
                                    Caso prefira fazer sua solicitação pessoalmente, 
                                    <?php if(!empty($info_sic['sic_formulario_pedido_pdf'])): ?>
                                        <a href="<?php echo $base_url . $info_sic['sic_formulario_pedido_pdf']; ?>" target="_blank" class="fw-bold text-decoration-none" style="color: var(--cor-principal);">baixe o formulário</a>
                                    <?php else: ?>
                                        <strong>baixe o formulário</strong>
                                    <?php endif; ?>
                                    de requerimento de informações e entregue no endereço do SIC Físico informado.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Legislação -->
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 sic-card p-4 shadow-sm border-0">
                        <span class="sic-card-title">Legislação</span>
                        <div class="card-body p-0">
                            <p class="section-desc-sac"><?php echo nl2br(htmlspecialchars($info_sic['sic_legislacao_descricao'] ?? 'Conheça as leis que garantem ao cidadão o direito constitucional de acesso às informações públicas.')); ?></p>
                            
                            <!-- Lei Federal -->
                            <?php 
                                $link_federal = !empty($info_sic['sic_legislacao_federal_pdf']) ? $base_url . $info_sic['sic_legislacao_federal_pdf'] : "https://www.planalto.gov.br/ccivil_03/_ato2011-2014/2011/lei/l12527.htm";
                            ?>
                            <a href="<?php echo $link_federal; ?>" target="_blank" class="legis-item shadow-sm">
                                <span><?php echo htmlspecialchars($info_sic['sic_legislacao_federal_titulo'] ?? 'Lei Federal nº 12.527/2011'); ?></span>
                                <i class="bi bi-chevron-right"></i>
                            </a>

                            <!-- Regulamentação Municipal -->
                            <span class="section-title-sac">Regulamentação Municipal da LAI</span>
                            <?php if(!empty($info_sic['sic_legislacao_municipal_titulo'])): ?>
                                <?php 
                                    $link_municipal = !empty($info_sic['sic_legislacao_municipal_pdf']) ? $base_url . $info_sic['sic_legislacao_municipal_pdf'] : "#";
                                ?>
                                <a href="<?php echo $link_municipal; ?>" target="_blank" class="legis-item shadow-sm">
                                    <span><?php echo htmlspecialchars($info_sic['sic_legislacao_municipal_titulo']); ?></span>
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            <?php else: ?>
                                <p class="text-muted small italic">Nenhum decreto municipal cadastrado.</p>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Acompanhar Pedido -->
<div class="modal fade" id="modalAcompanhar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Acompanhar Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">Insira o número do protocolo fornecido no momento da solicitação.</p>
                <form action="<?php echo $base_url; ?>portal/<?php echo $slug_pref_header; ?>/sic_consulta.php" method="GET">
                    <div class="input-group mb-3">
                        <input type="text" name="protocolo" class="form-control rounded-start-pill ps-3" placeholder="Ex: 2024123456" required>
                        <button type="submit" class="btn btn-primary rounded-end-pill px-4">Consultar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sucesso Pedido -->
<?php if (isset($_GET['protocolo'])): ?>
<div class="modal fade" id="modalSucesso" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg p-3">
            <div class="modal-body text-center p-4">
                <div class="mb-3 text-success display-1">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h3 class="fw-bold text-dark mb-2">Pedido Enviado!</h3>
                <p class="text-muted mb-4">Sua solicitação foi registrada com sucesso. Utilize o protocolo abaixo para acompanhar o andamento.</p>
                
                <div class="bg-light p-3 rounded-4 border border-dashed mb-4">
                    <span class="d-block small text-muted text-uppercase fw-bold mb-1">Número do Protocolo</span>
                    <h2 class="fw-bold text-primary mb-0"><?php echo htmlspecialchars($_GET['protocolo']); ?></h2>
                </div>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-secondary rounded-pill py-2" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var myModal = new bootstrap.Modal(document.getElementById('modalSucesso'));
        myModal.show();
    });
</script>
<?php endif; ?>

<?php include 'footer_publico.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>