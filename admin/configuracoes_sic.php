<?php
require_once 'auth_check.php';
require_once '../conexao.php';
require_once 'functions_logs.php';

// Bloqueia acesso se não for admin
if ($_SESSION['admin_user_perfil'] !== 'admin' && (int)$_SESSION['is_superadmin'] !== 1) {
    header("Location: index.php");
    exit;
}

$pref_id = $_SESSION['id_prefeitura'] ?? 0;

// Se for SuperAdmin e estiver sem contexto de prefeitura, bloqueia ou redireciona
if ($pref_id === 0 && (int)$_SESSION['is_superadmin'] !== 1) {
    $_SESSION['mensagem_erro'] = "Contexto de prefeitura não identificado.";
    header("Location: index.php");
    exit;
}

// Lógica para salvar as configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configuracoes = $_POST['config'];
    
    foreach ($configuracoes as $chave => $valor) {
        $valor_limpo = trim($valor);
        
        // Verifica se a chave já existe para esta prefeitura
        $stmt_check = $pdo->prepare("SELECT id FROM configuracoes WHERE id_prefeitura = ? AND chave = ?");
        $stmt_check->execute([$pref_id, $chave]);
        $id_existente = $stmt_check->fetchColumn();

        if ($id_existente) {
            $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ? WHERE id = ?");
            $stmt->execute([$valor_limpo, $id_existente]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO configuracoes (id_prefeitura, chave, valor) VALUES (?, ?, ?)");
            $stmt->execute([$pref_id, $chave, $valor_limpo]);
        }
    }

    registrar_log($pdo, 'EDIÇÃO', 'configuracoes', "Atualizou as configurações do e-SIC para a prefeitura ID: $pref_id");

    $_SESSION['mensagem_sucesso'] = "Dados do SIC Físico atualizados com sucesso!";
    header("Location: configuracoes_sic.php");
    exit;
}

// Busca as configurações atuais do banco filtrando pela prefeitura logada
$stmt = $pdo->prepare("SELECT chave, valor FROM configuracoes WHERE id_prefeitura = ? AND chave LIKE 'sic_%'");
$stmt->execute([$pref_id]);
$config_atuais = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Fallbacks para exibição no formulário (mesmos do sic.php público)
$config_atuais['sic_setor'] = $config_atuais['sic_setor'] ?? 'Controladoria Municipal';
$config_atuais['sic_endereco'] = $config_atuais['sic_endereco'] ?? 'Avenida das Nações, S/N - Centro';
$config_atuais['sic_responsavel'] = $config_atuais['sic_responsavel'] ?? 'João da Silva';
$config_atuais['sic_email'] = $config_atuais['sic_email'] ?? 'sic@municipio.gov.br';
$config_atuais['sic_telefone'] = $config_atuais['sic_telefone'] ?? '(62) 3123-4567';
$config_atuais['sic_horario'] = $config_atuais['sic_horario'] ?? 'Segunda a Sexta, das 08h às 17h';

$page_title_for_header = 'Configurações do SIC';
include 'admin_header.php';
?>

<div class="container-fluid container-custom-padding py-4">
    <div class="row">
        <div class="col-12">
            
            <div class="row align-items-center mb-4">
                <div class="col-md-7">
                    <h3 class="fw-bold text-dark mb-1">Configurações do SIC</h3>
                    <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i> Gerencie as informações de contato do Serviço de Informação ao Cidadão físico da sua prefeitura.</p>
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

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h6 class="mb-1 fw-bold text-dark"><i class="bi bi-geo-alt-fill text-primary me-2"></i>Informações de Contato do SIC Físico</h6>
                    <p class="text-muted small mb-0">Estes dados aparecerão na página pública do SIC.</p>
                </div>
                <div class="card-body bg-light bg-opacity-10 p-4">
                    <form method="POST" action="configuracoes_sic.php">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="sic_setor" class="form-label fw-bold small text-muted text-uppercase">Setor Responsável</label>
                                <input type="text" class="form-control border-0 shadow-sm p-3" id="sic_setor" name="config[sic_setor]" value="<?php echo htmlspecialchars($config_atuais['sic_setor']); ?>" style="border-radius: 12px;">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="sic_responsavel" class="form-label fw-bold small text-muted text-uppercase">Nome do Responsável</label>
                                <input type="text" class="form-control border-0 shadow-sm p-3" id="sic_responsavel" name="config[sic_responsavel]" value="<?php echo htmlspecialchars($config_atuais['sic_responsavel']); ?>" style="border-radius: 12px;">
                            </div>

                            <div class="col-12">
                                <label for="sic_endereco" class="form-label fw-bold small text-muted text-uppercase">Endereço Completo</label>
                                <input type="text" class="form-control border-0 shadow-sm p-3" id="sic_endereco" name="config[sic_endereco]" value="<?php echo htmlspecialchars($config_atuais['sic_endereco']); ?>" style="border-radius: 12px;">
                            </div>

                            <div class="col-md-4">
                                <label for="sic_email" class="form-label fw-bold small text-muted text-uppercase">E-mail de Contato</label>
                                <input type="email" class="form-control border-0 shadow-sm p-3" id="sic_email" name="config[sic_email]" value="<?php echo htmlspecialchars($config_atuais['sic_email']); ?>" style="border-radius: 12px;">
                            </div>

                            <div class="col-md-4">
                                <label for="sic_telefone" class="form-label fw-bold small text-muted text-uppercase">Telefone / Ramal</label>
                                <input type="text" class="form-control border-0 shadow-sm p-3" id="sic_telefone" name="config[sic_telefone]" value="<?php echo htmlspecialchars($config_atuais['sic_telefone']); ?>" style="border-radius: 12px;">
                            </div>

                            <div class="col-md-4">
                                <label for="sic_horario" class="form-label fw-bold small text-muted text-uppercase">Horário de Atendimento</label>
                                <input type="text" class="form-control border-0 shadow-sm p-3" id="sic_horario" name="config[sic_horario]" value="<?php echo htmlspecialchars($config_atuais['sic_horario']); ?>" style="border-radius: 12px;">
                            </div>

                            <div class="col-12 mt-5">
                                <button type="submit" class="btn btn-primary btn-lg shadow px-5" style="border-radius: 12px;">
                                    <i class="bi bi-save me-2"></i>Salvar Configurações
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Card de Ajuda -->
            <div class="alert alert-info border-0 shadow-sm rounded-4 p-4 d-flex align-items-center">
                <i class="bi bi-lightbulb-fill fs-2 me-3 text-warning"></i>
                <div class="small">
                    <strong>Dica Técnica:</strong> Estas configurações são específicas para a sua prefeitura. O sistema utiliza isolamento por ID para garantir que as informações de contato sejam únicas para cada portal municipal.
                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
</body>
</html>