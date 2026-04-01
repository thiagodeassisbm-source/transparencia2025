<?php
require_once 'auth_check.php';
require_once '../conexao.php';

if ($_SESSION['admin_user_perfil'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$is_superadmin = isset($_SESSION['is_superadmin']) && (int) $_SESSION['is_superadmin'] === 1;
$pref_id = (int) ($_SESSION['id_prefeitura'] ?? 0);

if (!$is_superadmin && $pref_id <= 0) {
    $_SESSION['mensagem_erro'] = 'Contexto de prefeitura não identificado.';
    header('Location: index.php');
    exit;
}

$sem_contexto_super = $is_superadmin && $pref_id <= 0;

$secoes = [];
$tipos_documento = [];
$categorias = [];

if ($pref_id > 0) {
    $stmt = $pdo->prepare('SELECT id, nome FROM portais WHERE id_prefeitura = ? ORDER BY nome ASC');
    $stmt->execute([$pref_id]);
    $secoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt_t = $pdo->prepare('SELECT id, nome FROM tipos_documento WHERE id_prefeitura = ? OR id_prefeitura IS NULL ORDER BY nome ASC');
    $stmt_t->execute([$pref_id]);
    $tipos_documento = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

    $stmt_c = $pdo->prepare('SELECT id, nome FROM categorias WHERE id_prefeitura = ? ORDER BY ordem ASC');
    $stmt_c->execute([$pref_id]);
    $categorias = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
}

$erro = '';

// Passo 1: upload + sessão para o mapeamento (sempre vinculado à prefeitura do contexto)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xml_file'])) {
    if ($sem_contexto_super) {
        $erro = 'Entre em uma prefeitura pela Central do Super Admin (Gerenciar Prefeituras → Entrar) antes de importar.';
    } elseif ($_FILES['xml_file']['error'] === UPLOAD_ERR_OK) {
        $id_portal_post = filter_input(INPUT_POST, 'id_portal', FILTER_VALIDATE_INT);
        if (!$id_portal_post) {
            $erro = 'Selecione a seção de destino dos dados.';
        } else {
            $stmt_ok = $pdo->prepare('SELECT id FROM portais WHERE id = ? AND id_prefeitura = ?');
            $stmt_ok->execute([$id_portal_post, $pref_id]);
            if (!$stmt_ok->fetch()) {
                $erro = 'A seção escolhida não pertence à sua prefeitura.';
            } else {
                $file_tmp_path = $_FILES['xml_file']['tmp_name'];
                $xml_content = file_get_contents($file_tmp_path);

                $_SESSION['import_xml'] = [
                    'id_prefeitura' => $pref_id,
                    'id_portal' => $id_portal_post,
                    'metadados' => [
                        'exercicio' => $_POST['exercicio'] ?? '',
                        'unidade_gestora' => $_POST['unidade_gestora'] ?? '',
                        'periodicidade' => $_POST['periodicidade'] ?? '',
                        'mes' => $_POST['mes'] ?? '',
                        'id_tipo_documento' => $_POST['id_tipo_documento'] ?? null,
                        'id_classificacao' => filter_input(INPUT_POST, 'id_classificacao', FILTER_VALIDATE_INT),
                    ],
                    'xml_content' => $xml_content,
                ];

                header('Location: mapear_xml.php');
                exit;
            }
        }
    } else {
        $erro = 'Ocorreu um erro no upload do arquivo.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Importar XML (Passo 1)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">

<?php
$page_title_for_header = 'Importar XML';
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-12">
            <?php if (!empty($_SESSION['mensagem_erro'])): ?>
                <div class="alert alert-danger border-0 shadow-sm"><?php echo htmlspecialchars($_SESSION['mensagem_erro']); unset($_SESSION['mensagem_erro']); ?></div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['mensagem_sucesso'])): ?>
                <div class="alert alert-success border-0 shadow-sm"><?php echo htmlspecialchars($_SESSION['mensagem_sucesso']); unset($_SESSION['mensagem_sucesso']); ?></div>
            <?php endif; ?>
            <?php if ($erro): ?><div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div><?php endif; ?>

            <?php if ($sem_contexto_super): ?>
            <div class="alert alert-warning border-0 shadow-sm">
                <strong>Prefeitura não selecionada.</strong> Na Central do Super Admin, abra <strong>Gerenciar Prefeituras</strong> e use <strong>Entrar</strong> no município desejado; em seguida volte a <strong>Importar XML</strong>.
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header"><h4>Passo 1: Informações da Publicação e Upload do XML</h4></div>
                <div class="card-body">
                    <?php if ($pref_id > 0): ?>
                    <p class="text-muted small mb-3">Importação apenas para seções e tipos cadastrados <strong>desta prefeitura</strong>.</p>
                    <?php endif; ?>
                    <form method="POST" action="importar_xml.php" enctype="multipart/form-data" class="<?php echo ($sem_contexto_super || empty($secoes)) ? 'opacity-50' : ''; ?>">
                        <h5 class="mb-3">Informações Gerais da Publicação</h5>
                        <div class="row p-3 mb-3 bg-white rounded border">
                            <div class="col-md-3 mb-3"><label for="exercicio" class="form-label">Exercício</label><select class="form-select" id="exercicio" name="exercicio" required><?php for ($ano = 2050; $ano >= 2020; $ano--): ?><option value="<?php echo $ano; ?>" <?php echo (date('Y') == $ano) ? 'selected' : ''; ?>><?php echo $ano; ?></option><?php endfor; ?></select></div>
                            <div class="col-md-3 mb-3"><label for="unidade_gestora" class="form-label">Unidade Gestora</label><select class="form-select" id="unidade_gestora" name="unidade_gestora" required><option>Prefeitura Municipal</option><option>Fundo Municipal de Saúde</option></select></div>
                            <div class="col-md-3 mb-3"><label for="periodicidade" class="form-label">Periodicidade</label><select class="form-select" id="periodicidade" name="periodicidade" required><option>Não se Aplica</option><option>Mensal</option><option>Bimestral</option><option>Trimestral</option><option>Quadrimestral</option><option>Semestral</option><option>Anual</option><option>Quadrienal</option></select></div>
                            <div class="col-md-3 mb-3"><label for="mes" class="form-label">Mês de Referência</label><select class="form-select" id="mes" name="mes" required><option>Não se Aplica</option><option>Janeiro</option><option>Fevereiro</option><option>Março</option><option>Abril</option><option>Maio</option><option>Junho</option><option>Julho</option><option>Agosto</option><option>Setembro</option><option>Outubro</option><option>Novembro</option><option>Dezembro</option></select></div>
                            <div class="col-md-6 mb-3"><label for="id_tipo_documento" class="form-label">Tipo de Documento</label><select class="form-select" id="id_tipo_documento" name="id_tipo_documento" required><option value="">-- Selecione --</option><?php foreach ($tipos_documento as $tipo): ?><option value="<?php echo (int) $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nome']); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-6 mb-3"><label for="id_classificacao" class="form-label">Classificação (Categoria)</label><select class="form-select" id="id_classificacao" name="id_classificacao"><option value="">-- Nenhuma --</option><?php foreach ($categorias as $categoria): ?><option value="<?php echo (int) $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option><?php endforeach; ?></select></div>
                        </div>
                        <hr>
                        <h5 class="mb-3 mt-4">Arquivos</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for="id_portal" class="form-label fw-bold">Seção de Destino dos Dados</label><select class="form-select" id="id_portal" name="id_portal" required <?php echo ($sem_contexto_super || empty($secoes)) ? 'disabled' : ''; ?>><option value="">-- Escolha uma seção --</option><?php foreach ($secoes as $secao): ?><option value="<?php echo (int) $secao['id']; ?>"><?php echo htmlspecialchars($secao['nome']); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-6 mb-3"><label for="xml_file" class="form-label fw-bold">Arquivo de Dados (XML)</label><input class="form-control" type="file" id="xml_file" name="xml_file" accept=".xml,text/xml" required <?php echo ($sem_contexto_super || empty($secoes)) ? 'disabled' : ''; ?>></div>
                        </div>
                        <button type="submit" class="btn btn-primary" <?php echo ($sem_contexto_super || empty($secoes)) ? 'disabled' : ''; ?>><i class="bi bi-arrow-right-circle-fill"></i> Continuar para Mapeamento</button>
                        <?php if ($pref_id > 0 && empty($secoes)): ?>
                            <p class="text-danger small mt-2 mb-0">Não há seções (portais) cadastradas para esta prefeitura. Crie uma seção em <strong>Criar Seções</strong> antes de importar.</p>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>

</body>
</html>
