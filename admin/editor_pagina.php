<?php
require_once 'auth_check.php';
require_once '../conexao.php';
if ($_SESSION['admin_user_perfil'] !== 'admin') { header("Location: index.php"); exit; }

$id_pagina = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$pagina = ['titulo' => '', 'conteudo' => ''];
$anexos_existentes = [];

// Lógica de salvar (criar ou atualizar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        $titulo = trim($_POST['titulo']);
        $conteudo = $_POST['conteudo'];
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titulo)));

        if ($id_pagina) { // Atualizar página existente
            $stmt = $pdo->prepare("UPDATE paginas SET titulo = ?, conteudo = ?, slug = ? WHERE id = ?");
            $stmt->execute([$titulo, $conteudo, $slug, $id_pagina]);
        } else { // Criar nova página
            $stmt = $pdo->prepare("INSERT INTO paginas (titulo, conteudo, slug) VALUES (?, ?, ?)");
            $stmt->execute([$titulo, $conteudo, $slug]);
            $id_pagina = $pdo->lastInsertId();
        }

        // Processar novos anexos
        if (isset($_FILES['novos_anexos'])) {
            $stmt_anexo = $pdo->prepare("INSERT INTO pagina_anexos (id_pagina, titulo_anexo, caminho_arquivo) VALUES (?, ?, ?)");
            $upload_dir = '../uploads/paginas/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

            foreach ($_FILES['novos_anexos']['name'] as $index => $nome_arquivo) {
                if ($_FILES['novos_anexos']['error'][$index] === UPLOAD_ERR_OK) {
                    $titulo_anexo = $_POST['novos_anexos_titulos'][$index];
                    $nome_unico = uniqid() . '-' . basename($nome_arquivo);
                    $caminho_destino = $upload_dir . $nome_unico;

                    if (move_uploaded_file($_FILES['novos_anexos']['tmp_name'][$index], $caminho_destino)) {
                        $stmt_anexo->execute([$id_pagina, $titulo_anexo, $caminho_destino]);
                    }
                }
            }
        }
        $pdo->commit();
        $_SESSION['mensagem_sucesso'] = "Página salva com sucesso!";
        header("Location: gerenciar_paginas.php");
        exit;
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['mensagem_sucesso'] = "Erro ao salvar a página: " . $e->getMessage();
        header("Location: editor_pagina.php" . ($id_pagina ? "?id=$id_pagina" : ""));
        exit;
    }
}

// Se estiver editando, busca os dados da página e seus anexos
if ($id_pagina) {
    $stmt = $pdo->prepare("SELECT titulo, conteudo FROM paginas WHERE id = ?");
    $stmt->execute([$id_pagina]);
    $pagina = $stmt->fetch();
    $stmt_anexos = $pdo->prepare("SELECT id, titulo_anexo, caminho_arquivo FROM pagina_anexos WHERE id_pagina = ?");
    $stmt_anexos->execute([$id_pagina]);
    $anexos_existentes = $stmt_anexos->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo $id_pagina ? 'Editar' : 'Criar'; ?> Página - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/lang/summernote-pt-BR.js"></script>
</head>
<body class="bg-light-subtle">

<?php 
$page_title_for_header = $id_pagina ? 'Editar Página' : 'Criar Nova Página';
include 'admin_header.php'; 
?>

<div class="container-fluid container-custom-padding">
    <div class="row"><div class="col-12">
        <div class="card">
            <div class="card-header"><h4>Editor de Página</h4></div>
            <div class="card-body">
                <form method="POST" action="editor_pagina.php<?php if($id_pagina) echo '?id='.$id_pagina; ?>" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título da Página</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($pagina['titulo']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="conteudo-editor" class="form-label">Conteúdo</label>
                        <textarea id="conteudo-editor" name="conteudo"><?php echo htmlspecialchars($pagina['conteudo']); ?></textarea>
                    </div>
                    
                    <hr>
                    <h5 class="mt-4">Anexos</h5>
                    <?php if(!empty($anexos_existentes)): ?>
                        <p>Documentos já anexados:</p>
                        <ul class="list-group mb-3">
                            <?php foreach($anexos_existentes as $anexo): ?>
                                <li class="list-group-item"><i class="bi bi-file-earmark-text"></i> <?php echo htmlspecialchars($anexo['titulo_anexo']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div id="novos-anexos-container"></div>
                    <button type="button" id="add-anexo-btn" class="btn btn-outline-primary mt-2"><i class="bi bi-plus-circle"></i> Anexar Documento</button>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-success">Salvar Página</button>
                        <a href="gerenciar_paginas.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div></div>
</div>

<?php include 'admin_footer.php'; ?>

<script>
  $(document).ready(function() {
    $('#conteudo-editor').summernote({
      placeholder: 'Comece a escrever o conteúdo da sua página aqui...',
      lang: 'pt-BR',
      height: 400,
      toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'clear']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['table', ['table']],
        ['insert', ['link', 'picture', 'video']],
        ['view', ['fullscreen', 'codeview', 'help']]
      ]
    });
  });

  // SCRIPT PARA ADICIONAR ANEXOS
  document.getElementById('add-anexo-btn').addEventListener('click', function() {
      const container = document.getElementById('novos-anexos-container');
      const anexoIndex = container.children.length;
      const newAnexoDiv = document.createElement('div');
      newAnexoDiv.className = 'row align-items-end mb-2 border p-2 rounded bg-light';
      newAnexoDiv.innerHTML = `
          <div class="col-md-5"><label for="anexo_titulo_${anexoIndex}" class="form-label small">Título do Documento</label><input type="text" class="form-control form-control-sm" id="anexo_titulo_${anexoIndex}" name="novos_anexos_titulos[]" required></div>
          <div class="col-md-6"><label for="anexo_file_${anexoIndex}" class="form-label small">Arquivo</label><input type="file" class="form-control form-control-sm" id="anexo_file_${anexoIndex}" name="novos_anexos[]" required></div>
          <div class="col-md-1"><button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.parentElement.remove()"><i class="bi bi-trash"></i></button></div>
      `;
      container.appendChild(newAnexoDiv);
  });
</script>
</body>
</html>