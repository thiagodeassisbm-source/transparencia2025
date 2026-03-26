<?php
require_once 'conexao.php';
$page_title = "SIC - Serviço de Informação ao Cidadão";

// Busca as configurações do SIC do banco de dados
$info_sic = [];
try {
    $stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'sic_%'");
    $info_sic = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    // Valores padrão em caso de erro
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
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<header class="page-header">
    <div class="container-fluid container-custom-padding">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                <li class="breadcrumb-item active" aria-current="page">SIC</li>
            </ol>
        </nav>
        <h1>Serviço de Informação ao Cidadão</h1>
    </div>
</header>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <?php include 'menu.php'; ?>
        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            
            <?php if (isset($_GET['protocolo'])): ?>
                <div class="alert alert-success">
                    <h4>Solicitação Enviada com Sucesso!</h4>
                    <p>Seu pedido de informação foi registrado. Anote o número do seu protocolo para acompanhar:</p>
                    <p class="h5"><strong>Protocolo:</strong> <?php echo htmlspecialchars($_GET['protocolo']); ?></p>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><h4>SIC Físico</h4></div>
                        <div class="card-body">
                            <p><strong>Setor:</strong><br><?php echo htmlspecialchars($info_sic['sic_setor']); ?></p>
                            <p><strong>Endereço:</strong><br><?php echo htmlspecialchars($info_sic['sic_endereco']); ?></p>
                            <p><strong>Responsável:</strong><br><?php echo htmlspecialchars($info_sic['sic_responsavel']); ?></p>
                            <p><strong>E-mail:</strong><br><?php echo htmlspecialchars($info_sic['sic_email']); ?></p>
                            <p><strong>Telefone:</strong><br><?php echo htmlspecialchars($info_sic['sic_telefone']); ?></p>
                            <p><strong>Horário de Funcionamento:</strong><br><?php echo htmlspecialchars($info_sic['sic_horario']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><h4>Solicitações</h4></div>
                        <div class="card-body">
                            <h5 class="card-title">Fazer uma Solicitação</h5>
                            <p>Clique no botão abaixo para preencher o formulário do seu pedido de informação.</p>
                            <a href="solicitacao_sic.php" class="btn btn-success mb-4"><i class="bi bi-plus-circle-fill"></i> Fazer uma Solicitação</a>
                            <hr>
                            <h5 class="card-title mt-4">Acompanhar Solicitação</h5>
                            <p>Consulte o andamento do seu pedido usando o número de protocolo fornecido.</p>
                            <form action="consulta_sic.php" method="GET" class="row g-3">
                                <div class="col-md-8">
                                    <label for="protocolo" class="visually-hidden">Protocolo</label>
                                    <input type="text" name="protocolo" id="protocolo" class="form-control" placeholder="Digite seu protocolo" required>
                                </div>
                                <div class="col-md-4 d-grid">
                                    <button type="submit" class="btn btn-primary">Acompanhar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header"><h4>Legislação e Dados Sigilosos</h4></div>
                        <div class="card-body">
                            <p class="fw-bold mb-1">Legislação</p>
                            <p class="text-muted small">Conheça as leis que garantem ao cidadão o direito de acesso às informações públicas.</p>
                            <div class="list-group">
                                <a href="https://www.planalto.gov.br/ccivil_03/_ato2011-2014/2011/lei/l12527.htm" target="_blank" class="list-group-item list-group-item-action">Lei Federal nº 12.527/2011</a>
                            </div>

                            <p class="fw-bold mb-1 mt-3">Regulamentação Municipal da LAI</p>
                            <div class="list-group">
                                <a href="https://itumbiara.go.gov.br/wp-content/uploads/2021/08/L5080-Lei-da-Transparencia-1.pdf" target="_blank" class="list-group-item list-group-item-action">Lei nº 5.080/2021</a>
                            </div>

                            <hr class="my-4">

                            <p class="fw-bold mb-1">Dados Sigilosos</p>
                            <p class="text-muted small">Acesse os dados que foram classificados como sigilosos e os perderam a classificação de sigilo.</p>
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

<footer class="text-center p-3 mt-4">
    </footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>