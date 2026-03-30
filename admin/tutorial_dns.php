<?php
// admin/tutorial_dns.php
require_once 'auth_check.php';
require_once '../conexao.php';

// Apenas Superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] !== 1) {
    header("Location: dashboard.php");
    exit;
}

$id_pref = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$exemplo_slug = "seuslug";
$exemplo_dominio = "transparencia.seudominio.com.br";
$is_subdominio = true;

if ($id_pref) {
    $stmt = $pdo->prepare("SELECT slug, dominio_customizado FROM prefeituras WHERE id = ?");
    $stmt->execute([$id_pref]);
    $pref = $stmt->fetch();
    if ($pref) {
        $exemplo_slug = $pref['slug'] ?: 'seuslug';
        if (!empty($pref['dominio_customizado'])) {
            $exemplo_dominio = trim(str_replace('www.', '', $pref['dominio_customizado']));
            $host_parts = explode('.', $exemplo_dominio);
            if (count($host_parts) > 2 && $host_parts[0] !== 'www') {
                $is_subdominio = true;
            } else {
                $is_subdominio = false;
            }
        }
    }
}

$page_title_for_header = 'Guia Definitivo: Configuração DNS e Whitelabel';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-header bg-dark text-white p-4 rounded-top-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0 fw-bold"><i class="bi bi-hdd-network me-2"></i> Guia Definitivo: DNS & Whitelabel</h4>
                        <p class="mb-0 text-white-50 opacity-75">Siga estas etapas para plugar um novo domínio perfeitamente.</p>
                    </div>
                    <?php if ($id_pref): ?>
                        <a href="editar_prefeitura.php?id=<?php echo $id_pref; ?>" class="btn btn-outline-light btn-sm rounded-pill px-4">Voltar</a>
                    <?php else: ?>
                        <a href="cadastrar_prefeitura.php" class="btn btn-outline-light btn-sm rounded-pill px-4">Voltar</a>
                    <?php endif; ?>
                </div>

                <div class="card-body p-4 p-md-5">
                    
                    <div class="alert alert-info border-0 shadow-sm rounded-3 mb-5">
                        <h5 class="alert-heading fw-bold"><i class="bi bi-info-circle-fill me-2"></i> Análise Automática</h5>
                        <p class="mb-0">Baseado no que você preencheu, o domínio alvo é <strong><?php echo htmlspecialchars($exemplo_dominio); ?></strong> e a prefeitura alvo é <strong><?php echo htmlspecialchars($exemplo_slug); ?></strong>.</p>
                        <p class="mb-0 mt-2">
                            <?php if ($is_subdominio): ?>
                                <span class="badge bg-primary">Diagnóstico: Subdomínio</span> Isso significa que você usará apontamento via <strong>CNAME</strong> na Zona DNS.
                            <?php else: ?>
                                <span class="badge bg-danger">Diagnóstico: Domínio Raiz</span> Isso requer exclusão prévia do site no Hostinger (se houver) e apontamentos via <strong>IP (A)</strong> ou direto no servidor.
                            <?php endif; ?>
                        </p>
                    </div>

                    <h5 class="fw-bold text-primary border-bottom pb-2 mb-4 d-flex align-items-center">
                        <span class="bg-primary text-white rounded-circle d-inline-flex justify-content-center align-items-center me-2" style="width: 28px; height: 28px; font-size: 14px;">1</span> O Lado do Cliente (DNS)
                    </h5>
                    
                    <?php if ($is_subdominio): ?>
                        <div class="bg-light p-4 rounded-3 border mb-5">
                            <p class="mb-3">Acesse o painel onde o domínio está registrado (Hostgator, Cloudflare, Registro.br, etc.) e adicione um novo registro na <strong>Zona DNS</strong>:</p>
                            <ul class="list-group list-group-flush border rounded">
                                <li class="list-group-item bg-transparent"><strong>Tipo (Type):</strong> <code>CNAME</code></li>
                                <li class="list-group-item bg-transparent"><strong>Nome / Host:</strong> <code><?php echo htmlspecialchars(explode('.', $exemplo_dominio)[0]); ?></code> <small class="text-muted">(Apenas a primeira palavra)</small></li>
                                <li class="list-group-item bg-transparent"><strong>Destino / Valor / Aponta para:</strong> <code>upgyn.com.br</code></li>
                                <li class="list-group-item bg-transparent border-bottom-0"><strong>TTL:</strong> <code>Automático</code> ou <code>14400</code></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="bg-light p-4 rounded-3 border mb-5">
                            <p class="mb-3">Como se trata de um domínio raiz (<code><?php echo htmlspecialchars($exemplo_dominio); ?></code>), domínios raiz não aceitam CNAME por padrão da internet.</p>
                            <p class="mb-3"><strong>Passo Crítico (Hostinger):</strong> Se já houver um "Site" criado para este domínio no seu plano Hostinger, você deve **Deletar / Excluir o Site** no painel inicial do Hostinger para liberar as amarras do nome e permitir os próximos passos.</p>
                            <p class="text-muted small"><i class="bi bi-exclamation-triangle-fill text-warning"></i> Atenção: Só faça isso se o domínio for exclusivo para a transparência e não hospedar outros sistemas ou e-mails corporativos.</p>
                        </div>
                    <?php endif; ?>

                    <h5 class="fw-bold text-primary border-bottom pb-2 mb-4 d-flex align-items-center">
                        <span class="bg-primary text-white rounded-circle d-inline-flex justify-content-center align-items-center me-2" style="width: 28px; height: 28px; font-size: 14px;">2</span> O Seu Lado (Painel Hostinger)
                    </h5>
                    
                    <div class="bg-light p-4 rounded-3 border mb-5">
                        <p class="mb-3">Vá para o painel de controle do site hospedeiro central (<strong>upgyn.com.br</strong>) dentro do Hostinger.</p>
                        <ol class="mb-0">
                            <li class="mb-2">No menu lateral esquerdo, clique em <strong>Domínios &rarr; Domínios Estacionados</strong> (Parked Domains / Alias).</li>
                            <li class="mb-2">Na caixa de texto, escreva o domínio completo: <strong><code><?php echo htmlspecialchars($exemplo_dominio); ?></code></strong></li>
                            <li class="mb-2">Clique no botão de <strong>Estacionar</strong>.</li>
                            <li class="mb-2">Aguarde a mensagem de Sucesso (se der erro de já existir, garanta que qualquer rastro antigo dele como Subdomínio independente foi apagado no Hostinger).</li>
                            <li>Acesse a aba <strong>Segurança &rarr; SSL</strong> e ative o certificado gratuito (HTTPS) para o novo domínio que apareceu na lista, garantindo o cadeado de segurança para os cidadãos.</li>
                        </ol>
                    </div>

                    <h5 class="fw-bold text-success border-bottom border-success pb-2 mb-4 d-flex align-items-center">
                        <span class="bg-success text-white rounded-circle d-inline-flex justify-content-center align-items-center me-2" style="width: 28px; height: 28px; font-size: 14px;">3</span> O Pulo do Gato (O Código .htaccess Mágico)
                    </h5>
                    
                    <div class="row g-4 align-items-stretch">
                        <div class="col-md-5">
                            <div class="p-3 border rounded h-100 border-success-subtle bg-success-subtle bg-opacity-10">
                                <p class="small text-muted mb-3"><i class="bi bi-info-circle-fill me-1"></i> Se parássemos agora, quando a pessoa abrir o domínio customizado acima, ela cairia na página inicial da UP GYN ou na Landing Page genérica.<br><br>Precisamos interceptar a visita e enviá-la para os arquivos da Central de forma invisível, já marcando a prefeitura que irá carregar.</p>
                                <hr>
                                <p class="fw-bold small mb-2 text-dark">Ação Necessária:</p>
                                <p class="small text-muted mb-0">No Gerenciador de Arquivos do Hostinger do site original (upgyn.com.br), acesse a pasta Raiz (📁 <code>public_html</code>) e edite o arquivo vazio ou existente chamado <code>.htaccess</code>. <strong>Cole o código ao lado bem no topo dele.</strong></p>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="position-relative h-100">
                                <div class="bg-dark p-3 rounded-3 shadow-sm h-100 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-center border-bottom border-secondary pb-2 mb-3">
                                        <span class="text-white-50 small font-monospace">public_html/.htaccess</span>
                                        <button class="btn btn-sm btn-outline-light text-nowrap rounded-2 py-0 border-0" onclick="copyCode(this)">
                                            <i class="bi bi-clipboard"></i> Copiar
                                        </button>
                                    </div>
                                    <pre class="mb-0 text-white font-monospace" style="font-size: 0.85rem; overflow-x: auto; overflow-y: hidden;" id="htaccessSnippet">RewriteEngine On

