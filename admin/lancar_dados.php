<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Variáveis de sessão do usuário
$perfil_usuario = $_SESSION['admin_user_perfil'];
$id_usuario_logado = $_SESSION['admin_user_id'];
$nome_usuario_logado = $_SESSION['admin_user_nome'] ?? 'Usuário';

$portal_id = filter_input(INPUT_GET, 'portal_id', FILTER_VALIDATE_INT);
if (!$portal_id) { header("Location: index.php"); exit; }

// Trava de Segurança Granular
if (!tem_permissao('form_' . $portal_id, 'lancar')) {
    header("Location: dashboard.php");
    exit;
}

// Busca dados para os dropdowns dos metadados
$tipos_documento = $pdo->query("SELECT id, nome FROM tipos_documento ORDER BY nome ASC")->fetchAll();
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY ordem ASC")->fetchAll();

// Busca dados da seção e seus campos para montar o formulário
$stmt_portal = $pdo->prepare("SELECT nome FROM portais WHERE id = ?");
$stmt_portal->execute([$portal_id]);
$secao = $stmt_portal->fetch();
$stmt_campos = $pdo->prepare("SELECT id, nome_campo, tipo_campo, opcoes_campo FROM campos_portal WHERE id_portal = ? ORDER BY ordem, nome_campo");
$stmt_campos->execute([$portal_id]);
$campos_dinamicos = $stmt_campos->fetchAll();

// Mapeia os tipos de campo para facilitar o processamento no POST
$tipos_por_id = [];
foreach ($campos_dinamicos as $cp) {
    $tipos_por_id[$cp['id']] = $cp['tipo_campo'];
}

// Processa o formulário de inserção
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        // 1. Pega os dados dos metadados (parte fixa)
        $exercicio = $_POST['exercicio'];
        $mes = $_POST['mes'];
        $periodicidade = $_POST['periodicidade'];
        $unidade_gestora = $_POST['unidade_gestora'];
        
        // --- CORREÇÃO 1: Tratar o campo como opcional, convertendo para NULL se estiver vazio ---
        // A lógica original ($id_tipo_documento = $_POST['id_tipo_documento'];) não previa um valor vazio.
        $id_tipo_documento = filter_input(INPUT_POST, 'id_tipo_documento', FILTER_VALIDATE_INT);
        if (empty($id_tipo_documento)) { $id_tipo_documento = null; }

        $id_classificacao = filter_input(INPUT_POST, 'id_classificacao', FILTER_VALIDATE_INT);
        if (empty($id_classificacao)) { $id_classificacao = null; }

        // 2. Insere o registro principal com os metadados
        $stmt_reg = $pdo->prepare(
            "INSERT INTO registros (id_portal, exercicio, mes, periodicidade, unidade_gestora, id_tipo_documento, id_classificacao) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt_reg->execute([$portal_id, $exercicio, $mes, $periodicidade, $unidade_gestora, $id_tipo_documento, $id_classificacao]);
        $id_registro = $pdo->lastInsertId();

        // 3. Pega e salva os dados dinâmicos (parte específica da seção)
        $valores_post = $_POST['valores'] ?? [];
        $valores_files = $_FILES['valores'] ?? [];
        $stmt_val = $pdo->prepare("INSERT INTO valores_registros (id_registro, id_campo, valor) VALUES (?, ?, ?)");

        foreach ($valores_post as $id_campo => $valor) {
            if (isset($valor) && $valor !== '') {
                $valor_final = trim($valor);
                // Se for moeda ou número, limpa a máscara para salvar no banco
                if (isset($tipos_por_id[$id_campo]) && ($tipos_por_id[$id_campo] === 'moeda' || $tipos_por_id[$id_campo] === 'numero')) {
                    $valor_final = limpar_valor_monetario($valor_final);
                }
                $stmt_val->execute([$id_registro, $id_campo, $valor_final]);
            }
        }
        
        if (!empty($valores_files)) {
            foreach ($valores_files['name'] as $id_campo => $nome_arquivo) {
                if ($valores_files['error'][$id_campo] === UPLOAD_ERR_OK) {
                    $upload_dir = '../uploads/';
                    $nome_unico = uniqid() . '-' . basename($nome_arquivo);
                    $caminho_destino = $upload_dir . $nome_unico;
                    if (move_uploaded_file($valores_files['tmp_name'][$id_campo], $caminho_destino)) {
                        $stmt_val->execute([$id_registro, $id_campo, str_replace('../', '', $caminho_destino)]);
                    } else { throw new Exception("Falha ao mover o arquivo enviado."); }
                }
            }
        }
        
        $pdo->commit();
        
        registrar_log(
            $pdo,
            'ADIÇÃO',
            modulo_log_lancamento($pdo, $portal_id),
            "Incluiu novo lançamento ID $id_registro (seção portal_id $portal_id)."
        );
        
        $_SESSION['mensagem_sucesso'] = "Lançamento inserido com sucesso!";
        header("Location: ver_lancamentos.php?portal_id=" . $portal_id);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['mensagem_sucesso'] = "Erro ao inserir registro: " . $e->getMessage();
        header("Location: lancar_dados.php?portal_id=" . $portal_id);
        exit();
    }
}

