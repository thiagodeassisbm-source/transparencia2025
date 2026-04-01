<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Pega as informações do usuário para o cabeçalho e permissões
$perfil_usuario = $_SESSION['admin_user_perfil'];

// Lógica para processar o formulário de novo card (simplificada)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_card'])) {
    // A criação completa de cards agora é feita em criar_secoes.php
    // Esta é apenas uma funcionalidade de atalho, se mantida.
    // ... (lógica de adicionar card)
    header("Location: gerenciar_cards.php");
    exit;
}

// Busca os cards existentes para listar na página
$pref_id = $_SESSION['id_prefeitura'] ?? 0;
// Debug: Versão 2026.03.30.3 (Inclusiva para órfãos)
$stmt = $pdo->prepare("SELECT c.*, cat.nome as nome_categoria, p.nome as nome_secao 
                      FROM cards_informativos c 
                      LEFT JOIN categorias cat ON c.id_categoria = cat.id 
                      LEFT JOIN portais p ON c.id_secao = p.id 
                      WHERE (c.id_prefeitura = ? OR p.id_prefeitura = ? OR c.id_prefeitura IS NULL OR c.id_prefeitura = 0)
                      ORDER BY c.ordem ASC, c.id DESC");
$stmt->execute([$pref_id, $pref_id]);
$cards = $stmt->fetchAll();

// Lista oficial de páginas do sistema para o badge
$paginas_sistema_nomes = [
    'estrutura.php' => 'Estrutura Org.',
    'ouvidoria.php' => 'Ouvidoria',
    'sic.php' => 'e-Sic',
    'faq.php' => 'FAQ',
    'relatorio_publicacoes.php' => 'Relat. Publicações'
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Cards - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light-subtle">

<?php
$page_title_for_header = 'Gerenciar Cards';
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding">
    <div class="row">
        <div class="col-12">
            <?php
            if (isset($_SESSION['mensagem_sucesso'])) {
                echo '<div class="alert alert-info alert-dismissible fade show" role="alert">' 
                   . htmlspecialchars($_SESSION['mensagem_sucesso']) . 
                   '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                unset($_SESSION['mensagem_sucesso']);
            }
            ?>
            <div class="alert alert-primary">
                Para criar novos cards com opções avançadas (link para páginas internas ou sites externos), por favor, use o menu <a href="criar_secoes.php" class="alert-link">Nova Seção/Card</a>.
            </div>
            <div class="card">
                <div class="card-header">Cards Cadastrados</div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Ordem</th>
                                <th>Ícone</th>
                                <th>Título</th>
                                <th>Categoria</th>
                                <th>Link de Destino</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cards as $card): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($card['ordem']); ?></td>
                                <td>
                                    <?php if (($card['tipo_icone'] ?? 'imagem') === 'bootstrap'): ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi <?php echo htmlspecialchars($card['caminho_icone']); ?> text-primary fs-5"></i>
                                        </div>
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($card['caminho_icone']); ?>" alt="Ícone" style="width: 40px; height: 40px; object-fit: contain;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($card['titulo']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($card['subtitulo']); ?></small>
                                </td>
                                <td><span class="badge bg-dark"><?php echo htmlspecialchars($card['nome_categoria'] ?? 'N/A'); ?></span></td>
                                <td>
                                    <?php
                                    $link_atual = trim($card['link_url'] ?? '');
                                    if (!empty($card['id_secao'])) {
                                        echo '<span class="badge bg-info text-dark">' . htmlspecialchars($card['nome_secao'] ?? 'Seção Interna') . '</span>';
                                    } elseif (!empty($link_atual)) {
                                        if (strpos($link_atual, 'pagina.php?slug=') !== false) {
                                            echo '<span class="badge bg-success">Página Interna</span>';
                                        } elseif (isset($paginas_sistema_nomes[$link_atual])) {
                                            echo '<span class="badge bg-warning text-dark"><i class="bi bi-cpu-fill me-1"></i>Página do Sistema: ' . $paginas_sistema_nomes[$link_atual] . '</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">Link Externo</span>';
                                        }
                                    } else {
                                        echo '<span class="badge bg-light text-dark border">Nenhum Link</span>';
                                    }
                                    ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="editar_card.php?id=<?php echo $card['id']; ?>" class="btn btn-outline-primary btn-sm rounded-3 d-flex align-items-center justify-content-center shadow-none" style="width: 32px; height: 32px;" title="Editar Card">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="excluir_card.php" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este card?');">
                                            <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                                            <input type="hidden" name="caminho_icone" value="<?php echo htmlspecialchars($card['caminho_icone']); ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-3 d-flex align-items-center justify-content-center shadow-none" style="width: 32px; height: 32px;" title="Excluir Card">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
</body>
</html>