<?php
require_once 'conexao.php';
$page_title = "Fazer Solicitação ao SIC";

// Detecta o ID da prefeitura para associar o pedido no futuro (se necessário)
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
// O header detecta o pref_slug via URL (/portal/catalao/solicitacao_sic.php)
include 'header_publico.php'; 
?>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <!-- Menu Lateral -->
        <div class="col-md-3 col-lg-2 d-none d-md-block p-0 mb-4">
            <?php include 'menu.php'; ?>
        </div>

        <!-- Conteúdo Principal -->
        <main class="col-md-9 ms-auto col-lg-10 px-md-4">
            <h2 class="mb-4 fw-bold text-dark border-bottom pb-2">Pedido de Acesso à Informação (e-SIC)</h2>
            
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-success me-2"></i> Formulário de Solicitação</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small">Preencha os campos abaixo para registrar seu Pedido de Acesso à Informação (LAI).</p>
                    
                    <form action="processar_sic.php" method="POST">
                        <!-- Campo oculto para vincular a prefeitura ao pedido se o processador suportar -->
                        <input type="hidden" name="pref_id" value="<?php echo $id_pref_header; ?>">
                        <input type="hidden" name="pref_slug" value="<?php echo $slug_pref_header; ?>">

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="nome_solicitante" class="form-label fw-bold text-muted small mb-1">Cidadão / Nome Completo*</label>
                                <input type="text" name="nome_solicitante" id="nome_solicitante" class="form-control rounded-3" placeholder="Digite seu nome completo" required>
                            </div>
                            
                            <div class="col-md-6 mt-3">
                                <label for="tipo_documento" class="form-label fw-bold text-muted small mb-1">Tipo de Documento*</label>
                                <select name="tipo_documento" id="tipo_documento" class="form-select rounded-3" required>
                                    <option value="">-- Selecione --</option>
                                    <option value="CPF">CPF</option>
                                    <option value="CNPJ">CNPJ</option>
                                    <option value="RG">RG</option>
                                    <option value="Registro Profissional">Registro Profissional</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mt-3">
                                <label for="numero_documento" class="form-label fw-bold text-muted small mb-1">Número do Documento*</label>
                                <input type="text" name="numero_documento" id="numero_documento" class="form-control rounded-3" placeholder="000.000.000-00" required>
                            </div>

                            <div class="col-md-6 mt-3">
                                <label for="email" class="form-label fw-bold text-muted small mb-1">E-mail para Retorno (opcional)</label>
                                <input type="email" name="email" id="email" class="form-control rounded-3" placeholder="seunome@email.com">
                            </div>

                            <div class="col-md-6 mt-3">
                                <label for="telefone" class="form-label fw-bold text-muted small mb-1">WhatsApp / Telefone (opcional)</label>
                                <input type="text" name="telefone" id="telefone" class="form-control rounded-3" placeholder="(00) 0 0000-0000">
                            </div>

                            <div class="col-12 mt-4">
                                <label for="descricao_pedido" class="form-label fw-bold text-muted small mb-1">Descrição Detalhada do Pedido*</label>
                                <textarea name="descricao_pedido" id="descricao_pedido" class="form-control rounded-3" rows="6" placeholder="Descreva de forma clara e objetiva a informação que você solicita..." required></textarea>
                            </div>

                            <div class="col-12 mt-5 text-end border-top pt-4">
                                <a href="portal/<?php echo $slug_pref_header; ?>/sic.php" class="btn btn-light rounded-pill px-4 me-2">Cancelar</a>
                                <button type="submit" class="btn btn-dynamic-primary btn-lg rounded-pill px-5 fw-bold shadow-sm">
                                    <i class="bi bi-send-fill me-2 rotate-45"></i> Enviar Pedido ao SIC
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="alert alert-light border-0 shadow-sm rounded-4 p-4" role="alert">
                <div class="d-flex">
                    <i class="bi bi-info-circle-fill text-primary fs-3 me-3"></i>
                    <div>
                        <h6 class="alert-heading fw-bold">Compromisso com a Transparência</h6>
                        <p class="mb-0 text-muted small">Em atendimento à <strong>Lei Federal Nº 12.527/2011 (LAI)</strong>, sua solicitação será processada dentro dos prazos legais. É essencial fornecer dados válidos para facilitar a localização da informação requerida pelo órgão responsável.</p>
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

<style>
.rotate-45 { display: inline-block; transform: rotate(-45deg); }
.form-control:focus, .form-select:focus {
    border-color: var(--cor-principal);
    box-shadow: 0 0 0 0.25rem rgba(var(--cor-principal-rgb), 0.1);
}
</style>
</body>
</html>