<?php
require_once 'conexao.php';
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
        // Busca todos os valores para as unidades encontradas, já com o nome do campo
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

        // Organiza os dados em um array mais fácil de usar
        $dados_organizados = [];
        foreach ($valores_raw as $valor) {
            $dados_organizados[$valor['registro_id']][$valor['nome_campo']] = $valor['valor'];
        }

        // Monta o array final de unidades
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
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">

<header class="page-header">
    <div class="container-fluid container-custom-padding">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                <li class="breadcrumb-item active" aria-current="page">Estrutura Organizacional</li>
            </ol>
        </nav>
        <h1>Estrutura Organizacional</h1>
    </div>
</header>

<div class="container-fluid">
    <div class="row">
        <?php include 'menu.php'; ?>
        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="list-group">
                        <span class="list-group-item list-group-item-action active" aria-current="true">UNIDADES</span>
                        <div class="list-group-item">
                            <input type="text" id="filtro-unidade" class="form-control" placeholder="Filtrar unidades...">
                        </div>
                        <div id="lista-de-unidades">
                            <?php foreach ($unidades as $unidade): ?>
                                <a href="#unidade-<?php echo $unidade['id']; ?>" class="list-group-item list-group-item-action unidade-item">
                                    <?php echo htmlspecialchars($unidade['Unidade'] ?? 'Unidade sem nome'); ?>
                                </a>
                            <?php
endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <?php if (!empty($unidades)): ?>
                        <?php foreach ($unidades as $index => $unidade): ?>
                        <div class="card mb-3 unidade-detalhe" id="unidade-<?php echo $unidade['id']; ?>" style="<?php echo $index > 0 ? 'display:none;' : ''; ?>">
                            <div class="card-header">
                                <h5><?php echo htmlspecialchars($unidade['Unidade'] ?? 'Detalhes da Unidade'); ?></h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Responsável:</strong><br><?php echo htmlspecialchars($unidade['Responsável'] ?? 'Não informado'); ?></p>
                                <p><strong>Endereço:</strong><br><?php echo nl2br(htmlspecialchars($unidade['Endereço'] ?? 'Não informado')); ?></p>
                                <p><strong>Email:</strong><br><?php echo htmlspecialchars($unidade['Email'] ?? 'Não informado'); ?></p>
                                <p><strong>Telefones:</strong><br><?php echo htmlspecialchars($unidade['Telefones'] ?? 'Não informado'); ?></p>
                                <p><strong>Horário de Atendimento:</strong><br><?php echo nl2br(htmlspecialchars($unidade['Horário de Atendimento'] ?? 'Não informado')); ?></p>
                                <hr>
                                <p><strong>Competências:</strong><br><?php echo nl2br(htmlspecialchars($unidade['Competências'] ?? 'Não informado')); ?></p>
                            </div>
                        </div>
                        <?php
    endforeach; ?>
                    <?php
else: ?>
                        <p class="text-muted">Nenhuma unidade cadastrada na Estrutura Organizacional.</p>
                    <?php
endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
<footer class="text-center p-3 mt-4"></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const linksUnidades = document.querySelectorAll('.unidade-item');
    const detalhesUnidades = document.querySelectorAll('.unidade-detalhe');
    const filtroInput = document.getElementById('filtro-unidade');

    if (linksUnidades.length > 0) {
        linksUnidades[0].classList.add('active');
    }

    linksUnidades.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            detalhesUnidades.forEach(detalhe => detalhe.style.display = 'none');
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.style.display = 'block';
            }
            linksUnidades.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });

    filtroInput.addEventListener('keyup', function() {
        const termo = this.value.toLowerCase();
        linksUnidades.forEach(link => {
            const nomeUnidade = link.textContent.toLowerCase();
            if (nomeUnidade.includes(termo)) {
                link.style.display = '';
            } else {
                link.style.display = 'none';
            }
        });
    });
});
</script>
</body>
</html>