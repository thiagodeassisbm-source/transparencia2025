<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once __DIR__ . '/includes/xml_import_helpers.php';

if ($_SESSION['admin_user_perfil'] !== 'admin') {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['import_xml']) || !isset($_POST['mapeamento'])) {
    $_SESSION['mensagem_erro'] = 'Dados de importação ou mapeamento ausentes. Refaça o Passo 2.';
    header('Location: importar_xml.php');
    exit;
}

$dados_importacao = $_SESSION['import_xml'];
$pref_id = (int) ($_SESSION['id_prefeitura'] ?? 0);
$id_portal = (int) ($dados_importacao['id_portal'] ?? 0);
$stored_pref = isset($dados_importacao['id_prefeitura']) ? (int) $dados_importacao['id_prefeitura'] : 0;

if ($pref_id <= 0 || $id_portal <= 0 || ($stored_pref > 0 && $stored_pref !== $pref_id)) {
    unset($_SESSION['import_xml']);
    $_SESSION['mensagem_erro'] = 'Contexto de importação inválido. Comece pelo Passo 1.';
    header('Location: importar_xml.php');
    exit;
}

$stmt_portal = $pdo->prepare('SELECT id FROM portais WHERE id = ? AND id_prefeitura = ?');
$stmt_portal->execute([$id_portal, $pref_id]);
if (!$stmt_portal->fetch()) {
    unset($_SESSION['import_xml']);
    $_SESSION['mensagem_erro'] = 'A seção não pertence à prefeitura atual.';
    header('Location: importar_xml.php');
    exit;
}

$mapeamento = $_POST['mapeamento'];
$stmt_campos_ok = $pdo->prepare('SELECT id FROM campos_portal WHERE id_portal = ?');
$stmt_campos_ok->execute([$id_portal]);
$campos_permitidos = array_map('intval', $stmt_campos_ok->fetchAll(PDO::FETCH_COLUMN));
foreach ($mapeamento as $tag => $id_campo) {
    if ($id_campo === '' || $id_campo === null) {
        continue;
    }
    $cid = (int) $id_campo;
    if (!in_array($cid, $campos_permitidos, true)) {
        $_SESSION['mensagem_erro'] = 'Mapeamento inválido: campo de destino não pertence à seção escolhida.';
        header('Location: mapear_xml.php');
        exit;
    }
}

$xml = simplexml_load_string($dados_importacao['xml_content']);
if ($xml === false) {
    unset($_SESSION['import_xml']);
    $_SESSION['mensagem_erro'] = 'XML inválido. Reinicie a importação.';
    header('Location: importar_xml.php');
    exit;
}


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

// 3. Lista registros: tag do passo 2 (detecção automática ou tipos_xml) ou união de tags do cadastro
$stmt_tags = $pdo->query('SELECT tag_registro FROM tipos_xml WHERE ativo = 1');
$tags_validas = $stmt_tags->fetchAll(PDO::FETCH_COLUMN);

$tag_registro_sessao = $dados_importacao['tag_registro_importacao'] ?? null;
if ($tag_registro_sessao !== null && $tag_registro_sessao !== '') {
    $tag_registro_sessao = xml_import_sanitize_tag((string) $tag_registro_sessao);
} else {
    $tag_registro_sessao = null;
}

$dados_xml = xml_import_listar_registros($xml, $tags_validas, $tag_registro_sessao);

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
            <input type="hidden" name="tag_registro_importacao" value="<?php echo htmlspecialchars($dados_importacao['tag_registro_importacao'] ?? ''); ?>">
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