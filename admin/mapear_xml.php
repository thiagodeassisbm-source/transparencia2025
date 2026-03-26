<?php
require_once 'auth_check.php';
require_once '../conexao.php';

if (!isset($_SESSION['import_xml'])) { 
    die("Nenhum dado de importação encontrado. Por favor, comece pelo Passo 1."); 
}
$dados_importacao = $_SESSION['import_xml'];

$xml = simplexml_load_string($dados_importacao['xml_content']);
if ($xml === false) { 
    die("Erro ao ler o XML."); 
}

$id_portal = $dados_importacao['id_portal'];
$stmt_campos = $pdo->prepare("SELECT id, nome_campo FROM campos_portal WHERE id_portal = ? ORDER BY nome_campo ASC");
$stmt_campos->execute([$id_portal]);
$campos_destino = $stmt_campos->fetchAll();

$tags_xml = [];

// --- INÍCIO DO NOVO CÓDIGO DINÂMICO ---
// Busca todas as tags de registro (singular) ativas da tabela `tipos_xml`
$stmt_tags = $pdo->query("SELECT tag_registro FROM tipos_xml WHERE ativo = 1");
$tags_validas = $stmt_tags->fetchAll(PDO::FETCH_COLUMN);

$primeiro_registro = null;
// Apenas executa a busca se houver tags cadastradas no banco
if (!empty($tags_validas)) {
    // Monta a string de busca do XPath dinamicamente para encontrar o primeiro registro de qualquer tipo válido
    // Exemplo de resultado: //Contrato[1] | //Despesa[1] | //Servidor[1]
    $xpath_parts = [];
    foreach ($tags_validas as $tag) {
        $xpath_parts[] = "//$tag" . "[1]";
    }
    $xpath_query = implode(' | ', $xpath_parts);

    // Executa a busca no XML
    $primeiro_registro = $xml->xpath($xpath_query)[0] ?? null;
}
// --- FIM DO NOVO CÓDIGO DINÂMICO ---


if ($primeiro_registro) {
    foreach($primeiro_registro->children() as $child) {
        $tags_xml[] = $child->getName();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Importar XML (Passo 2 - Mapeamento)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">
<?php $page_title_for_header = 'Importar XML (Mapeamento)'; include 'admin_header.php'; ?>
<div class="container-fluid container-custom-padding">
    <div class="row"><div class="col-12">
        <div class="card">
            <div class="card-header"><h4>Passo 2: Mapeamento "De/Para" dos Campos</h4></div>
            <div class="card-body">
                <p>O sistema encontrou as seguintes colunas no seu arquivo XML. Indique em qual campo do portal cada uma deve ser salva.</p>
                <form action="preview_importacao.php" method="POST">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Campo no XML (Origem)</th>
                                <th>Salvar no Campo do Portal (Destino)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($tags_xml)): ?>
                                <tr><td colspan="2" class="text-center">
                                    <strong class="text-danger">Nenhuma tag de dados encontrada no XML.</strong><br>
                                    Verifique a estrutura do arquivo ou se o tipo de XML está cadastrado corretamente em "Gerenciar Tipos de XML".
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($tags_xml as $tag): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($tag); ?></strong></td>
                                    <td>
                                        <select name="mapeamento[<?php echo htmlspecialchars($tag); ?>]" class="form-select">
                                            <option value="">-- Ignorar este campo --</option>
                                            <?php foreach ($campos_destino as $campo): ?>
                                                <option value="<?php echo $campo['id']; ?>"><?php echo htmlspecialchars($campo['nome_campo']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="text-end">
                        <a href="importar_xml.php" class="btn btn-secondary">Voltar</a>
                        <button type="submit" class="btn btn-primary" <?php if(empty($tags_xml)) echo 'disabled'; ?>>Continuar para Anexos <i class="bi bi-arrow-right"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div></div>
</div>
<footer class="text-center p-3 bg-light mt-4"></footer>
</body>
</html>