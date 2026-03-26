<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Apenas admins podem acessar
if ($_SESSION['admin_user_perfil'] !== 'admin') {
    $_SESSION['mensagem_sucesso'] = "Acesso negado.";
    header("Location: index.php");
    exit;
}

// Busca dados para os dropdowns
$secoes = $pdo->query("SELECT id, nome FROM portais ORDER BY nome ASC")->fetchAll();
$tipos_documento = $pdo->query("SELECT id, nome FROM tipos_documento ORDER BY nome ASC")->fetchAll();
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY ordem ASC")->fetchAll();

$dados_xml = null;
$id_portal_preview = null;
$metadados_preview = [];
$erro = '';
$tipo_dados = '';

// Lógica para o Passo 1: ler o XML e mostrar a pré-visualização
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['xml_file'])) {
    $id_portal_preview = filter_input(INPUT_POST, 'id_portal', FILTER_VALIDATE_INT);
    
    $metadados_preview = [
        'exercicio' => $_POST['exercicio'],
        'unidade_gestora' => $_POST['unidade_gestora'],
        'periodicidade' => $_POST['periodicidade'],
        'mes' => $_POST['mes'],
        'id_tipo_documento' => $_POST['id_tipo_documento'],
        'id_classificacao' => filter_input(INPUT_POST, 'id_classificacao', FILTER_VALIDATE_INT)
    ];

    if (!$id_portal_preview) {
        $erro = "Por favor, selecione uma seção de destino.";
    } elseif ($_FILES['xml_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['xml_file']['tmp_name'];
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file_tmp_path);

        if ($xml === false) {
            $erro = "Erro: O arquivo XML enviado parece estar malformado ou corrompido.";
        } else {
            if (isset($xml->contratos->contrato) && count($xml->contratos->contrato) > 0) {
                $tipo_dados = 'contratos';
                $dados_xml = $xml->contratos->contrato;
                $mapeamento_preview = [
                    'numero_contrato' => 'Número do Contrato',
                    'nome_contratado' => 'Contratado',
                    'valor_total' => 'Valor'
                ];
            } elseif (isset($xml->folha_pagamento->servidor) && count($xml->folha_pagamento->servidor) > 0) {
                $tipo_dados = 'folha_pagamento';
                $dados_xml = $xml->folha_pagamento->servidor;
                $mapeamento_preview = [
                    'matricula' => 'Matrícula',
                    'nome_servidor' => 'Nome do Servidor',
                    'salario_bruto' => 'Salário Bruto'
                ];
            } else {
                 $erro = "Estrutura de XML não reconhecida.";
            }
        }
    } else {
        $erro = "Ocorreu um erro no upload do arquivo.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Importar XML - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time( ); ?>">
</head>
<body class="bg-light-subtle">

<?php 
$page_title_for_header = 'Importar XML'; 
include 'admin_header.php'; 
?>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-12">
            
            <?php if ($erro): ?>
                <div class="alert alert-danger"><?php echo $erro; ?></div>
            <?php endif; ?>

            <?php if (!$dados_xml): // Formulário Inicial (Passo 1) ?>
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
                        <button type="submit" class="btn btn-primary"><i class="bi bi-eye-fill"></i> Pré-visualizar Importação</button>
                    </form>
                </div>
            </div>
            <?php else: // Pré-visualização (Passo 2) ?>
            <form id="form-final" action="processar_importacao_final.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_portal" value="<?php echo $id_portal_preview; ?>">
                <input type="hidden" name="tipo_dados" value="<?php echo $tipo_dados; ?>">
                <?php foreach ($metadados_preview as $key => $value): ?>
                    <input type="hidden" name="metadados[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($value); ?>">
                <?php endforeach; ?>
                <div class="card">
                    <div class="card-header"><h4>Passo 2: Associar Anexos e Confirmar Importação</h4></div>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <?php foreach ($mapeamento_preview as $header): ?>
                                        <th><?php echo $header; ?></th>
                                    <?php endforeach; ?>
                                    <th style="width: 35%;">Anexar Arquivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $item_index = 0;
                                foreach($dados_xml as $item) {
                                    echo '<tr>';
                                    foreach ($mapeamento_preview as $tag_xml => $header) {
                                        echo '<td>' . htmlspecialchars($item->{$tag_xml}) . '</td>';
                                    }
                                    echo '<td>';
                                    foreach ($item->children() as $child) {
                                        $tag_name = $child->getName();
                                        $tag_value = (string)$child;
                                        echo '<input type="hidden" name="itens[' . $item_index . '][' . htmlspecialchars($tag_name) . ']" value="' . htmlspecialchars(trim($tag_value)) . '">';
                                    }
                                    echo '<input type="file" class="form-control" name="anexos[' . $item_index . ']">';
                                    echo '</td>';
                                    echo '</tr>';
                                    $item_index++;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-body text-end">
                        <a href="importar_xml.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" id="final-submit-button" class="btn btn-success"><i class="bi bi-check-circle-fill"></i> Salvar Tudo no Portal</button>
                    </div>
                </div>
            </form>
            <?php endif; ?>
            
            <div id="feedback-area" class="mt-4" style="display: none;">
                <div class="progress mb-3" style="height: 25px;">
                    <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
                <div id="report-area" class="alert"></div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center p-3 bg-light mt-4">
    &copy; <?php echo date('Y'); ?> - Todos os direitos reservados.
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function( ) {
    const form = document.getElementById('form-final');
    if (!form) return;

    const submitButton = form.querySelector('#final-submit-button');
    const feedbackArea = document.getElementById('feedback-area');
    const progressBar = document.getElementById('progress-bar');
    const reportArea = document.getElementById('report-area');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...';
        feedbackArea.style.display = 'block';
        
        const formData = new FormData(form);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);

        xhr.upload.onprogress = function(event) {
            if (event.lengthComputable) {
                const percentComplete = (event.loaded / event.total) * 100;
                let percent = percentComplete.toFixed(0);
                progressBar.style.width = percent + '%';
                progressBar.textContent = `Enviando... ${percent}%`;
            }
        };

        xhr.onload = function() {
            progressBar.style.width = '100%';
            progressBar.textContent = '100% - Processamento Concluído';

            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    // ================== LÓGICA DE REDIRECIONAMENTO ALTERADA ==================
                    if (response.success) {
                        // Se o servidor retornar sucesso, redireciona imediatamente para a URL fornecida.
                        window.location.href = response.redirect_url;
                    } else {
                        // Se o servidor retornar um erro, mostra a mensagem de erro.
                        throw new Error(response.message || 'Ocorreu um erro desconhecido.');
                    }
                    // ================== FIM DA LÓGICA ALTERADA ==================

                } catch (e) {
                    progressBar.classList.add('bg-danger');
                    reportArea.classList.add('alert-danger');
                    reportArea.innerHTML = `<strong>Erro Crítico:</strong> ${e.message}  
<pre>${xhr.responseText}</pre>`;
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="bi bi-check-circle-fill"></i> Salvar Tudo no Portal';
                }
            } else {
                 progressBar.classList.add('bg-danger');
                 reportArea.classList.add('alert-danger');
                 reportArea.textContent = `Erro no servidor: ${xhr.status} ${xhr.statusText}`;
                 submitButton.disabled = false;
                 submitButton.innerHTML = '<i class="bi bi-check-circle-fill"></i> Salvar Tudo no Portal';
            }
        };
        xhr.send(formData);
    });
});
</script>
</body>
</html>
