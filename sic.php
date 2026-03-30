<?php
require_once 'conexao.php';

// O bootstrap_portal.php já deve ter sido carregado se viermos via rewrite, 
// senão o header_publico se encarrega de detectar.

// Busca as configurações do SIC do banco de dados para a prefeitura ativa
$info_sic = [];
try {
    $id_pref = $id_prefeitura_ativa ?? 0;
    $stmt = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE id_prefeitura = ? AND chave LIKE 'sic_%'");
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
</head>
<body class="bg-light">

<?php 
// O header_publico.php gerencia o slug_pref_header
include 'header_publico.php'; 
?>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <!-- Menu Lateral -->
        <div class="col-md-3 col-lg-2 d-md-block p-0 mb-4">
            <?php include 'menu.php'; ?>
        </div>

        <!-- Conteúdo Principal -->
        <main class="col-md-9 ms-auto col-lg-10 px-md-4">
            <h2 class="mb-4 fw-bold text-dark border-bottom pb-2">Serviço de Informação ao Cidadão (SIC)</h2>
            
            <?php if (isset($_GET['protocolo'])): ?>
                <div class="alert alert-success shadow-sm border-0 rounded-4 p-4 mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill fs-2 me-3 text-success"></i>
                        <div>
                            <h5 class="mb-1 fw-bold">Solicitação Enviada com Sucesso!</h5>
                            <p class="mb-0">Seu pedido foi registrado. Guarde seu protocolo: <strong><?php echo htmlspecialchars($_GET['protocolo']); ?></strong></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- SIC Físico -->
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-white py-3 border-0">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-geo-alt-fill text-primary me-2"></i> SIC Físico</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="small text-muted fw-bold text-uppercase">Setor:</label>
                                <p class="mb-0 fw-medium"><?php echo htmlspecialchars($info_sic['sic_setor']); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted fw-bold text-uppercase">Endereço:</label>
                                <p class="mb-0 fw-medium"><?php echo htmlspecialchars($info_sic['sic_endereco']); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted fw-bold text-uppercase">Responsável:</label>
                                <p class="mb-0 fw-medium"><?php echo htmlspecialchars($info_sic['sic_responsavel']); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted fw-bold text-uppercase">E-mail:</label>
                                <p class="mb-0 fw-medium text-primary"><?php echo htmlspecialchars($info_sic['sic_email']); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted fw-bold text-uppercase">Telefone:</label>
                                <p class="mb-0 fw-medium"><?php echo htmlspecialchars($info_sic['sic_telefone']); ?></p>
                            </div>
                            <div>
                                <label class="small text-muted fw-bold text-uppercase">Horário:</label>
                                <p class="mb-0 fw-medium"><?php echo htmlspecialchars($info_sic['sic_horario']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Solicitações Online -->
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-white py-3 border-0">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-success me-2"></i> Solicitações</h5>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="mb-4">
                                <h6 class="fw-bold">Fazer uma Solicitação</h6>
                                <p class="text-muted small">Clique no botão abaixo para preencher o formulário do seu pedido de informação online.</p>
                                <a href="<?php echo $base_url; ?>portal/<?php echo $slug_pref_header; ?>/solicitacao_sic.php" class="btn btn-dynamic-primary btn-lg w-100 rounded-pill py-2 shadow-sm">
                                    <i class="bi bi-plus-circle me-2"></i> Iniciar Solicitação
                                </a>
                            </div>
                            
                            <div class="mt-auto pt-4 border-top">
                                <h6 class="fw-bold">Acompanhar Pedido</h6>
                                <p class="text-muted small">Consulte o andamento de um pedido existente usando seu protocolo.</p>
                                <form action="<?php echo $base_url; ?>portal/<?php echo $slug_pref_header; ?>/consulta_sic.php" method="GET">
                                    <div class="input-group mb-2">
                                        <input type="text" name="protocolo" class="form-control rounded-start-pill ps-3" placeholder="Número do Protocolo" required>
                                        <button type="submit" class="btn btn-primary rounded-end-pill px-3">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Legislação -->
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-white py-3 border-0">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-journal-text text-warning me-2"></i> Legislação e Dados</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <p class="fw-bold mb-1 small text-uppercase text-muted">Legislação Federal</p>
                                <a href="https://www.planalto.gov.br/ccivil_03/_ato2011-2014/2011/lei/l12527.htm" target="_blank" class="text-decoration-none d-block p-2 bg-light rounded text-dark hover-shadow-sm transition">
                                    <i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i> Lei Federal nº 12.527/2011 (LAI)
                                </a>
                            </div>

                            <div class="mb-4">
                                <p class="fw-bold mb-1 small text-uppercase text-muted">Regulamentação Municipal</p>
                                <a href="#" class="text-decoration-none d-block p-2 bg-light rounded text-dark opacity-50">
                                    <i class="bi bi-file-earmark-pdf-fill text-primary me-2"></i> Lei Municipal da LAI
                                </a>
                            </div>

                            <div class="pt-3 border-top">
                                <p class="fw-bold mb-1 small text-uppercase text-muted">Dados Sigilosos</p>
                                <div class="list-group list-group-flush small">
                                    <a href="#" class="list-group-item list-group-item-action px-0 border-0 text-muted italic">Informações Classificadas</a>
                                    <a href="#" class="list-group-item list-group-item-action px-0 border-0 text-muted italic">Informações Desclassificadas</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'footer_publico.php'; ?>

</body>
</html>