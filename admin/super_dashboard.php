<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Apenas Super Admin pode ver esta página
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

// Lógica de Troca de Prefeitura (Acesso Rápido)
if (isset($_GET['acessar'])) {
    $id_pref = filter_input(INPUT_GET, 'acessar', FILTER_VALIDATE_INT);
    if ($id_pref) {
        $_SESSION['id_prefeitura'] = $id_pref;
        header("Location: dashboard.php");
        exit;
    }
}

// Estatísticas Globais
$total_prefeituras = $pdo->query("SELECT COUNT(*) FROM prefeituras")->fetchColumn();
$total_vagas = $pdo->query("SELECT COUNT(*) FROM registros")->fetchColumn(); // total de dados lançados
$prefeituras = $pdo->query("SELECT * FROM prefeituras ORDER BY nome ASC")->fetchAll();

$page_title_for_header = 'Central do Super Admin';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Super Admin - Plataforma</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="admin_style.css?v=<?php echo time(); ?>">
    <style>
        .card-stat { transition: all 0.3s ease; border: none; border-radius: 15px; }
        .card-stat:hover { transform: translateY(-5px); }
        .table-custom { border-radius: 15px; overflow: hidden; background: #fff; }
        .table-custom thead { background: #6366f1; color: #fff; }
        .status-badge { font-size: 0.75rem; padding: 5px 12px; border-radius: 50px; }
    </style>
</head>
<body class="bg-light">

<?php include 'admin_header.php'; ?>

<div class="container-fluid container-custom-padding py-4">
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card card-stat shadow-sm bg-primary text-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar-lg bg-white bg-opacity-25 rounded-circle p-3 me-3"><i class="bi bi-buildings fs-1"></i></div>
                    <div>
                        <h6 class="mb-1 opacity-75">Clientes / Prefeituras</h6>
                        <h2 class="mb-0 fw-bold"><?php echo $total_prefeituras; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat shadow-sm bg-indigo text-white h-100" style="background: #4f46e5;">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar-lg bg-white bg-opacity-25 rounded-circle p-3 me-3"><i class="bi bi-database-check fs-1"></i></div>
                    <div>
                        <h6 class="mb-1 opacity-75">Base de Dados Total</h6>
                        <h2 class="mb-0 fw-bold"><?php echo $total_vagas; ?> registros</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
             <div class="card card-stat shadow-sm bg-success text-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar-lg bg-white bg-opacity-25 rounded-circle p-3 me-3"><i class="bi bi-shield-check fs-1"></i></div>
                    <div>
                        <h6 class="mb-1 opacity-75">Status GERAL</h6>
                        <h2 class="mb-0 fw-bold">Online</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gestão de Prefeituras -->
    <div class="card shadow-sm border-0 table-custom">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-list-task me-2 text-primary"></i> Prefeituras sob Gestão</h5>
            <button class="btn btn-primary rounded-pill btn-sm px-4"><i class="bi bi-plus-circle me-1"></i> Novo Cliente</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Prefeitura</th>
                        <th>URL Slug</th>
                        <th>Status</th>
                        <th>Lançamentos</th>
                        <th class="text-end pe-4">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($prefeituras)): ?>
                    <tr><td colspan="5" class="text-center py-5">Nenhum cliente cadastrado.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($prefeituras as $pref): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-3 border rounded shadow-sm p-1 bg-white">
                                    <img src="<?php echo !empty($pref['logo']) ? '../'.$pref['logo'] : '../imagens/logo-placeholder.png'; ?>" style="width: 32px; height: 32px; object-fit: contain;">
                                </div>
                                <span class="fw-bold"><?php echo htmlspecialchars($pref['nome']); ?></span>
                            </div>
                        </td>
                        <td><code class="bg-light p-1">/<?php echo $pref['slug']; ?></code></td>
                        <td>
                            <span class="status-badge <?php echo $pref['status'] == 'ativo' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> <?php echo ucfirst($pref['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                                $id_p = $pref['id'];
                                $qtd = $pdo->query("SELECT COUNT(*) FROM registros WHERE id_portal IN (SELECT id FROM portais WHERE id_prefeitura = $id_p)")->fetchColumn() ?: 0;
                                echo $qtd;
                            ?>
                        </td>
                        <td class="text-end pe-4">
                            <a href="super_dashboard.php?acessar=<?php echo $pref['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill"><i class="bi bi-door-open me-1"></i> Acessar Painel</a>
                            <button class="btn btn-light btn-sm rounded-pill ms-1"><i class="bi bi-gear"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<footer class="text-center p-4 text-muted small">
    &copy; <?php echo date('Y'); ?> - Sistema de Transparência Multi-Central
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
