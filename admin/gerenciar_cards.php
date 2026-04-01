<?php
require_once 'auth_check.php';
require_once '../conexao.php';

// Pega as informações do usuário para o cabeçalho e permissões
$perfil_usuario = $_SESSION['admin_user_perfil'];

// Busca os cards existentes para listar na página
$pref_id = $_SESSION['id_prefeitura'] ?? 0;
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

$page_title_for_header = 'Gerenciar Cards';
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-12">
            
            <div class="row align-items-center mb-4">
                <div class="col-md-7">
                    <h3 class="fw-bold text-dark mb-1">Gerenciar Cards</h3>
                    <p class="text-muted small mb-0"><i class="bi bi-grid-3x3-gap me-1"></i> Visualize e organize os atalhos rápidos exibidos na página inicial do portal.</p>
                </div>
                <div class="col-md-5 text-md-end mt-3 mt-md-0">
                    <a href="criar_secoes.php" class="btn btn-primary shadow-sm rounded-pill px-4">
                        <i class="bi bi-plus-circle me-2"></i>Novo Card / Seção
                    </a>
                </div>
            </div>

            <?php
            if (isset($_SESSION['mensagem_sucesso'])) {
                echo '<div class="alert alert-success border-0 shadow-sm alert-dismissible fade show rounded-4" role="alert">' 
                   . '<i class="bi bi-check-circle-fill me-2"></i>' . htmlspecialchars($_SESSION['mensagem_sucesso']) . 
                   '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                unset($_SESSION['mensagem_sucesso']);
            }
            ?>

            <!-- Card Informativo -->
            <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); color: #fff; border-radius: 15px;">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="me-4 d-none d-md-block">
                        <div class="bg-white bg-opacity-20 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                            <i class="bi bi-info-circle-fill fs-2"></i>
                        </div>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1 text-white">Organização do Portal</h5>
                        <p class="mb-0 opacity-90 small">
                            Os cards são os principais pontos de acesso do cidadão. Para criar novos cards com opções avançadas (links externos ou páginas dinâmicas), utilize o botão <strong>Novo Card / Seção</strong> acima.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-ul me-2 text-primary"></i>Portfólio de Cards</h6>
                    <span class="badge bg-light text-primary rounded-pill px-3"><?php echo count($cards); ?> Total</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="p-3 text-center" style="width: 80px;">Ordem</th>
                                <th class="p-3" style="width: 100px;">Ícone</th>
                                <th class="p-3">Título / Subtítulo</th>
                                <th class="p-3">Categoria</th>
                                <th class="p-3">Destino</th>
                                <th class="p-3 text-center" style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cards)): ?>
                                <tr>
                                    <td colspan="6" class="p-5 text-center text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-3 opacity-20"></i>
                                        Nenhum card cadastrado para esta prefeitura.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cards as $card): ?>
                                    <tr>
                                        <td class="p-3 text-center fw-bold text-muted"><?php echo htmlspecialchars($card['ordem']); ?></td>
                                        <td class="p-3">
                                            <?php if (($card['tipo_icone'] ?? 'imagem') === 'bootstrap'): ?>
                                                <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                                    <i class="bi <?php echo htmlspecialchars($card['caminho_icone']); ?> fs-4"></i>
                                                </div>
                                            <?php else: ?>
                                                <div class="bg-light rounded-3 p-1" style="width: 45px; height: 45px;">
                                                    <img src="<?php echo htmlspecialchars($card['caminho_icone']); ?>" alt="Ícone" class="w-100 h-100 object-fit-contain">
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3">
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($card['titulo']); ?></div>
                                            <div class="text-muted small truncate-1"><?php echo htmlspecialchars($card['subtitulo']); ?></div>
                                        </td>
                                        <td class="p-3">
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-20 rounded-pill px-3">
                                                <?php echo htmlspecialchars($card['nome_categoria'] ?? 'Geral'); ?>
                                            </span>
                                        </td>
                                        <td class="p-3">
                                            <?php
                                            $link_atual = trim($card['link_url'] ?? '');
                                            if (!empty($card['id_secao'])) {
                                                echo '<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-3"><i class="bi bi-hdd-stack me-1"></i>' . htmlspecialchars($card['nome_secao']) . '</span>';
                                            } elseif (!empty($link_atual)) {
                                                if (strpos($link_atual, 'pagina.php?slug=') !== false) {
                                                    echo '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-3"><i class="bi bi-file-earmark-text me-1"></i>Página Interna</span>';
                                                } elseif (isset($paginas_sistema_nomes[$link_atual])) {
                                                    echo '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-3"><i class="bi bi-cpu me-1"></i>' . $paginas_sistema_nomes[$link_atual] . '</span>';
                                                } else {
                                                    echo '<span class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25 rounded-3"><i class="bi bi-link-45deg me-1"></i>Externo</span>';
                                                }
                                            } else {
                                                echo '<span class="text-muted small italic">Sem vínculo</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="p-3">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="editar_card.php?id=<?php echo $card['id']; ?>" class="btn btn-outline-primary btn-sm rounded-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Editar Card">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="POST" action="excluir_card.php" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este card?');">
                                                    <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                                                    <input type="hidden" name="caminho_icone" value="<?php echo htmlspecialchars($card['caminho_icone']); ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-3 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Excluir Card">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
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