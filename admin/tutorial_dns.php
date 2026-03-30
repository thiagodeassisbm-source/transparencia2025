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
$exemplo_slug = "";
$exemplo_dominio = "";

if ($id_pref) {
    $stmt = $pdo->prepare("SELECT slug, dominio_customizado FROM prefeituras WHERE id = ?");
    $stmt->execute([$id_pref]);
    $pref = $stmt->fetch();
    if ($pref) {
        $exemplo_slug = $pref['slug'] ?: '';
        if (!empty($pref['dominio_customizado'])) {
            $exemplo_dominio = trim(str_replace('www.', '', $pref['dominio_customizado']));
        }
    }
}

$page_title_for_header = 'Gerador de Roteamento DNS e .htaccess';
include 'admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-header bg-dark text-white p-4 rounded-top-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0 fw-bold"><i class="bi bi-hdd-network me-2"></i> Gerador Interativo: DNS & Whitelabel</h4>
                        <p class="mb-0 text-white-50 opacity-75">Preencha os campos abaixo para gerar seu tutorial personalizado.</p>
                    </div>
                    <?php if ($id_pref): ?>
                        <a href="editar_prefeitura.php?id=<?php echo $id_pref; ?>" class="btn btn-outline-light btn-sm rounded-pill px-4">Voltar</a>
                    <?php else: ?>
                        <a href="cadastrar_prefeitura.php" class="btn btn-outline-light btn-sm rounded-pill px-4">Voltar</a>
                    <?php endif; ?>
                </div>

                <div class="card-body p-4 p-md-5">
                    
                    <!-- Fomulário Interativo -->
                    <div class="row g-4 mb-5 p-4 bg-light rounded-4 border">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">Qual vai ser o domínio que será ligado aqui?</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                <input type="text" id="inputDomain" class="form-control" placeholder="ex: transparencia.cidade.gov.br" value="<?php echo htmlspecialchars($exemplo_dominio); ?>">
                            </div>
                            <small class="text-muted">É o link que o cidadão vai digitar no navegador.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary">Qual a Sigla Interna (Slug) dessa prefeitura no sistema?</label>
                            <div class="input-group">
                                <span class="input-group-text">/portal/</span>
                                <input type="text" id="inputSlug" class="form-control" placeholder="ex: orizona" value="<?php echo htmlspecialchars($exemplo_slug); ?>">
                            </div>
                            <small class="text-muted">É a pasta virtual dela no Multi-Prefeituras.</small>
                        </div>
                    </div>

                    <!-- Analise -->
                    <div id="tutorialContent" style="display: none;">
                        <div class="alert alert-info border-0 shadow-sm rounded-3 mb-5">
                            <h5 class="alert-heading fw-bold"><i class="bi bi-info-circle-fill me-2"></i> Análise do Domínio: <span class="ex_dom_text"></span></h5>
                            <p class="mb-0 mt-2" id="analiseSubdominio" style="display:none;">
                                <span class="badge bg-primary fs-6 me-2">Tipo: Subdomínio</span> Isso significa que você usará apontamento via <strong>CNAME</strong> lá na Zona DNS do seu cliente.
                            </p>
                            <p class="mb-0 mt-2" id="analiseRaiz" style="display:none;">
                                <span class="badge bg-danger fs-6 me-2">Tipo: Domínio Raiz</span> Domínios raízes <strong>NÃO</strong> aceitam CNAME. Você deverá usar apontamento <strong>Tipo A</strong> (para o IP da Hostinger) ou <strong>ALIAS</strong> na Zona DNS.
                            </p>
                        </div>

                        <!-- Passo 1 -->
                        <h5 class="fw-bold text-primary border-bottom pb-2 mb-4 d-flex align-items-center">
                            <span class="bg-primary text-white rounded-circle d-inline-flex justify-content-center align-items-center me-2" style="width: 28px; height: 28px; font-size: 14px;">1</span> O Lado do Cliente (Criar Lá na Zona DNS Delineada)
                        </h5>
                        
                        <!-- Conteudo Subdominio -->
                        <div id="passo1Subdominio" class="bg-light p-4 rounded-3 border mb-5" style="display:none;">
                            <p class="mb-3">A equipe de TI da prefeitura acessa o painel deles (Registro.br, Cloudflare, Hostgator) e cria uma linha na <strong>Zona DNS</strong>:</p>
                            <ul class="list-group list-group-flush border rounded">
                                <li class="list-group-item bg-transparent"><strong>Tipo (Type):</strong> <code>CNAME</code></li>
                                <li class="list-group-item bg-transparent"><strong>Nome / Entrada:</strong> <code class="ex_dom_prefixo fw-bold fs-6"></code> <small class="text-muted">(Apenas o primeiro nome)</small></li>
                                <li class="list-group-item bg-transparent"><strong>Destino / Aponta p/:</strong> <code class="fw-bold fs-6">upgyn.com.br</code></li>
                                <li class="list-group-item bg-transparent border-bottom-0"><strong>TTL:</strong> <code>Automático</code></li>
                            </ul>
                        </div>
                        
                        <!-- Conteudo Raiz -->
                        <div id="passo1Raiz" class="bg-light p-4 rounded-3 border mb-5" style="display:none;">
                            <ul class="list-group list-group-flush border rounded">
                                <li class="list-group-item bg-transparent text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Aviso: Trata-se de um Domínio Raiz (<span class="ex_dom_text"></span>).</strong></li>
                                <li class="list-group-item bg-transparent">O técnico precisará mapear ele por <strong>Tipo A</strong> apontando diretamente para o <strong>IP do servidor da Hostinger</strong>, ou usar um <strong>ALIAS</strong>.</li>
                                <li class="list-group-item bg-transparent">Se esse domínio for de teste seu e já tiver um "Site" criado pra ele no Hostinger, <strong>você precisa Excluir esse site antigo</strong> do painel antes de avançar para destravar o nome.</li>
                            </ul>
                        </div>

                        <!-- Passo 2 -->
                        <h5 class="fw-bold text-primary border-bottom pb-2 mb-4 d-flex align-items-center">
                            <span class="bg-primary text-white rounded-circle d-inline-flex justify-content-center align-items-center me-2" style="width: 28px; height: 28px; font-size: 14px;">2</span> O Seu Lado (Estacionar no Painel Hostinger)
                        </h5>
                        
                        <div class="bg-light p-4 rounded-3 border mb-5">
                            <p class="mb-3">Vá para o painel de controle do site hospedeiro central (<strong>upgyn.com.br</strong>) dentro do Hostinger.</p>
                            <ol class="mb-0">
                                <li class="mb-2">No menu esquerdo, vá em <strong>Domínios &rarr; Domínios Estacionados</strong> (Parked / Alias).</li>
                                <li class="mb-2">Digite o endereço exato que montamos: <strong><code class="ex_dom_text fw-bold fs-6"></code></strong></li>
                                <li class="mb-2">Clique em <strong>Estacionar</strong> e espere dar Sucesso.</li>
                                <li><strong>Obrigatório:</strong> Acesse a aba <strong>Segurança &rarr; SSL</strong> e ative o certificado gratuito HTTPS para o <span class="ex_dom_text text-dark fw-bold"></span>. Sem ele, a tela do cidadão dará erro de segurança.</li>
                            </ol>
                        </div>

                        <!-- Passo 3 -->
                        <h5 class="fw-bold text-success border-bottom border-success pb-2 mb-4 d-flex align-items-center">
                            <span class="bg-success text-white rounded-circle d-inline-flex justify-content-center align-items-center me-2" style="width: 28px; height: 28px; font-size: 14px;">3</span> O Pulo do Gato (O Seu Código .htaccess Exclusivo)
                        </h5>
                        
                        <div class="row g-4 align-items-stretch mb-4">
                            <div class="col-md-5">
                                <div class="p-3 border rounded h-100 border-success-subtle bg-success-subtle bg-opacity-10">
                                    <p class="fw-bold mb-2 text-dark">Ação de Roteamento:</p>
                                    <p class="small text-muted mb-0">No Hostinger do <strong>upgyn.com.br</strong>, acesse o <strong>Gerenciador de Arquivos</strong> -> pasta raiz <code>public_html</code>.<br><br>Abra o arquivo invisível chamado <strong>.htaccess</strong> e <strong>Cole exatamente este código inteiro no TOPO</strong> do arquivo.</p>
                                    <div class="mt-3 small p-2 bg-white rounded border border-warning shadow-sm">
                                        <i class="bi bi-lightning-fill text-warning"></i> Esse código amarra quem entrar por `<span class="ex_dom_text fw-bold"></span>` diretamente e exclusivamente para a pasta invisível de `<span class="ex_slug_text fw-bold"></span>`.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="position-relative h-100">
                                    <div class="bg-dark p-3 rounded-3 shadow-sm h-100 d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-center border-bottom border-secondary pb-2 mb-3">
                                            <span class="text-white-50 small font-monospace">public_html/.htaccess</span>
                                            <button class="btn btn-sm btn-outline-light text-nowrap rounded-2 py-0 border-0" onclick="copyCode(this)">
                                                <i class="bi bi-clipboard"></i> Copiar Código gerado
                                            </button>
                                        </div>
                                        <pre class="mb-0 text-white font-monospace" style="font-size: 0.82rem; overflow-x: auto; overflow-y: hidden;" id="htaccessSnippet"></pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center px-lg-5 pt-3">
                            <i class="bi bi-globe-americas text-primary fs-1 mb-2 d-block"></i>
                            <h5 class="fw-bold">Tudo Ligado!</h5>
                            <p class="text-muted">A internet inteira ligou os pontos. Assim que o DNS propagar, o link <code><span class="ex_dom_text"></span></code> abrirá invisivelmente a <span class="ex_slug_text text-capitalize text-dark fw-bold"></span>.</p>
                        </div>
                    </div>
                    
                    <div id="initialState" class="text-center py-5 text-muted">
                        <i class="bi bi-arrow-up-circle fs-2 mb-3 d-block opacity-50"></i>
                        <h6 class="fw-bold">Aguardando os dados...</h6>
                        <p class="small">Preencha o domínio e o slug lá em cima para eu gerar o seu passo a passo.</p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const inputDomain = document.getElementById("inputDomain");
    const inputSlug = document.getElementById("inputSlug");
    const tutorialContent = document.getElementById("tutorialContent");
    const initialState = document.getElementById("initialState");

    function cleanDomain(d) {
        return d.trim().replace(/^https?:\/\//i, '').replace(/^www\./i, '').replace(/\/.*$/, '');
    }

    function isSubdomain(d) {
        const parts = d.split('.');
        if (parts.length < 2) return false;
        
        // Final .br (com.br, gov.br etc) == 2 parts for TLD
        const isBrTLD = (parts[parts.length - 1] === 'br' && ['com', 'gov', 'org', 'net', 'edu', 'leg', 'mil', 'jus'].includes(parts[parts.length - 2]));
        
        if (isBrTLD) {
            return parts.length > 3; // a.gov.br (3) -> Raiz | transp.a.gov.br (4) -> Sub
        } else {
            return parts.length > 2; // a.com (2) -> Raiz | transp.a.com (3) -> Sub
        }
    }

    function updateTutorial() {
        const domainRaw = inputDomain.value;
        const domain = cleanDomain(domainRaw);
        const slug = inputSlug.value.trim().replace(/[^a-zA-Z0-9_-]/g, '').toLowerCase();

        if (domain === "" || slug === "") {
            tutorialContent.style.display = "none";
            initialState.style.display = "block";
            return;
        }

        tutorialContent.style.display = "block";
        initialState.style.display = "none";

        const isSub = isSubdomain(domain);
        
        // Atualiza textos
        document.querySelectorAll(".ex_dom_text").forEach(el => el.textContent = domain);
        document.querySelectorAll(".ex_slug_text").forEach(el => el.textContent = slug);
        
        // Atualiza Lógica Sub vs Raiz
        if (isSub) {
            document.getElementById("analiseSubdominio").style.display = "block";
            document.getElementById("analiseRaiz").style.display = "none";
            document.getElementById("passo1Subdominio").style.display = "block";
            document.getElementById("passo1Raiz").style.display = "none";
            
            const prefix = domain.split('.')[0];
            document.querySelectorAll(".ex_dom_prefixo").forEach(el => el.textContent = prefix);
        } else {
            document.getElementById("analiseSubdominio").style.display = "none";
            document.getElementById("analiseRaiz").style.display = "block";
            document.getElementById("passo1Subdominio").style.display = "none";
            document.getElementById("passo1Raiz").style.display = "block";
        }

        // Gera HTACCESS
        const escapedDomain = domain.replace(/\./g, '\\.');
        const htaccessText = `RewriteEngine On

# 1. Se o visitante abriu esse domínio customizado
RewriteCond %{HTTP_HOST} ^(www\\.)?${escapedDomain}$ [NC]
# 2. Impede repetições eternas do servidor
RewriteCond %{REQUEST_URI} !^/sistemas/transparencia2026/
# 3. Força a rota invisível + injeta o slug [${slug}]
RewriteRule ^(.*)$ /sistemas/transparencia2026/$1?pref_slug=${slug} [QSA,L]`;

        document.getElementById("htaccessSnippet").textContent = htaccessText;
    }

    inputDomain.addEventListener("input", updateTutorial);
    inputSlug.addEventListener("input", updateTutorial);
    
    // Roda no carregamento inicial se tiver valores
    updateTutorial();
});

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
