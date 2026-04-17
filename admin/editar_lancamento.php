<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Variáveis de sessão do usuário
$perfil_usuario = $_SESSION['admin_user_perfil'];
$id_usuario_logado = $_SESSION['admin_user_id'];
$nome_usuario_logado = $_SESSION['admin_user_nome'] ?? 'Usuário';

$registro_id = filter_input(INPUT_GET, 'registro_id', FILTER_VALIDATE_INT);
if (!$registro_id) { header("Location: index.php"); exit; }

// Busca o portal_id a partir do registro_id
$stmt_info = $pdo->prepare("SELECT id_portal FROM registros WHERE id = ?");
$stmt_info->execute([$registro_id]);
$registro_info = $stmt_info->fetch();
if (!$registro_info) { header("Location: index.php"); exit; }
$portal_id = $registro_info['id_portal'];

// Trava de Segurança Granular
if (!tem_permissao('form_' . $portal_id, 'editar')) {
    header("Location: dashboard.php");
    exit;
}

// Busca dados para os dropdowns dos metadados
$tipos_documento = $pdo->query("SELECT id, nome FROM tipos_documento ORDER BY nome ASC")->fetchAll();
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY ordem ASC")->fetchAll();

// Busca dados da seção e seus campos
$stmt_secao = $pdo->prepare("SELECT nome FROM portais WHERE id = ?");
$stmt_secao->execute([$portal_id]);
$secao = $stmt_secao->fetch();

$stmt_campos = $pdo->prepare("SELECT id, nome_campo, tipo_campo, opcoes_campo FROM campos_portal WHERE id_portal = ? ORDER BY ordem, nome_campo");
$stmt_campos->execute([$portal_id]);
$campos_dinamicos = $stmt_campos->fetchAll();

// Mapeia os tipos de campo para facilitar o processamento no POST
$tipos_por_id = [];
foreach ($campos_dinamicos as $cp) {
    $tipos_por_id[$cp['id']] = $cp['tipo_campo'];
}

// Processa o formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        // 1. Pega e atualiza os dados dos metadados (parte fixa) na tabela 'registros'
        $exercicio = $_POST['exercicio'];
        $mes = $_POST['mes'];
        $periodicidade = $_POST['periodicidade'];
        $unidade_gestora = $_POST['unidade_gestora'];
        $id_tipo_documento = $_POST['id_tipo_documento'];
        $id_classificacao = filter_input(INPUT_POST, 'id_classificacao', FILTER_VALIDATE_INT);
        if (empty($id_classificacao)) { $id_classificacao = null; }

        $stmt_reg = $pdo->prepare(
            "UPDATE registros SET exercicio = ?, mes = ?, periodicidade = ?, unidade_gestora = ?, id_tipo_documento = ?, id_classificacao = ?
             WHERE id = ?"
        );
        $stmt_reg->execute([$exercicio, $mes, $periodicidade, $unidade_gestora, $id_tipo_documento, $id_classificacao, $registro_id]);

        // 2. Apaga os valores dinâmicos antigos para inserir os novos
        $stmt_delete_vals = $pdo->prepare("DELETE FROM valores_registros WHERE id_registro = ?");
        $stmt_delete_vals->execute([$registro_id]);

        // 3. Insere os novos valores dinâmicos
        $valores_post = $_POST['valores'] ?? [];
        $valores_files = $_FILES['valores_files'] ?? [];
        $stmt_insert_val = $pdo->prepare("INSERT INTO valores_registros (id_registro, id_campo, valor) VALUES (?, ?, ?)");
        
        foreach ($valores_post as $id_campo => $valor) {
            if (isset($valor) && $valor !== '') {
                $valor_final = trim($valor);
                // Se for moeda ou número, limpa a máscara para salvar no banco
                if (isset($tipos_por_id[$id_campo]) && ($tipos_por_id[$id_campo] === 'moeda' || $tipos_por_id[$id_campo] === 'numero')) {
                    $valor_final = limpar_valor_monetario($valor_final);
                }
                $stmt_insert_val->execute([$registro_id, $id_campo, $valor_final]);
            }
        }
        
        if (!empty($valores_files)) {
             foreach ($valores_files['name'] as $id_campo => $nome_arquivo) {
                if ($valores_files['error'][$id_campo] === UPLOAD_ERR_OK) {
                    $caminho_antigo = $_POST['caminho_antigo'][$id_campo] ?? '';
                    if (!empty($caminho_antigo) && file_exists('../'.$caminho_antigo)) { unlink('../'.$caminho_antigo); }
                    
                    $upload_dir = '../uploads/';
                    $nome_unico = uniqid() . '-' . basename($nome_arquivo);
                    $caminho_destino = $upload_dir . $nome_unico;
                    move_uploaded_file($valores_files['tmp_name'][$id_campo], $caminho_destino);
                    $stmt_insert_val->execute([$registro_id, $id_campo, str_replace('../', '', $caminho_destino)]);
                }
            }
        }
        
        $pdo->commit();
        
        registrar_log(
            $pdo,
            'EDIÇÃO',
            modulo_log_lancamento($pdo, $portal_id),
            "Editou lançamento ID $registro_id (seção portal_id $portal_id)."
        );
        
        $_SESSION['mensagem_sucesso'] = "Lançamento atualizado com sucesso!";
        header("Location: ver_lancamentos.php?portal_id=" . $portal_id);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['mensagem_sucesso'] = "Erro ao atualizar: " . $e->getMessage();
        header("Location: editar_lancamento.php?registro_id=" . $registro_id);
        exit;
    }
}

