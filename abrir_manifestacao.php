<?php
require_once 'conexao.php';
require_once 'bootstrap_portal.php';
$tipo_manifestacao = $_GET['tipo'] ?? 'Sugestão';
$page_title = "Abrir Manifestação - " . $tipo_manifestacao;

// O header identifica a prefeitura pelo rewrite: /portal/{slug}/abrir_manifestacao.php?pref_slug=...
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
            <h2 class="mb-4 fw-bold text-dark border-bottom pb-2">Nova Manifestação: <span class="text-primary"><?php echo htmlspecialchars($tipo_manifestacao); ?></span></h2>
            
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-chat-right-text-fill text-success me-2"></i> Formulário de Ouvidoria</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small">Sua manifestação será analisada pela equipe competente. Você receberá um protocolo para acompanhar o andamento.</p>
                    
                    <form action="processar_ouvidoria.php" method="POST">
                        <input type="hidden" name="tipo_manifestacao" value="<?php echo htmlspecialchars($tipo_manifestacao); ?>">
                        <input type="hidden" name="pref_id" value="<?php echo (int)$id_pref_header; ?>">
                        <input type="hidden" name="pref_slug" value="<?php echo htmlspecialchars($slug_pref_header, ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="nome_cidadao" class="form-label fw-bold text-muted small mb-1">Cidadão / Nome Completo*</label>
                                <input type="text" name="nome_cidadao" id="nome_cidadao" class="form-control rounded-3" placeholder="Seu nome completo" required>
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
                                <label for="assunto" class="form-label fw-bold text-muted small mb-1">Assunto / Tópico*</label>
                                <input type="text" name="assunto" id="assunto" class="form-control rounded-3" placeholder="Ex: Iluminação pública, Atendimento nas unidades de saúde..." required>
                            </div>

                            <div class="col-12 mt-3">
                                <label for="descricao" class="form-label fw-bold text-muted small mb-1">Descrição Detalhada*</label>
                                <textarea name="descricao" id="descricao" class="form-control rounded-3" rows="8" placeholder="Descreva sua manifestação de forma clara e detalhada..." required></textarea>
                            </div>

                            <div class="col-12 mt-5 text-end border-top pt-4">
                                <a href="portal/<?php echo $slug_pref_header; ?>/ouvidoria.php" class="btn btn-light rounded-pill px-4 me-2">Cancelar</a>
                                <button type="submit" class="btn btn-dynamic-primary btn-lg rounded-pill px-5 fw-bold shadow-sm">
                                    <i class="bi bi-send-check-fill me-2"></i> Enviar Manifestação
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="alert alert-light border-0 shadow-sm rounded-4 p-4" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-shield-check text-success fs-3 me-3"></i>
                    <div>
                        <h6 class="alert-heading fw-bold mb-1">Privacidade Garantida</h6>
                        <p class="mb-0 text-muted small">Suas informações de contato são utilizadas apenas para fins de retorno institucional e não são expostas publicamente no portal. Sua contribuição ajuda a melhorar os serviços públicos de <?php echo $nome_pref_header; ?>.</p>
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
