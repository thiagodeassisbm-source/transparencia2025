<?php
require_once 'auth_check.php';
require_once '../conexao.php';

if (!isset($_SESSION['import_xml']) || !isset($_POST['mapeamento'])) { 
    die("Dados de importação ou mapeamento ausentes."); 
}
$dados_importacao = $_SESSION['import_xml'];
$mapeamento = $_POST['mapeamento'];
$xml = simplexml_load_string($dados_importacao['xml_content']);
$id_portal = $dados_importacao['id_portal'];


// --- INÍCIO DO NOVO CÓDIGO DINÂMICO ---

// 1. Busca todos os tipos de XML cadastrados e ativos no banco de dados
$stmt_tipos = $pdo->query("SELECT tag_container, nome_amigavel FROM tipos_xml WHERE ativo = 1");
$tipos_xml = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);

// 2. Identifica o tipo de dados dinamicamente, verificando qual tag container (plural) existe no XML
$tipo_dados = '';
if (!empty($tipos_xml)) {
    foreach ($tipos_xml as $tipo) {
        if (isset($xml->{$tipo['tag_container']})) {
            $tipo_dados = $tipo['nome_amigavel'];
            break; // Para a busca assim que encontrar o tipo correspondente
        }
    }
}

// 3. Monta a string de busca do XPath dinamicamente com todas as tags de registro (singular)
$stmt_tags = $pdo->query("SELECT tag_registro FROM tipos_xml WHERE ativo = 1");
$tags_validas = $stmt_tags->fetchAll(PDO::FETCH_COLUMN);

$dados_xml = [];
if (!empty($tags_validas)) {
    // Exemplo de resultado: //Contrato | //Despesa | //Servidor
    $xpath_query = '//' . implode(' | //', $tags_validas);
    $dados_xml = $xml->xpath($xpath_query);
}

// --- FIM DO NOVO CÓDIGO DINÂMICO ---


$ids_mapeados = array_values(array_filter($mapeamento));
$headers = [];
if (!empty($ids_mapeados)) {
    $placeholders = implode(',', array_fill(0, count($ids_mapeados), '?'));
    $stmt_headers = $pdo->prepare("SELECT id, nome_campo FROM campos_portal WHERE id IN ($placeholders)");
    $stmt_headers->execute($ids_mapeados);
    $headers = $stmt_headers->fetchAll(PDO::FETCH_KEY_PAIR);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Importar XML (Passo 3 - Pré-visualização)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">
<?php 
$page_title_for_header = 'Importar XML (Pré-visualização)'; 
include 'admin_header.php'; 
?>
<div class="container-fluid container-custom-padding">
    <div class="row"><div class="col-12">
        <form action="processar_importacao_final.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_portal" value="<?php echo htmlspecialchars($id_portal); ?>">
            <input type="hidden" name="metadados_serializados" value="<?php echo base64_encode(serialize($dados_importacao['metadados'])); ?>">
            <input type="hidden" name="mapeamento_serializado" value="<?php echo base64_encode(serialize($mapeamento)); ?>">
            <input type="hidden" name="tipo_dados" value="<?php echo htmlspecialchars($tipo_dados); ?>">
            <textarea name="xml_content" style="display:none;"><?php echo base64_encode($dados_importacao['xml_content']); ?></textarea>

            <div class="card">
                <div class="card-header"><h4>Passo 3: Associar Anexos e Confirmar</h4></div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <?php foreach($mapeamento as $tag => $id_campo): ?>
                                    <?php if(!empty($id_campo) && isset($headers[$id_campo])): ?>
                                        <th><?php echo htmlspecialchars($headers[$id_campo]); ?></th>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <th style="width: 30%;">Anexar Arquivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($dados_xml as $index => $item): ?>
                            <tr>
                                <?php foreach($mapeamento as $tag => $id_campo): ?>
                                    <?php if(!empty($id_campo)): ?>
                                        <td><?php echo htmlspecialchars($item->{$tag}); ?></td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <td><input type="file" class="form-control form-control-sm" name="anexos[<?php echo $index; ?>]"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-body text-end">
                    <a href="mapear_xml.php" class="btn btn-secondary">Voltar ao Mapeamento</a>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-circle-fill"></i> Concluir Importação</button>
                </div>
            </div>
        </form>
    </div></div>
</div>
<footer class="text-center p-3 bg-light mt-4"></footer>
</body>
</html>