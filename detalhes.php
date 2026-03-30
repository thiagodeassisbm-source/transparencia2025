<?php
require_once 'conexao.php';
require_once 'bootstrap_portal.php'; // Carrega o contexto da prefeitura (SaaS)

$registro_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$registro_id) { die("Registro não especificado."); }

// Busca os dados da seção a qual o registro pertence e valida prefeitura
$stmt_info = $pdo->prepare(
    "SELECT p.nome as nome_secao, p.slug 
     FROM registros r 
     JOIN portais p ON r.id_portal = p.id 
     WHERE r.id = ? AND p.id_prefeitura = ?");
$stmt_info->execute([$registro_id, $id_prefeitura_ativa]);
$secao_info = $stmt_info->fetch();
if (!$secao_info) { die("Registro não encontrado ou não pertence a esta prefeitura."); }

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

$anexos = [];
foreach ($detalhes as $detalhe) {
    if ($detalhe['tipo_campo'] === 'anexo' && !empty($detalhe['valor'])) {
        $anexos[] = $detalhe;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <base href="<?php echo $base_url; ?>">
    <title>Detalhes do Documento - Portal da Transparência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/style.css?v=<?php echo time(); ?>">
    <style>
        .detail-label { font-size: 0.75rem; font-weight: 700; color: #6c757d; text-transform: uppercase; margin-bottom: 2px; }
        .detail-value { font-size: 1rem; color: #212529; padding-bottom: 8px; border-bottom: 1px solid #f1f3f5; font-family: 'Poppins', sans-serif; font-weight: 500; }
        .anexos-card-title { font-size: 1rem; font-weight: 700; color: #2ca444; }
    </style>
</head>
<body class="bg-light">

<?php 
$page_title = "Detalhes"; 
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
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0 fw-bold">Detalhes do Registro</h2>
                <a href="portal/<?php echo $slug_prefeitura_ativa; ?>/<?php echo $secao_info['slug']; ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h4 class="mb-0 fw-bold text-primary"><i class="bi bi-file-earmark-text me-2"></i><?php echo htmlspecialchars($secao_info['nome_secao']); ?></h4>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <?php foreach ($detalhes as $detalhe): ?>
                            <?php if ($detalhe['tipo_campo'] !== 'anexo'): ?>
                                <?php
                                $col_class = ($detalhe['tipo_campo'] === 'textarea') ? 'col-md-12' : 'col-md-6';
                                ?>
                                <div class="<?php echo $col_class; ?>">
                                    <div class="detail-label"><?php echo htmlspecialchars($detalhe['nome_campo']); ?></div>
                                    <div class="detail-value">
                                        <?php
                                        if ($detalhe['tipo_campo'] === 'data' && !empty($detalhe['valor'])) {
                                            $d = date_create($detalhe['valor']);
                                            echo ($d) ? date_format($d, 'd/m/Y') : htmlspecialchars($detalhe['valor']);
                                        } elseif ($detalhe['tipo_campo'] === 'moeda' && !empty($detalhe['valor'])) {
                                            echo 'R$ ' . number_format($detalhe['valor'], 2, ',', '.');
                                        } else {
                                            echo nl2br(htmlspecialchars($detalhe['valor']));
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if(!empty($anexos)): ?>
                <div class="card-footer bg-white border-top p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-paperclip text-success me-2"></i>Documentos Anexos</h5>
                    <div class="row">
                        <?php foreach($anexos as $anexo): ?>
                            <div class="col-md-6 mb-2">
                                <a href="<?php echo htmlspecialchars(str_replace('../', '', $anexo['valor'])); ?>" download class="btn btn-light border w-100 text-start d-flex justify-content-between align-items-center p-3">
                                    <span><i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i><?php echo basename($anexo['valor']); ?></span>
                                    <i class="bi bi-download text-muted"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
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