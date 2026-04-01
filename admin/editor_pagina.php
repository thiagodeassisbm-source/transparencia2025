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

        $pref_id = $_SESSION['id_prefeitura'];

        if ($id_pagina) { // Atualizar página existente
            $stmt = $pdo->prepare("UPDATE paginas SET titulo = ?, conteudo = ?, slug = ? WHERE id = ? AND id_prefeitura = ?");
            $stmt->execute([$titulo, $conteudo, $slug, $id_pagina, $pref_id]);
        } else { // Criar nova página
            $stmt = $pdo->prepare("INSERT INTO paginas (titulo, conteudo, slug, id_prefeitura) VALUES (?, ?, ?, ?)");
            $stmt->execute([$titulo, $conteudo, $slug, $pref_id]);
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
    $pref_id = $_SESSION['id_prefeitura'];
    $stmt = $pdo->prepare("SELECT titulo, conteudo FROM paginas WHERE id = ? AND id_prefeitura = ?");
    $stmt->execute([$id_pagina, $pref_id]);
    $pagina = $stmt->fetch();
    
    if (!$pagina) {
        $_SESSION['mensagem_sucesso'] = "Página não encontrada ou sem permissão.";
        header("Location: gerenciar_paginas.php");
        exit;
    }
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
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/lang/summernote-pt-BR.js"></script>
    <style>
        .note-editor { background: #fff !important; border-radius: 12px !important; border: 1px solid #e2e8f0 !important; overflow: hidden; }
        .note-toolbar { background: #f8fafc !important; border-bottom: 1px solid #e2e8f0 !important; padding: 10px !important; }
        .note-btn { background: #fff !important; border: 1px solid #e2e8f0 !important; border-radius: 6px !important; padding: 5px 10px !important; color: #475569 !important; }
        .note-btn:hover { background: #f1f5f9 !important; }
    </style>
</head>
<body class="bg-light-subtle">

<?php 
$page_title_for_header = $id_pagina ? 'Editar Página' : 'Criar Nova Página';
include 'admin_header.php'; 
?>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-xl-10 mx-auto">
            
            <div class="d-flex align-items-center mb-4">
                <a href="gerenciar_paginas.php" class="btn btn-light border-0 shadow-sm me-3 text-muted">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
                <div>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $id_pagina ? 'Editar Página' : 'Nova Página de Conteúdo'; ?></h3>
                    <p class="text-muted small mb-0">Crie conteúdos ricos com textos, imagens e anexos.</p>
                </div>
            </div>

            <!-- Card Informativo -->
            <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color: #fff; border-radius: 12px;">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="me-4 d-none d-md-block">
                        <div class="bg-white bg-opacity-20 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-magic fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Dica de Formatação</h5>
                        <p class="mb-0 opacity-90 small">
                            Você pode copiar textos do Word ou Excel e colar aqui. O sistema manterá a formatação básica. Use a barra de ferramentas para mudar cores e tamanhos de fonte conforme necessário.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom border-light">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-pencil-square me-2 text-primary"></i>Editor de Conteúdo Profissional</h6>
                </div>
                <div class="card-body p-4">
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
      height: 500,
      fontSizes: ['8', '9', '10', '11', '12', '14', '16', '18', '20', '22', '24', '28', '32', '36', '48', '64', '72', '80', '96', '110', '120'],
      toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'clear']],
        ['fontsize', ['fontsize']],
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