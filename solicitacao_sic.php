<?php
require_once 'conexao.php';
$page_title = "Fazer Solicitação ao SIC";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - Portal da Transparência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="accessibility-bar py-1">
    <div class="container-fluid container-custom-padding">
        <div class="d-flex justify-content-end align-items-center">
            <span class="me-3 fw-bold d-none d-md-inline"><i class="bi bi-universal-access"></i> ACESSIBILIDADE</span>
            <button id="font-increase" class="btn btn-sm btn-outline-dark me-1" title="Aumentar Fonte">A+</button>
            <button id="font-reset" class="btn btn-sm btn-outline-dark me-1" title="Fonte Padrão">A</button>
            <button id="font-decrease" class="btn btn-sm btn-outline-dark me-2" title="Diminuir Fonte">A-</button>
            <button id="contrast-toggle" class="btn btn-sm btn-outline-dark" title="Alto Contraste"><i class="bi bi-circle-half"></i></button>
        </div>
    </div>
</div>

<header class="page-header">
    <div class="container-fluid container-custom-padding">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                <li class="breadcrumb-item"><a href="sic.php">SIC</a></li>
                <li class="breadcrumb-item active" aria-current="page">Fazer Solicitação</li>
            </ol>
        </nav>
        <h1>Pedido de Acesso à Informação</h1>
    </div>
</header>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <?php include 'menu.php'; ?>
        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            <div class="card">
                <div class="card-header"><h4>Formulário de Solicitação</h4></div>
                <div class="card-body">
                    <p class="text-muted">Preencha os campos abaixo para registrar seu Pedido de Acesso à Informação (LAI).</p>
                    <form action="processar_sic.php" method="POST">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="nome_solicitante" class="form-label">Nome Completo*</label>
                                <input type="text" name="nome_solicitante" id="nome_solicitante" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tipo_documento" class="form-label">Tipo de Documento*</label>
                                <select name="tipo_documento" id="tipo_documento" class="form-select" required>
                                    <option value="">-- Selecione --</option>
                                    <option value="CPF">CPF</option>
                                    <option value="CNPJ">CNPJ</option>
                                    <option value="RG">RG</option>
                                    <option value="Registro Profissional">Registro Profissional</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="numero_documento" class="form-label">Número do Documento*</label>
                                <input type="text" name="numero_documento" id="numero_documento" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">E-mail para Contato (opcional)</label>
                                <input type="email" name="email" id="email" class="form-control" placeholder="seunome@email.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telefone" class="form-label">Telefone para Contato (opcional)</label>
                                <input type="text" name="telefone" id="telefone" class="form-control" placeholder="(00) 00000-0000">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="descricao_pedido" class="form-label">Descrição do Pedido de Informação*</label>
                                <textarea name="descricao_pedido" id="descricao_pedido" class="form-control" rows="8" placeholder="Descreva de forma clara e objetiva a informação que você solicita." required></textarea>
                            </div>
                        </div>
                        <a href="sic.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Enviar Pedido</button>
                    </form>
                </div>
            </div>
            <div class="alert alert-light border mt-4" role="alert">
                <h5 class="alert-heading small text-uppercase"><i class="bi bi-info-circle-fill"></i> Nota Importante</h5>
                <p class="mb-0 small">Em atendimento ao Decreto Federal Nº 7.724/2012, é necessário que o requerente informe seu nome, número de um documento válido e que especifique de forma clara e precisa a informação requerida.</p>
            </div>
        </main>
    </div>
</div>

<footer class="text-center p-3 mt-4">
    <div class="container-fluid container-custom-padding">
        <div class="d-flex justify-content-between align-items-center" style="font-size: 14px;">
            <div>&copy; <?php echo date('Y'); ?> - Todos os direitos reservados.</div>
            <div>
                Desenvolvido por |
                <a href="https://www.upgyn.com.br" target="_blank" class="ms-2">
                    <img src="imagens/logo-up.png" alt="UPGYN" style="height: 40px; vertical-align: middle;">
                </a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const body = document.body;
    const btnIncrease = document.getElementById('font-increase');
    const btnReset = document.getElementById('font-reset');
    const btnDecrease = document.getElementById('font-decrease');
    const btnContrast = document.getElementById('contrast-toggle');
    let currentFontSize = parseInt(localStorage.getItem('fontSize') || 16);
    let highContrast = localStorage.getItem('highContrast') === 'true';
    function applySettings() {
        body.style.fontSize = currentFontSize + 'px';
        if (highContrast) { body.classList.add('high-contrast'); } 
        else { body.classList.remove('high-contrast'); }
    }
    if(btnIncrease) { btnIncrease.addEventListener('click', function() { if (currentFontSize < 24) { currentFontSize += 2; localStorage.setItem('fontSize', currentFontSize); applySettings(); } }); }
    if(btnDecrease) { btnDecrease.addEventListener('click', function() { if (currentFontSize > 12) { currentFontSize -= 2; localStorage.setItem('fontSize', currentFontSize); applySettings(); } }); }
    if(btnReset) { btnReset.addEventListener('click', function() { currentFontSize = 16; localStorage.removeItem('fontSize'); applySettings(); }); }
    if(btnContrast) { btnContrast.addEventListener('click', function() { highContrast = !highContrast; localStorage.setItem('highContrast', highContrast); applySettings(); }); }
    applySettings();
});
</script>
</body>
</html>