$stmt_registro_atual = $pdo->prepare("SELECT * FROM registros WHERE id = ?");
$stmt_registro_atual->execute([$registro_id]);
$registro_atual = $stmt_registro_atual->fetch();

$stmt_valores = $pdo->prepare("SELECT id_campo, valor FROM valores_registros WHERE id_registro = ?");
$stmt_valores->execute([$registro_id]);
$valores_atuais = $stmt_valores->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Lançamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">

<?php 
// Define as variáveis para o cabeçalho reutilizável
$page_title_for_header = 'Editar Lançamento';
// Este é o último item do breadcrumb, que deve ser dinâmico
$active_breadcrumb = 'Editar'; 
// Como é uma sub-página, nenhum link principal da navegação fica ativo
$active_nav_link = ''; 
include 'admin_header.php'; 
?>

<div class="container-fluid container-custom-padding">
    <div class="row"><div class="col-12 pt-4"> <div class="card">
            <div class="card-header">Formulário de Edição para "<?php echo htmlspecialchars($secao['nome']); ?>"</div>
            <div class="card-body">
                <form method="POST" action="editar_lancamento.php?registro_id=<?php echo $registro_id; ?>" enctype="multipart/form-data">
                    
                    <h5 class="mb-3">Informações Gerais da Publicação</h5>
                    <div class="row p-3 mb-3 bg-white rounded border">
                        <div class="col-md-3 mb-3">
                            <label for="exercicio" class="form-label">Exercício</label>
                            <select class="form-select" id="exercicio" name="exercicio" required>
                                <?php for ($ano = 2050; $ano >= 2020; $ano--): ?>
                                    <option value="<?php echo $ano; ?>" <?php echo ($registro_atual['exercicio'] == $ano) ? 'selected' : ''; ?>><?php echo $ano; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="unidade_gestora" class="form-label">Unidade Gestora</label>
                            <select class="form-select" id="unidade_gestora" name="unidade_gestora" required>
                                <option <?php echo ($registro_atual['unidade_gestora'] == 'Prefeitura Municipal') ? 'selected' : ''; ?>>Prefeitura Municipal</option>
                                <option <?php echo ($registro_atual['unidade_gestora'] == 'Fundo Municipal de Saúde') ? 'selected' : ''; ?>>Fundo Municipal de Saúde</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="periodicidade" class="form-label">Periodicidade</label>
                            <select class="form-select" id="periodicidade" name="periodicidade" required>
                                <?php $periodicidades = ['Não se Aplica', 'Mensal', 'Bimestral', 'Trimestral', 'Quadrimestral', 'Semestral', 'Anual', 'Quadrienal']; ?>
                                <?php foreach($periodicidades as $p): ?>
                                <option <?php echo ($registro_atual['periodicidade'] == $p) ? 'selected' : ''; ?>><?php echo $p; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="mes" class="form-label">Mês de Referência</label>
                            <select class="form-select" id="mes" name="mes" required>
                                <?php $meses = ['Não se Aplica', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro']; ?>
                                <?php foreach($meses as $m): ?>
                                <option <?php echo ($registro_atual['mes'] == $m) ? 'selected' : ''; ?>><?php echo $m; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                           <label for="id_tipo_documento" class="form-label">Tipo de Documento</label>
                           <select class="form-select" id="id_tipo_documento" name="id_tipo_documento" required>
                               <option value="">-- Selecione --</option>
                               <?php foreach ($tipos_documento as $tipo): ?>
                                   <option value="<?php echo $tipo['id']; ?>" <?php echo ($registro_atual['id_tipo_documento'] == $tipo['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipo['nome']); ?></option>
                               <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="col-md-6 mb-3">
                           <label for="id_classificacao" class="form-label">Classificação (Categoria)</label>
                           <select class="form-select" id="id_classificacao" name="id_classificacao">
                               <option value="">-- Nenhuma --</option>
                               <?php foreach ($categorias as $categoria): ?>
                                   <option value="<?php echo $categoria['id']; ?>" <?php echo ($registro_atual['id_classificacao'] == $categoria['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($categoria['nome']); ?></option>
                               <?php endforeach; ?>
                           </select>
                        </div>
                    </div>
                    
                    <hr>

                    <h5 class="mb-3 mt-4">Detalhes Específicos de "<?php echo htmlspecialchars($secao['nome']); ?>"</h5>
                    <div class="p-3 bg-white rounded border">
                        <?php if (empty($campos_dinamicos)): ?>
                            <p class="text-muted">Esta seção não possui campos específicos.</p>
                        <?php else: ?>
                            <?php foreach ($campos_dinamicos as $campo):
                                $valor_atual = $valores_atuais[$campo['id']] ?? '';
                            ?>
                                <div class="mb-3">
                                    <label for="campo_<?php echo $campo['id']; ?>" class="form-label"><?php echo htmlspecialchars($campo['nome_campo']); ?></label>
                                    <?php if ($campo['tipo_campo'] == 'select'): ?>
                                        <select class="form-select" id="campo_<?php echo $campo['id']; ?>" name="valores[<?php echo $campo['id']; ?>]">
                                            <option value="">-- Selecione --</option>
                                            <?php
                                            if (strpos($campo['opcoes_campo'], 'tabela:') === 0) {
                                                $nome_tabela = substr($campo['opcoes_campo'], 7);
                                                $opcoes_db = $pdo->query("SELECT nome FROM `$nome_tabela` ORDER BY nome ASC")->fetchAll(PDO::FETCH_COLUMN);
                                                foreach ($opcoes_db as $opcao) { echo '<option value="' . htmlspecialchars($opcao) . '" ' . ($valor_atual == $opcao ? 'selected' : '') . '>' . htmlspecialchars($opcao) . '</option>'; }
                                            } else {
                                                $opcoes = explode(',', $campo['opcoes_campo']);
                                                foreach ($opcoes as $opcao) { $opcao_trim = trim($opcao); echo '<option value="' . htmlspecialchars($opcao_trim) . '" ' . ($valor_atual == $opcao_trim ? 'selected' : '') . '>' . htmlspecialchars($opcao_trim) . '</option>'; }
                                            }
                                            ?>
                                        </select>
                                    <?php elseif ($campo['tipo_campo'] == 'anexo'): ?>
                                        <?php if (!empty($valor_atual) && file_exists('../' . $valor_atual)): ?>
                                            <p class="form-text mb-1">Arquivo atual: <a href="<?php echo htmlspecialchars('../'.$valor_atual); ?>" target="_blank">Ver anexo</a></p>
                                            <input type="hidden" name="caminho_antigo[<?php echo $campo['id']; ?>]" value="<?php echo htmlspecialchars($valor_atual); ?>">
                                        <?php endif; ?>
                                        <input type="file" class="form-control" id="campo_<?php echo $campo['id']; ?>" name="valores_files[<?php echo $campo['id']; ?>]">
                                    <?php elseif ($campo['tipo_campo'] == 'textarea'): ?>
                                        <textarea class="form-control" id="campo_<?php echo $campo['id']; ?>" name="valores[<?php echo $campo['id']; ?>]" rows="3"><?php echo htmlspecialchars($valor_atual); ?></textarea>
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
                                        <input type="<?php echo $tipo_input; ?>" class="form-control <?php echo $extra_class; ?>" id="campo_<?php echo $campo['id']; ?>" name="valores[<?php echo $campo['id']; ?>]" value="<?php echo htmlspecialchars($valor_atual); ?>">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        <a href="ver_lancamentos.php?portal_id=<?php echo $portal_id; ?>" class="btn btn-secondary">Cancelar</a>
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