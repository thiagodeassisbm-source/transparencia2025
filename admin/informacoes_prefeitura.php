<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

if ($_SESSION['admin_user_perfil'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Lógica para salvar as configurações
$id_prefeitura = $_SESSION['id_prefeitura'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configuracoes = $_POST['config'];
    
    // Preparar prepared statement robusto (Insert ou Update por Prefeitura)
    $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor, id_prefeitura) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
    
    foreach ($configuracoes as $chave => $valor) {
        $stmt->execute([$chave, trim($valor), $id_prefeitura]);
    }

    // Lógica para o upload do logo
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/site/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
        
        $logo_antigo = $_POST['logo_antigo'];
        // Deleta o logo antigo se ele existir
        if (!empty($logo_antigo) && file_exists($logo_antigo)) {
            unlink($logo_antigo);
        }

        $nome_arquivo = 'logo-' . time() . '.' . pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $caminho_destino = $upload_dir . $nome_arquivo;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $caminho_destino)) {
            $stmt->execute(['prefeitura_logo', $caminho_destino, $id_prefeitura]);
        }
    }

    registrar_log($pdo, 'EDIÇÃO', 'configuracoes', "Atualizou as informações gerais da prefeitura.");

    $_SESSION['mensagem_sucesso'] = "Configurações da Prefeitura salvas com sucesso!";
    header("Location: informacoes_prefeitura.php");
    exit;
}

// Busca as configurações atuais do banco para preencher o formulário (Filtrado por Prefeitura)
$stmt = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE id_prefeitura = ? AND chave LIKE 'prefeitura_%'");
$stmt->execute([$id_prefeitura]);
$config_atuais = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Valores padrão se não existirem
$cor_principal = $config_atuais['prefeitura_cor_principal'] ?? '#2ca444';
$cor_secundaria = $config_atuais['prefeitura_cor_secundaria'] ?? '#1a4d1a';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Configurações da Prefeitura - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .color-preview-box {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #fff;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .color-input-wrapper {
            width: 50px;
            height: 50px;
            overflow: hidden;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            padding: 0;
            display: flex;
        }
        .color-input-wrapper input[type="color"] {
            border: none;
            width: 150%;
            height: 150%;
            cursor: pointer;
            margin: -25%;
        }
        .hex-code {
            font-family: monospace;
            font-weight: bold;
            color: #444;
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid #eee;
        }
        .nav-tabs .nav-link {
            color: #495057;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #2ca444;
            font-weight: 700;
            border-bottom: 3px solid #2ca444;
        }
    </style>
