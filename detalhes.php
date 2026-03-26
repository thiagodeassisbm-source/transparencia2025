<?php
require_once 'conexao.php';

$registro_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$registro_id) { die("Registro não especificado."); }

// Busca os dados da seção a qual o registro pertence
$stmt_info = $pdo->prepare(
    "SELECT p.nome as nome_secao, p.slug 
     FROM registros r 
     JOIN portais p ON r.id_portal = p.id 
     WHERE r.id = ?");
$stmt_info->execute([$registro_id]);
$secao_info = $stmt_info->fetch();
if (!$secao_info) { die("Registro não encontrado ou inválido."); }

// Busca todos os campos e valores para este registro específico
$stmt_detalhes = $pdo->prepare(
    "SELECT cp.nome_campo, cp.tipo_campo, vr.valor 
     FROM valores_registros vr
     JOIN campos_portal cp ON vr.id_campo = cp.id
     WHERE vr.id_registro = ?
     ORDER BY cp.ordem, cp.id"
);
$stmt_detalhes->execute([$registro_id]);
$detalhes = $stmt_detalhes->fetchAll(PDO::FETCH_ASSOC);

// A lógica para separar os anexos continua necessária para os botões
$anexos = [];
foreach ($detalhes as $detalhe) {
    if ($detalhe['tipo_campo'] === 'anexo' && !empty($detalhe['valor'])) {
        $anexos[] = $detalhe;
    }
}

$page_title = "Detalhes do Documento";
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

<header class="page-header">
    <div class="container-fluid container-custom-padding">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                        <li class="breadcrumb-item"><a href="portal.php?slug=<?php echo htmlspecialchars($secao_info['slug']); ?>"><?php echo htmlspecialchars($secao_info['nome_secao']); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Detalhes</li>
                    </ol>
                </nav>
                <h1><?php echo htmlspecialchars($page_title); ?></h1>
            </div>
            <div class="accessibility-bar-header d-flex align-items-center pt-2">
                <span class="me-2 d-none d-lg-inline text-white" style="font-size: 0.8rem;">ACESSIBILIDADE</span>
                <button id="font-increase" class="btn btn-sm btn-outline-light me-1" title="Aumentar Fonte">A+</button>
                <button id="font-reset" class="btn btn-sm btn-outline-light me-1" title="Fonte Padrão">A</button>
                <button id="font-decrease" class="btn btn-sm btn-outline-light me-2" title="Diminuir Fonte">A-</button>
                <button id="contrast-toggle" class="btn btn-sm btn-outline-light" title="Alto Contraste"><i class="bi bi-circle-half"></i></button>
            </div>
        </div>
    </div>
</header>

<div class="container-fluid">
    <div class="row">
        <?php include 'menu.php'; ?>
        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            
            <div class="card d-flex flex-column" style="min-height: 70vh;">
                <div class="card-body flex-grow-1">
                    <h4 class="mb-4"><?php echo htmlspecialchars($secao_info['nome_secao']); ?></h4>
                    <hr>
                    <div class="row">
                        <?php foreach ($detalhes as $detalhe): ?>
                            <?php if ($detalhe['tipo_campo'] !== 'anexo'): ?>
                                <?php
                                $col_class = ($detalhe['tipo_campo'] === 'textarea') ? 'col-md-12' : 'col-md-6';
                                ?>
                                <div class="<?php echo $col_class; ?> mb-3">
                                    <small class="text-muted text-uppercase fw-bold"><?php echo htmlspecialchars($detalhe['nome_campo']); ?></small>
                                    <p class="border-bottom pb-2">
                                        <?php
                                        if ($detalhe['tipo_campo'] === 'data' && !empty($detalhe['valor'])) {
                                            echo date('d/m/Y', strtotime($detalhe['valor']));
                                        } else {
                                            echo nl2br(htmlspecialchars($detalhe['valor']));
                                        }
                                        ?>
                                    </p>
                                    </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center bg-light">
                    <div>
                        <?php if(!empty($anexos)): ?>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-paperclip"></i> ANEXOS
                                </button>
                                <ul class="dropdown-menu">
                                    <?php foreach($anexos as $anexo): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?php echo htmlspecialchars(str_replace('../', '', $anexo['valor'])); ?>" download>
                                                <i class="bi bi-download"></i> <?php echo basename($anexo['valor']); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if(count($anexos) > 1): ?>
                            <a href="#" class="btn btn-outline-secondary ms-2"><i class="bi bi-cloud-download"></i> BAIXAR TODOS OS ANEXOS</a>
                        <?php endif; ?>
                    </div>
                    <div>
                        <a href="portal.php?slug=<?php echo $secao_info['slug']; ?>" class="btn btn-secondary">VOLTAR</a>
                    </div>
                </div>
            </div>
            
        </main>
    </div>
</div>

<footer class="p-3 mt-4">
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
document.addEventListener('DOMContentLoaded', function () {
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