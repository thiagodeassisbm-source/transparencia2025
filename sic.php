<?php
require_once 'conexao.php';
$page_title = "SIC - Serviço de Informação ao Cidadão";

// Busca as configurações do SIC do banco de dados
$info_sic = [];
try {
    $stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'sic_%'");
    $info_sic = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $info_sic = [
        'sic_setor' => 'Não informado', 'sic_endereco' => 'Não informado',
        'sic_responsavel' => 'Não informado', 'sic_email' => 'Não informado',
        'sic_telefone' => 'Não informado', 'sic_horario' => 'Não informado'
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <base href="<?php echo $base_url; ?>">
    <title>SIC - Cadastro e Consulta - Portal da Transparência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light">

<?php 
$page_title = "SIC"; 
include 'header_publico.php'; 
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'menu.php'; ?>
        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            <h2 class="mb-4 fw-bold">Serviço de Informação ao Cidadão (SIC)</h2>
            
            <?php if (isset($_GET['protocolo'])): ?>
                <div class="alert alert-success shadow-sm">
                    <h4><i class="bi bi-check-circle-fill me-2"></i>Solicitação Enviada com Sucesso!</h4>
                    <p>Seu pedido de informação foi registrado. Anote o número do seu protocolo para acompanhar:</p>
                    <p class="h5"><strong>Protocolo:</strong> <?php echo htmlspecialchars($_GET['protocolo']); ?></p>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-white py-3"><h4><i class="bi bi-geo-alt-fill text-primary me-2"></i>SIC Físico</h4></div>
                        <div class="card-body">
                            <p><strong>Setor:</strong><br><?php echo htmlspecialchars($info_sic['sic_setor'] ?? 'Não informado'); ?></p>
                            <p><strong>Endereço:</strong><br><?php echo htmlspecialchars($info_sic['sic_endereco'] ?? 'Não informado'); ?></p>
                            <p><strong>Responsável:</strong><br><?php echo htmlspecialchars($info_sic['sic_responsavel'] ?? 'Não informado'); ?></p>
                            <p><strong>E-mail:</strong><br><?php echo htmlspecialchars($info_sic['sic_email'] ?? 'sic@municipio.gov.br'); ?></p>
                            <p><strong>Telefone:</strong><br><?php echo htmlspecialchars($info_sic['sic_telefone'] ?? 'Não informado'); ?></p>
                            <p><strong>Horário de Funcionamento:</strong><br><?php echo htmlspecialchars($info_sic['sic_horario'] ?? 'Não informado'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-white py-3"><h4><i class="bi bi-pencil-square text-success me-2"></i>Solicitações</h4></div>
                        <div class="card-body">
                            <h5 class="card-title fw-bold">Fazer uma Solicitação</h5>
                            <p>Clique no botão abaixo para preencher o formulário do seu pedido de informação online.</p>
                            <a href="solicitacao_sic.php" class="btn btn-dynamic-primary btn-lg w-100 mb-4 shadow-sm"><i class="bi bi-plus-circle-fill me-2"></i> Iniciar Solicitação</a>
                            <hr>
                            <h5 class="card-title mt-4 fw-bold">Acompanhar Pedido</h5>
                            <p>Consulte o andamento de um pedido existente usando seu protocolo.</p>
                            <form action="consulta_sic.php" method="GET" class="row g-2">
                                <div class="col-12">
                                    <input type="text" name="protocolo" id="protocolo" class="form-control" placeholder="Número do Protocolo" required>
                                </div>
                                <div class="col-12 d-grid mt-2">
                                    <button type="submit" class="btn btn-primary">Buscar Acompanhamento</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-white py-3"><h4><i class="bi bi-journal-text text-warning me-2"></i>Legislação e Dados</h4></div>
                        <div class="card-body">
                            <p class="fw-bold mb-1">Legislação Federal</p>
                            <p class="text-muted small">Lei de Acesso à Informação (LAI).</p>
                            <div class="list-group mb-4">
                                <a href="https://www.planalto.gov.br/ccivil_03/_ato2011-2014/2011/lei/l12527.htm" target="_blank" class="list-group-item list-group-item-action"><i class="bi bi-file-earmark-pdf me-2 text-danger"></i>Lei Federal nº 12.527/2011</a>
                            </div>

                            <p class="fw-bold mb-1">Regulamentação Municipal</p>
                            <div class="list-group mb-4">
                                <a href="#" target="_blank" class="list-group-item list-group-item-action"><i class="bi bi-file-earmark-pdf me-2 text-primary"></i>Lei Municipal da LAI</a>
                            </div>

                            <hr class="my-4">

                            <p class="fw-bold mb-1">Dados Sigilosos</p>
                            <p class="text-muted small">Informações com restrição de acesso temporário.</p>
                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action">Informações Classificadas</a>
                                <a href="#" class="list-group-item list-group-item-action">Informações Desclassificadas</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php 
$custom_container_class = "container-custom-padding";
include 'footer_publico.php'; 
?>
</body>
</html>