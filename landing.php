<?php
require_once 'conexao.php';

// 1. Busca todas as prefeituras ativas e seus logos (se houver)
// Usamos uma query que junta as informações necessárias para a vitrine
$sql = "SELECT p.id, p.nome, p.slug, 
               (SELECT valor FROM configuracoes WHERE id_prefeitura = p.id AND chave = 'prefeitura_logo' LIMIT 1) as logo
        FROM prefeituras p 
        WHERE p.status = 'ativo'
        ORDER BY p.nome ASC";

$stmt = $pdo->query($sql);
$prefeituras = $stmt->fetchAll();

// Definimos algumas cores padrão para a landing page (moderna e clean)
$primary_color = "#0f172a"; // Navy Dark Slate
$accent_color = "#3b82f6";  // Blue 500
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal da Transparência Municipal - Gestão Transparente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: <?php echo $primary_color; ?>;
            --accent: <?php echo $accent_color; ?>;
            --bg-glass: rgba(255, 255, 255, 0.1);
            --bg-glass-heavy: rgba(255, 255, 255, 0.85);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            overflow-x: hidden;
        }

        /* Hero Section with Mesh Gradient */
        .hero-section {
            position: relative;
            background: radial-gradient(at top left, #1e293b 0%, #0f172a 100%);
            padding: 100px 0 160px;
            color: #fff;
            overflow: hidden;
            text-align: center;
        }

        /* Abstract blobs for modern background */
        .hero-section::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            background: var(--accent);
            filter: blur(120px);
            opacity: 0.2;
            z-index: 0;
            border-radius: 50%;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 1.5rem;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: #94a3b8;
            max-width: 700px;
            margin: 0 auto 3rem;
            font-weight: 300;
            line-height: 1.6;
        }

        /* Search Bar Glassmorphism */
        .search-container {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }

        .search-input-wrapper {
            background: var(--bg-glass);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .search-input-wrapper:focus-within {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
        }

        .search-input-wrapper input {
            background: transparent;
            border: none;
            color: #fff;
            padding: 12px 25px;
            width: 100%;
            font-size: 1.1rem;
            outline: none;
        }

        .search-input-wrapper input::placeholder {
            color: rgba(255,255,255,0.6);
        }

        .search-btn {
            background: var(--accent);
            color: #fff;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.2s ease;
        }

        .search-btn:hover {
            transform: scale(1.05);
            background: #2563eb;
        }

        /* Cities Grid */
        .cities-section {
            margin-top: -80px;
            padding-bottom: 80px;
            position: relative;
            z-index: 10;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-header h2 {
            font-weight: 800;
            color: var(--primary);
            font-size: 2rem;
        }

        .city-card {
            background: #fff;
            border-radius: 20px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            border: 1px solid #f1f5f9;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: inherit;
        }

        .city-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 40px -10px rgba(0,0,0,0.08);
            border-color: var(--accent);
        }

        .city-logo-wrapper {
            width: 100px;
            height: 100px;
            background: #f8fafc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            padding: 15px;
            transition: all 0.3s ease;
            position: relative;
        }

        .city-card:hover .city-logo-wrapper {
            background: rgba(59, 130, 246, 0.05);
            transform: scale(1.1);
        }

        .city-logo-wrapper img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .city-logo-wrapper i {
            font-size: 2.5rem;
            color: #94a3b8;
        }

        .city-name {
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .city-tag {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            background: rgba(59, 130, 246, 0.08);
            padding: 4px 12px;
            border-radius: 50px;
        }

        /* Features Section */
        .features-grid {
            padding: 80px 0;
            background: #fff;
        }

        .feature-item {
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .feature-title {
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .feature-text {
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Footer Modern */
        .landing-footer {
            background: #0f172a;
            color: rgba(255,255,255,0.6);
            padding: 80px 0 40px;
        }

        .footer-logo {
            font-weight: 800;
            color: #fff;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .footer-logo i {
            color: var(--accent);
            margin-right: 10px;
        }

        .footer-hr {
            border-color: rgba(255, 255, 255, 0.1);
            margin: 40px 0;
        }

        /* Utility for city card search filtering */
        .d-none-filtered {
            display: none !important;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container hero-content">
            <h1 class="hero-title">Portal da Transparência</h1>
            <p class="hero-subtitle">
                A plataforma moderna para acesso a informações públicas, diários oficiais e transparência municipal em um único lugar.
            </p>

            <div class="search-container">
                <div class="search-input-wrapper">
                    <input type="text" id="citySearch" placeholder="Busque por sua cidade..." autocomplete="off">
                    <button class="search-btn">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Cities Showcase -->
    <section class="cities-section">
        <div class="container px-4">
            <div id="cityGrid" class="row g-4">
                <?php if (!empty($prefeituras)): ?>
                    <?php foreach ($prefeituras as $pref): ?>
                        <div class="col-6 col-md-4 col-lg-3 city-item" data-name="<?php echo strtolower($pref['nome']); ?>">
                            <a href="portal/<?php echo $pref['slug']; ?>" class="city-card">
                                <div class="city-logo-wrapper shadow-sm">
                                    <?php 
                                    $logo_path = !empty($pref['logo']) ? str_replace('../', '', $pref['logo']) : '';
                                    if ($logo_path && file_exists(__DIR__ . '/' . $logo_path)): 
                                    ?>
                                        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars($pref['nome']); ?>">
                                    <?php else: ?>
                                        <i class="bi bi-bank"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="city-name text-truncate w-100"><?php echo htmlspecialchars($pref['nome']); ?></div>
                                <div class="city-tag">Acessar Portal</div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <div class="alert alert-light shadow-sm">Nenhuma prefeitura cadastrada no momento.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-grid">
        <div class="container">
            <div class="section-header">
                <h2>Recursos do Sistema</h2>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="bi bi-clipboard-data"></i></div>
                        <h4 class="feature-title">Dados Abertos</h4>
                        <p class="feature-text">Informações detalhadas sobre receitas, despesas, folha de pagamento e contratos em formato acessível.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="bi bi-journal-text"></i></div>
                        <h4 class="feature-title">Diário Oficial</h4>
                        <p class="feature-text">Publicações oficiais diárias, decretos, leis e atos administrativos com certificação digital.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
                        <h4 class="feature-title">Conformidade Legal</h4>
                        <p class="feature-text">Integralmente adequado à Lei de Acesso à Informação (LAI) e Lei de Responsabilidade Fiscal.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <div class="footer-logo">
                        <i class="bi bi-brightness-high-fill"></i> Transparência 2026
                    </div>
                    <p>Fomentando a transparência pública e o controle social.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="admin/login.php" class="btn btn-outline-light rounded-pill px-4 btn-sm">
                        <i class="bi bi-lock me-2"></i>Acesso Restrito
                    </a>
                </div>
            </div>
            <hr class="footer-hr">
            <div class="text-center small">
                &copy; <?php echo date('Y'); ?> UP GYN Sistemas. Todos os direitos reservados.
            </div>
        </div>
    </footer>

    <script>
        // Simple filter for city search
        const searchInput = document.getElementById('citySearch');
        const cityItems = document.querySelectorAll('.city-item');

        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase().trim();
            
            cityItems.forEach(item => {
                const name = item.getAttribute('data-name');
                if (name.includes(term)) {
                    item.classList.remove('d-none-filtered');
                } else {
                    item.classList.add('d-none-filtered');
                }
            });
        });
    </script>
</body>
</html>
