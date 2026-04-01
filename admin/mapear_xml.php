<?php
require_once 'auth_check.php';
require_once '../conexao.php';

if ($_SESSION['admin_user_perfil'] !== 'admin') {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['import_xml'])) {
    $_SESSION['mensagem_erro'] = 'Nenhum arquivo em processamento. Envie o XML em Importar XML (Passo 1).';
    header('Location: importar_xml.php');
    exit;
}

$dados_importacao = $_SESSION['import_xml'];
$pref_id = (int) ($_SESSION['id_prefeitura'] ?? 0);
$id_portal = isset($dados_importacao['id_portal']) ? (int) $dados_importacao['id_portal'] : 0;
$stored_pref = isset($dados_importacao['id_prefeitura']) ? (int) $dados_importacao['id_prefeitura'] : 0;

if ($pref_id <= 0 || $id_portal <= 0) {
    unset($_SESSION['import_xml']);
    $_SESSION['mensagem_erro'] = 'Contexto de prefeitura inválido. Reinicie a importação pelo Passo 1.';
    header('Location: importar_xml.php');
    exit;
}

if ($stored_pref > 0 && $stored_pref !== $pref_id) {
    unset($_SESSION['import_xml']);
    $_SESSION['mensagem_erro'] = 'A importação foi iniciada em outro município. Reinicie pelo Passo 1.';
    header('Location: importar_xml.php');
    exit;
}

$stmt_portal = $pdo->prepare('SELECT id FROM portais WHERE id = ? AND id_prefeitura = ?');
$stmt_portal->execute([$id_portal, $pref_id]);
if (!$stmt_portal->fetch()) {
    unset($_SESSION['import_xml']);
    $_SESSION['mensagem_erro'] = 'A seção desta importação não pertence à prefeitura atual. Reinicie pelo Passo 1.';
    header('Location: importar_xml.php');
    exit;
}

$xml = simplexml_load_string($dados_importacao['xml_content']);
if ($xml === false) {
    unset($_SESSION['import_xml']);
    $_SESSION['mensagem_erro'] = 'Erro ao ler o XML. Envie um arquivo válido no Passo 1.';
    header('Location: importar_xml.php');
    exit;
}

$stmt_campos = $pdo->prepare('SELECT id, nome_campo FROM campos_portal WHERE id_portal = ? ORDER BY nome_campo ASC');
$stmt_campos->execute([$id_portal]);
$campos_destino = $stmt_campos->fetchAll();

$tags_xml = [];

$stmt_tags = $pdo->query('SELECT tag_registro FROM tipos_xml WHERE ativo = 1');
$tags_validas = $stmt_tags ? $stmt_tags->fetchAll(PDO::FETCH_COLUMN) : [];

$primeiro_registro = null;
if (!empty($tags_validas)) {
    $xpath_parts = [];
    foreach ($tags_validas as $tag) {
        $xpath_parts[] = '//' . $tag . '[1]';
    }
    $xpath_query = implode(' | ', $xpath_parts);
    $found = $xml->xpath($xpath_query);
    $primeiro_registro = $found[0] ?? null;
}

if ($primeiro_registro) {
    foreach ($primeiro_registro->children() as $child) {
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
                            <?php if (empty($tags_xml)): ?>
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
                                                <option value="<?php echo (int) $campo['id']; ?>"><?php echo htmlspecialchars($campo['nome_campo']); ?></option>
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
                        <button type="submit" class="btn btn-primary" <?php if (empty($tags_xml)) {
                            echo 'disabled';
                        } ?>>Continuar para Anexos <i class="bi bi-arrow-right"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div></div>
</div>
<footer class="text-center p-3 bg-light mt-4"></footer>
</body>
</html>