# 1. Se o visitante abriu esse domínio customizado
RewriteCond %{HTTP_HOST} ^(www\.)?<?php echo str_replace('.', '\.', htmlspecialchars($exemplo_dominio)); ?>$ [NC]
# 2. Impede o servidor de engasgar entrando em looping eterno
RewriteCond %{REQUEST_URI} !^/sistemas/transparencia2026/
# 3. Força a rota invisível + injeta o slug exato desta prefeitura
RewriteRule ^(.*)$ /sistemas/transparencia2026/$1?pref_slug=<?php echo htmlspecialchars($exemplo_slug); ?> [QSA,L]</pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 text-center px-lg-5">
                        <i class="bi bi-check-circle-fill text-success fs-1 mb-2 d-block"></i>
                        <h5 class="fw-bold">Prontinho! Roteamento Completo.</h5>
                        <p class="text-muted">A internet inteira ligou os pontos. Dentro de alguns minutos, quando acessar <code><?php echo htmlspecialchars($exemplo_dominio); ?></code> o navegador mostrará de imediato todo o conteúdo da prefeitura <strong><?php echo htmlspecialchars($exemplo_slug); ?></strong>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyCode(btn) {
    const code = document.getElementById('htaccessSnippet').innerText;
    navigator.clipboard.writeText(code).then(() => {
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2"></i> Copiado!';
        btn.classList.replace('btn-outline-light', 'btn-success');
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.replace('btn-success', 'btn-outline-light');
        }, 3000);
    });
}
</script>

<?php include 'admin_footer.php'; ?>
