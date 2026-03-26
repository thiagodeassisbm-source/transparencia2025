<?php
require_once 'auth_check.php';
require_once '../conexao.php';

if ($_SESSION['admin_user_perfil'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Busca dados para os dropdowns
$secoes = $pdo->query("SELECT id, nome FROM portais ORDER BY nome ASC")->fetchAll();
$tipos_documento = $pdo->query("SELECT id, nome FROM tipos_documento ORDER BY nome ASC")->fetchAll();
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY ordem ASC")->fetchAll();

$erro = '';

// Lógica para o Passo 1: Receber os dados, salvar na sessão e redirecionar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xml_file'])) {
    if ($_FILES['xml_file']['error'] === UPLOAD_ERR_OK) {
        
        $file_tmp_path = $_FILES['xml_file']['tmp_name'];
        $xml_content = file_get_contents($file_tmp_path);

        $_SESSION['import_xml'] = [
            'id_portal' => filter_input(INPUT_POST, 'id_portal', FILTER_VALIDATE_INT),
            'metadados' => [
                'exercicio' => $_POST['exercicio'],
                'unidade_gestora' => $_POST['unidade_gestora'],
                'periodicidade' => $_POST['periodicidade'],
                'mes' => $_POST['mes'],
                'id_tipo_documento' => $_POST['id_tipo_documento'],
                'id_classificacao' => filter_input(INPUT_POST, 'id_classificacao', FILTER_VALIDATE_INT)
            ],
            'xml_content' => $xml_content
        ];

        header("Location: mapear_xml.php");
        exit;

    } else {
        $erro = "Ocorreu um erro no upload do arquivo.";
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
            <?php if ($erro): ?><div class="alert alert-danger"><?php echo $erro; ?></div><?php endif; ?>
            
            <div class="card">
                <div class="card-header"><h4>Passo 1: Informações da Publicação e Upload do XML</h4></div>
                <div class="card-body">
                    <form method="POST" action="importar_xml.php" enctype="multipart/form-data">
                        <h5 class="mb-3">Informações Gerais da Publicação</h5>
                        <div class="row p-3 mb-3 bg-white rounded border">
                            <div class="col-md-3 mb-3"><label for="exercicio" class="form-label">Exercício</label><select class="form-select" id="exercicio" name="exercicio" required><?php for ($ano = 2050; $ano >= 2020; $ano--): ?><option value="<?php echo $ano; ?>" <?php echo (date('Y') == $ano) ? 'selected' : ''; ?>><?php echo $ano; ?></option><?php endfor; ?></select></div>
                            <div class="col-md-3 mb-3"><label for="unidade_gestora" class="form-label">Unidade Gestora</label><select class="form-select" id="unidade_gestora" name="unidade_gestora" required><option>Prefeitura Municipal</option><option>Fundo Municipal de Saúde</option></select></div>
                            <div class="col-md-3 mb-3"><label for="periodicidade" class="form-label">Periodicidade</label><select class="form-select" id="periodicidade" name="periodicidade" required><option>Não se Aplica</option><option>Mensal</option><option>Bimestral</option><option>Trimestral</option><option>Quadrimestral</option><option>Semestral</option><option>Anual</option><option>Quadrienal</option></select></div>
                            <div class="col-md-3 mb-3"><label for="mes" class="form-label">Mês de Referência</label><select class="form-select" id="mes" name="mes" required><option>Não se Aplica</option><option>Janeiro</option><option>Fevereiro</option><option>Março</option><option>Abril</option><option>Maio</option><option>Junho</option><option>Julho</option><option>Agosto</option><option>Setembro</option><option>Outubro</option><option>Novembro</option><option>Dezembro</option></select></div>
                            <div class="col-md-6 mb-3"><label for="id_tipo_documento" class="form-label">Tipo de Documento</label><select class="form-select" id="id_tipo_documento" name="id_tipo_documento" required><option value="">-- Selecione --</option><?php foreach ($tipos_documento as $tipo): ?><option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nome']); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-6 mb-3"><label for="id_classificacao" class="form-label">Classificação (Categoria)</label><select class="form-select" id="id_classificacao" name="id_classificacao"><option value="">-- Nenhuma --</option><?php foreach ($categorias as $categoria): ?><option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option><?php endforeach; ?></select></div>
                        </div>
                        <hr>
                        <h5 class="mb-3 mt-4">Arquivos</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for="id_portal" class="form-label fw-bold">Seção de Destino dos Dados</label><select class="form-select" id="id_portal" name="id_portal" required><option value="">-- Escolha uma seção --</option><?php foreach ($secoes as $secao): ?><option value="<?php echo $secao['id']; ?>"><?php echo htmlspecialchars($secao['nome']); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-6 mb-3"><label for="xml_file" class="form-label fw-bold">Arquivo de Dados (XML)</label><input class="form-control" type="file" id="xml_file" name="xml_file" accept=".xml,text/xml" required></div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-right-circle-fill"></i> Continuar para Mapeamento</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// LINHA FALTANTE ADICIONADA AQUI
include 'admin_footer.php'; 
?>

</body>
</html>