// Busca dados da seção e seus campos para montar o formulário
$stmt_portal = $pdo->prepare("SELECT nome FROM portais WHERE id = ?");
$stmt_portal->execute([$portal_id]);
$secao = $stmt_portal->fetch();
$stmt_campos = $pdo->prepare("SELECT id, nome_campo, tipo_campo, opcoes_campo FROM campos_portal WHERE id_portal = ? ORDER BY ordem, nome_campo");
$stmt_campos->execute([$portal_id]);
$campos_dinamicos = $stmt_campos->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lançar Dados em <?php echo htmlspecialchars($secao['nome']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">

<?php
// Define as variáveis para o cabeçalho reutilizável
$page_title_for_header = 'Lançar Dados: ' . htmlspecialchars($secao['nome']);
$active_breadcrumb = 'Lançar Dados';
// Como é uma sub-página, nenhum link principal da navegação fica ativo
$active_nav_link = ''; 
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding">
    <div class="row"><div class="col-12 pt-4">
        <?php
        if (isset($_SESSION['mensagem_sucesso'])) {
            echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['mensagem_sucesso']) . '</div>';
            unset($_SESSION['mensagem_sucesso']);
        }
        ?>
        <div class="card">
            <div class="card-header">
                Formulário de Inserção de Dados
            </div>
            <div class="card-body">
                <form method="POST" action="lancar_dados.php?portal_id=<?php echo $portal_id; ?>" enctype="multipart/form-data">
                    
                    <h5 class="mb-3">Informações Gerais da Publicação</h5>
                    <div class="row p-3 mb-3 bg-white rounded border">
                        <div class="col-md-3 mb-3"><label for="exercicio" class="form-label">Exercício</label><select class="form-select" id="exercicio" name="exercicio" required><?php for ($ano = 2050; $ano >= 2020; $ano--): ?><option value="<?php echo $ano; ?>" <?php echo (date('Y') == $ano) ? 'selected' : ''; ?>><?php echo $ano; ?></option><?php endfor; ?></select></div>
                        <div class="col-md-3 mb-3"><label for="unidade_gestora" class="form-label">Unidade Gestora</label><select class="form-select" id="unidade_gestora" name="unidade_gestora" required><option>Prefeitura Municipal</option><option>Fundo Municipal de Saúde</option></select></div>
                        <div class="col-md-3 mb-3"><label for="periodicidade" class="form-label">Periodicidade</label><select class="form-select" id="periodicidade" name="periodicidade" required><option>Não se Aplica</option><option>Mensal</option><option>Bimestral</option><option>Trimestral</option><option>Quadrimestral</option><option>Semestral</option><option>Anual</option><option>Quadrienal</option></select></div>
                        <div class="col-md-3 mb-3"><label for="mes" class="form-label">Mês de Referência</label><select class="form-select" id="mes" name="mes" required><option>Não se Aplica</option><option>Janeiro</option><option>Fevereiro</option><option>Março</option><option>Abril</option><option>Maio</option><option>Junho</option><option>Julho</option><option>Agosto</option><option>Setembro</option><option>Outubro</option><option>Novembro</option><option>Dezembro</option></select></div>
                        
                        <div class="col-md-6 mb-3"><label for="id_tipo_documento" class="form-label">Tipo de Documento (Opcional)</label><select class="form-select" id="id_tipo_documento" name="id_tipo_documento"><option value="">-- Selecione --</option><?php foreach ($tipos_documento as $tipo): ?><option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nome']); ?></option><?php endforeach; ?></select></div>

                        <div class="col-md-6 mb-3"><label for="id_classificacao" class="form-label">Classificação (Categoria)</label><select class="form-select" id="id_classificacao" name="id_classificacao"><option value="">-- Nenhuma --</option><?php foreach ($categorias as $categoria): ?><option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option><?php endforeach; ?></select></div>
                    </div>
                    
                    <hr>

                    <h5 class="mb-3 mt-4">Detalhes Específicos de "<?php echo htmlspecialchars($secao['nome']); ?>"</h5>
                    <div class="p-3 bg-white rounded border">
                        <?php if (empty($campos_dinamicos)): ?>
                            <p class="text-muted">Esta seção não possui campos específicos.</p>
                        <?php else: ?>
                            <?php foreach ($campos_dinamicos as $campo): ?>
                                <div class="mb-3">
                                    <label for="campo_<?php echo $campo['id']; ?>" class="form-label"><?php echo htmlspecialchars($campo['nome_campo']); ?></label>
                                    <?php if ($campo['tipo_campo'] == 'select'): ?>
                                        <select class="form-select" id="campo_<?php echo $campo['id']; ?>" name="valores[<?php echo $campo['id']; ?>]">
                                            <option value="">-- Selecione --</option>
                                            <?php
                                            if (strpos($campo['opcoes_campo'], 'tabela:') === 0) {
                                                $nome_tabela = substr($campo['opcoes_campo'], 7);
                                                $opcoes_db = $pdo->query("SELECT nome FROM `$nome_tabela` ORDER BY nome ASC")->fetchAll(PDO::FETCH_COLUMN);
                                                foreach ($opcoes_db as $opcao) { echo '<option value="' . htmlspecialchars($opcao) . '">' . htmlspecialchars($opcao) . '</option>'; }
                                            } else {
                                                $opcoes = explode(',', $campo['opcoes_campo']);
                                                foreach ($opcoes as $opcao) { $opcao_trim = trim($opcao); echo '<option value="' . htmlspecialchars($opcao_trim) . '">' . htmlspecialchars($opcao_trim) . '</option>'; }
                                            }
                                            ?>
                                        </select>
                                    <?php elseif ($campo['tipo_campo'] == 'anexo'): ?>
                                        <input type="file" class="form-control" id="campo_<?php echo $campo['id']; ?>" name="valores[<?php echo $campo['id']; ?>]">
                                    <?php elseif ($campo['tipo_campo'] == 'textarea'): ?>
                                        <textarea class="form-control" id="campo_<?php echo $campo['id']; ?>" name="valores[<?php echo $campo['id']; ?>]" rows="3"></textarea>
                                    <?php else: 
                                        $tipo_input = 'text';
                                        $extra_class = '';
                                        if ($campo['tipo_campo'] == 'data') $tipo_input = 'date';
                                        
                                        // Ambos recebem máscara, pois 'numero' no portal geralmente é um valor decimal/monetário
                                        if ($campo['tipo_campo'] == 'moeda' || $campo['tipo_campo'] == 'numero') {
                                            $extra_class = 'money-mask';
                                        }
                                        
                                        $step = ($campo['tipo_campo'] == 'moeda' || $campo['tipo_campo'] == 'numero') ? '0.01' : 'any';
                                    ?>
                                        <input type="<?php echo $tipo_input; ?>" class="form-control <?php echo $extra_class; ?>" id="campo_<?php echo $campo['id']; ?>" name="valores[<?php echo $campo['id']; ?>]">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-success">Salvar Lançamento</button>
                        <a href="criar_secoes.php" class="btn btn-secondary">Voltar</a>
                    </div>
                </form>
            </div>
        </div>
    </div></div>
</div>

<footer class="text-center p-3 bg-light mt-4">
    &copy; <?php echo date('Y'); ?> - Todos os direitos reservados.
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>