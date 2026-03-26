<?php
// /busca.php (Versão Final e Corrigida)
require_once 'conexao.php';

$termo_busca = $_GET['q'] ?? '';
$resultados = [];

if (!empty($termo_busca)) {
    $termo_like = '%' . $termo_busca . '%';

    // PARTE 1: Busca nos dados dos lançamentos (valores_registros)
    $sql1 = "SELECT 
                'Lançamento' as tipo,
                p.nome as titulo,
                vr.valor as subtitulo,
                CONCAT('portal.php?slug=', p.slug) as link
             FROM valores_registros vr
             JOIN registros r ON vr.id_registro = r.id
             JOIN portais p ON r.id_portal = p.id
             WHERE vr.valor LIKE ?
             GROUP BY r.id";

    // PARTE 2: Busca nos títulos e subtítulos dos cards (QUERY CORRIGIDA)
    $sql2 = "SELECT 
                'Card Informativo' as tipo,
                c.titulo,
                c.subtitulo,
                CONCAT('portal.php?slug=', p.slug) as link
             FROM cards_informativos c
             LEFT JOIN portais p ON c.id_secao = p.id
             WHERE c.titulo LIKE ? OR c.subtitulo LIKE ?";

    // PARTE 3: Busca nos nomes das seções
    $sql3 = "SELECT 
                'Seção do Menu' as tipo,
                nome as titulo,
                descricao as subtitulo,
                CONCAT('portal.php?slug=', slug) as link
             FROM portais 
             WHERE nome LIKE ? OR descricao LIKE ?";
    
    // Une as 3 buscas em uma só
    $stmt = $pdo->prepare("($sql1) UNION ($sql2) UNION ($sql3) LIMIT 50");
    $stmt->execute([$termo_like, $termo_like, $termo_like, $termo_like, $termo_like]);
    $resultados = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Resultado da Busca por "<?php echo htmlspecialchars($termo_busca); ?>"</title>
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
                <li class="breadcrumb-item active" aria-current="page">Busca</li>
            </ol>
        </nav>
        <h1>Resultado da Busca</h1>
    </div>
</header>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <?php include 'menu.php'; ?>

        <main class="col-md-9 ms-auto col-lg-10 px-md-4 pt-4">
            <h2 class="h3">Resultados para: "<?php echo htmlspecialchars($termo_busca); ?>"</h2>
            <hr>

            <?php if (empty($resultados)): ?>
                <div class="alert alert-warning">Nenhum resultado encontrado para o termo pesquisado.</div>
            <?php else: ?>
                <p>Foram encontrados <?php echo count($resultados); ?> resultados.</p>
                <div class="info-card-list">
                    <?php foreach($resultados as $resultado): ?>
                        <?php
                            $link_destino = $resultado['link'] ?? '#';
                        ?>
                        <a href="<?php echo htmlspecialchars($link_destino); ?>" class="info-card" <?php if($link_destino !== '#') echo 'target="_blank"'; ?>>
                            <div class="icon-container">
                                <?php
                                $icon_class = 'bi-file-earmark-text'; // Padrão
                                if ($resultado['tipo'] === 'Card Informativo') $icon_class = 'bi-card-list';
                                if ($resultado['tipo'] === 'Seção do Menu') $icon_class = 'bi-list-ul';
                                ?>
                                <i class="bi <?php echo $icon_class; ?>" style="font-size: 2rem; color: #6c757d;"></i>
                            </div>
                            <div class="text-container">
                                <div class="card-titulo"><?php echo htmlspecialchars($resultado['titulo']); ?></div>
                                <div class="card-subtitulo">
                                    <strong>Tipo:</strong> <?php echo htmlspecialchars($resultado['tipo']); ?><br>
                                    <?php echo htmlspecialchars(mb_strimwidth($resultado['subtitulo'], 0, 100, "...")); ?>
                                </div>
                            </div>
                             <div class="favorite-icon">
                                <i class="bi bi-chevron-right"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<footer class="text-center p-3 mt-4">
    &copy; <?php echo date('Y'); ?> - Todos os direitos reservados.
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>