</head>
<body class="bg-light-subtle">
<?php 
$page_title_for_header = 'Configurações da Prefeitura'; 
include 'admin_header.php'; 
?>
<div class="container-fluid container-custom-padding py-4">
    <div class="row"><div class="col-12">
        <?php
        if (isset($_SESSION['mensagem_sucesso'])) {
            echo '<div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">' 
               . '<i class="bi bi-check-circle-fill me-2"></i>' . htmlspecialchars($_SESSION['mensagem_sucesso']) . 
               '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            unset($_SESSION['mensagem_sucesso']);
        }
        ?>
        
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
                <h4 class="mb-0"><i class="bi bi-gear-fill me-2 text-primary"></i>Configurações Gerais do Sistema</h4>
            </div>
            <div class="card-body p-0">
                <form method="POST" action="informacoes_prefeitura.php" enctype="multipart/form-data">
                    
                    <!-- Navegação por Abas -->
                    <ul class="nav nav-tabs px-4 pt-3" id="configTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="geral-tab" data-bs-toggle="tab" data-bs-target="#geral" type="button" role="tab"><i class="bi bi-info-circle me-1"></i> Informações Principais</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="estilo-tab" data-bs-toggle="tab" data-bs-target="#estilo" type="button" role="tab"><i class="bi bi-palette me-1"></i> Estilo & Cores</button>
                        </li>
                    </ul>

                    <div class="tab-content p-4" id="configTabsContent">
                        
                        <!-- ABA GERAL -->
                        <div class="tab-pane fade show active" id="geral" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="titulo" class="form-label fw-bold">Título do Portal</label>
                                    <input type="text" class="form-control" id="titulo" name="config[prefeitura_titulo]" value="<?php echo htmlspecialchars($config_atuais['prefeitura_titulo'] ?? ''); ?>" placeholder="Ex: Portal da Transparência Municipal">
                                </div>
                                <div class="col-md-6">
                                    <label for="cidade" class="form-label fw-bold">Cidade / Estado</label>
                                    <input type="text" class="form-control" id="cidade" name="config[prefeitura_cidade]" value="<?php echo htmlspecialchars($config_atuais['prefeitura_cidade'] ?? ''); ?>" placeholder="Ex: Goiânia / GO">
                                </div>
                                <div class="col-md-6">
                                    <label for="telefone" class="form-label fw-bold">Telefone de Contato</label>
                                    <input type="text" class="form-control" id="telefone" name="config[prefeitura_telefone]" value="<?php echo htmlspecialchars($config_atuais['prefeitura_telefone'] ?? ''); ?>" placeholder="(XX) XXXX-XXXX">
                                </div>
                                <div class="col-md-12">
                                    <label for="endereco" class="form-label fw-bold">Endereço Completo</label>
                                    <input type="text" class="form-control" id="endereco" name="config[prefeitura_endereco]" value="<?php echo htmlspecialchars($config_atuais['prefeitura_endereco'] ?? ''); ?>">
                                </div>
                                <div class="col-md-12 mt-4">
                                    <label class="form-label fw-bold d-block">Logomarca da Prefeitura</label>
                                    <div class="d-flex align-items-center gap-4 p-3 border rounded bg-light">
                                        <div class="bg-white p-2 border rounded shadow-sm">
                                            <?php if(!empty($config_atuais['prefeitura_logo'])): ?>
                                                <img src="<?php echo htmlspecialchars($config_atuais['prefeitura_logo']); ?>" alt="Logo Atual" style="max-height: 100px; display: block;">
                                            <?php else: ?>
                                                <div class="text-muted small">Sem Logo</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <input type="file" class="form-control mb-2" id="logo" name="logo" accept="image/*">
                                            <input type="hidden" name="logo_antigo" value="<?php echo htmlspecialchars($config_atuais['prefeitura_logo'] ?? ''); ?>">
                                            <small class="text-muted d-block"><i class="bi bi-info-circle me-1"></i>Recomendado: Arquivo PNG ou SVG com fundo transparente.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ABA ESTILO -->
                        <div class="tab-pane fade" id="estilo" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="p-4 border rounded shadow-sm bg-white h-100">
                                        <label class="form-label fw-bold mb-3 d-block"><i class="bi bi-brush me-2 text-primary"></i>Cor Principal (Banner da Logo)</label>
                                        <div class="color-preview-box mb-3">
                                            <div class="color-input-wrapper">
                                                <input type="color" id="picker_principal" value="<?php echo $cor_principal; ?>" oninput="syncColor('picker', 'principal')">
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light fw-bold">#</span>
                                                    <input type="text" class="form-control fw-bold" id="text_principal" value="<?php echo str_replace('#', '', $cor_principal); ?>" oninput="syncColor('text', 'principal')" maxlength="6">
                                                </div>
                                                <input type="hidden" name="config[prefeitura_cor_principal]" id="input_principal" value="<?php echo $cor_principal; ?>">
                                            </div>
                                        </div>
                                        <small class="text-muted d-block">Fundo da área onde fica a logomarca da prefeitura.</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="p-4 border rounded shadow-sm bg-white h-100">
                                        <label class="form-label fw-bold mb-3 d-block"><i class="bi bi-brush me-2 text-success"></i>Cor Secundária (Barra Superior & Pesquisa)</label>
                                        <div class="color-preview-box mb-3">
                                            <div class="color-input-wrapper">
                                                <input type="color" id="picker_secundaria" value="<?php echo $cor_secundaria; ?>" oninput="syncColor('picker', 'secundaria')">
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light fw-bold">#</span>
                                                    <input type="text" class="form-control fw-bold" id="text_secundaria" value="<?php echo str_replace('#', '', $cor_secundaria); ?>" oninput="syncColor('text', 'secundaria')" maxlength="6">
                                                </div>
                                                <input type="hidden" name="config[prefeitura_cor_secundaria]" id="input_secundaria" value="<?php echo $cor_secundaria; ?>">
                                            </div>
                                        </div>
                                        <small class="text-muted d-block">Barra de acessibilidade e área de busca lateral.</small>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="alert alert-info py-3 shadow-sm border-0 d-flex align-items-center">
                                        <i class="bi bi-lightbulb-fill fs-4 me-3"></i>
                                        <div>
                                            <strong>Dica de Personalização:</strong> Você pode escolher a cor visualmente clicando no círculo colorido ou digitar o código hexadecimal (HEX) diretamente no campo de texto ao lado. Ambos se sincronizam automaticamente!
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light py-4 px-4 text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm"><i class="bi bi-save me-2"></i>Salvar Todas as Configurações</button>
                    </div>
                </form>
            </div>
        </div>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function syncColor(source, type) {
    const picker = document.getElementById('picker_' + type);
    const text = document.getElementById('text_' + type);
    const hidden = document.getElementById('input_' + type);
    
    if (source === 'picker') {
        const color = picker.value.toUpperCase();
        text.value = color.replace('#', '');
        hidden.value = color;
    } else {
        let val = text.value.trim();
        if (val.length === 6) {
            const color = '#' + val.toUpperCase();
            picker.value = color;
            hidden.value = color;
        }
    }
}
</script>

<?php include 'admin_footer.php'; ?>
</body>
</html>
>
</body>
</html>