<?php
// /estrutura.php
require_once 'conexao.php';
require_once 'bootstrap_portal.php';

$page_title = "Estrutura Organizacional";

// Encontra o ID da seção "Estrutura Organizacional"
$stmt_portal = $pdo->prepare("SELECT id FROM portais WHERE nome = 'Estrutura Organizacional' LIMIT 1");
$stmt_portal->execute();
$id_portal = $stmt_portal->fetchColumn();

$unidades = [];
if ($id_portal) {
    // Busca todos os registros (unidades) desta seção
    $stmt_registros = $pdo->prepare("SELECT id FROM registros WHERE id_portal = ? ORDER BY id");
    $stmt_registros->execute([$id_portal]);
    $registros_ids = $stmt_registros->fetchAll(PDO::FETCH_COLUMN);

    if ($registros_ids) {
        $placeholders = implode(',', array_fill(0, count($registros_ids), '?'));
        $stmt_valores = $pdo->prepare(
            "SELECT r.id as registro_id, cp.nome_campo, vr.valor
             FROM registros r
             LEFT JOIN valores_registros vr ON r.id = vr.id_registro
             LEFT JOIN campos_portal cp ON vr.id_campo = cp.id
             WHERE r.id IN ($placeholders)
             ORDER BY r.id, cp.ordem"
        );
        $stmt_valores->execute($registros_ids);
        $valores_raw = $stmt_valores->fetchAll(PDO::FETCH_ASSOC);

        $dados_organizados = [];
        foreach ($valores_raw as $valor) {
            $dados_organizados[$valor['registro_id']][$valor['nome_campo']] = $valor['valor'];
        }

        foreach ($registros_ids as $id) {
            $unidades[] = array_merge(['id' => $id], $dados_organizados[$id] ?? []);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - Portal da Transparência</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/style.css?v=<?php echo time(); ?>">
    <style>
        .unidade-link.active {
            background-color: var(--cor-principal, #2ca444) !important;
            border-color: var(--cor-principal, #2ca444) !important;
            color: white !important;
        }
    </style>
</head>
<body class="bg-light">

<?php include 'header_publico.php'; ?>

<div class="container-fluid py-5">
    <div class="row">
        <div class="col-md-3 col-lg-2 d-none d-md-block px-0 ps-3 mb-4">
            <?php include 'menu.php'; ?>
        </div>
        <main class="col-md-9 ms-auto col-lg-10 px-md-5">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0 fw-bold"><?php echo htmlspecialchars($page_title); ?></h2>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm overflow-hidden">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-list-task me-2 text-primary"></i> Unidades Ativas</h6>
                        </div>
                        <div class="p-3 bg-light-subtle border-bottom">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-filter"></i></span>
                                <input type="text" id="filtro-unidade" class="form-control border-start-0" placeholder="Filtrar secretarias...">
                            </div>
                        </div>
                        <div class="list-group list-group-flush" id="lista-de-unidades" style="max-height: 500px; overflow-y: auto;">
                            <?php foreach ($unidades as $index => $unidade): ?>
                                <a href="#unidade-<?php echo $unidade['id']; ?>" class="list-group-item list-group-item-action unidade-item p-3 border-bottom-0 <?php echo $index === 0 ? 'active unidade-link' : ''; ?>" data-id="<?php echo $unidade['id']; ?>">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-building me-2 opacity-50"></i>
                                        <span><?php echo htmlspecialchars($unidade['Unidade'] ?? 'Unidade sem nome'); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <?php if (!empty($unidades)): ?>
                        <?php foreach ($unidades as $index => $unidade): ?>
                        <div class="card border-0 shadow-sm unidade-detalhe" id="unidade-<?php echo $unidade['id']; ?>" style="<?php echo $index > 0 ? 'display:none;' : ''; ?>">
                            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold text-primary"><?php echo htmlspecialchars($unidade['Unidade'] ?? 'Detalhes da Unidade'); ?></h5>
                                <span class="badge bg-light text-dark border">Cód: #<?php echo $unidade['id']; ?></span>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-muted text-uppercase mb-1">Responsável</label>
                                        <p class="mb-0 fw-600"><?php echo htmlspecialchars($unidade['Responsável'] ?? 'Não informado'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-muted text-uppercase mb-1">E-mail de Contato</label>
                                        <p class="mb-0 text-primary fw-600"><?php echo htmlspecialchars($unidade['Email'] ?? 'Não informado'); ?></p>
                                    </div>
                                    <div class="col-12">
                                        <label class="small fw-bold text-muted text-uppercase mb-1">Endereço de Atendimento</label>
                                        <div class="p-3 bg-light rounded"><i class="bi bi-geo-alt me-2 text-danger"></i><?php echo nl2br(htmlspecialchars($unidade['Endereço'] ?? 'Não informado')); ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-muted text-uppercase mb-1">Canais de Telefone</label>
                                        <p class="mb-0"><i class="bi bi-telephone-inbound me-2 opacity-50"></i><?php echo htmlspecialchars($unidade['Telefones'] ?? 'Não informado'); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-muted text-uppercase mb-1">Horas de Expediente</label>
                                        <p class="mb-0"><i class="bi bi-clock me-2 opacity-50"></i><?php echo nl2br(htmlspecialchars($unidade['Horário de Atendimento'] ?? 'Não informado')); ?></p>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <label class="small fw-bold text-muted text-uppercase mb-1 border-bottom d-block pb-1 mb-3">Atribuições e Competências</label>
                                        <div class="text-muted" style="line-height: 1.6;"><?php echo nl2br(htmlspecialchars($unidade['Competências'] ?? 'Não detalhado em nosso sistema.')); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card border-0 shadow-sm p-5 text-center bg-white rounded-3">
                            <i class="bi bi-layout-sidebar-inset display-4 text-muted mb-3 d-block"></i>
                            <p class="text-muted mb-0">Nenhuma unidade cadastrada na Estrutura Organizacional.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php 
$custom_container_class = "container-custom-padding";
include 'footer_publico.php'; 
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const linksUnidades = document.querySelectorAll('.unidade-item');
    const detalhesUnidades = document.querySelectorAll('.unidade-detalhe');
    const filtroInput = document.getElementById('filtro-unidade');

    linksUnidades.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            // Esconde todos
            detalhesUnidades.forEach(detalhe => detalhe.style.display = 'none');
            // Mostra o alvo
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.style.display = 'block';
            }
            // Navegação visual
            linksUnidades.forEach(l => {
                l.classList.remove('active');
                l.classList.remove('unidade-link');
            });
            this.classList.add('active');
            this.classList.add('unidade-link');
        });
    });

    filtroInput.addEventListener('keyup', function() {
        const termo = this.value.toLowerCase();
        linksUnidades.forEach(link => {
            const nomeUnidade = link.textContent.toLowerCase();
            link.style.display = nomeUnidade.includes(termo) ? '' : 'none';
        });
    });
});
</script>
</body>
</html>