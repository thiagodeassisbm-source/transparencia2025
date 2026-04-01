<?php
session_start();
require_once '../conexao.php';
require_once 'functions_logs.php';

// Se o usuário já está logado, redireciona para o painel
// O sistema sempre mostrará a tela de login para permitir a entrada no contexto correto da prefeitura
// (Mesmo que já haja um usuário logado em outra prefeitura ou como superadmin)

// 1. Busca o logo e o título (SaaS Unificado)
$pref_slug = filter_input(INPUT_GET, 'slug', FILTER_SANITIZE_SPECIAL_CHARS);
$id_prefeitura_contexto = null;

if ($pref_slug) {
    $stmt_p = $pdo->prepare("SELECT id FROM prefeituras WHERE slug = ?");
    $stmt_p->execute([$pref_slug]);
    $id_prefeitura_contexto = $stmt_p->fetchColumn();
}

// Configuração de Identidade (Se não houver slug, usa o padrão Global)
if ($id_prefeitura_contexto) {
    $sql_conf = "SELECT chave, valor FROM configuracoes WHERE id_prefeitura = $id_prefeitura_contexto AND chave IN ('prefeitura_logo', 'prefeitura_titulo')";
    $stmt_conf = $pdo->query($sql_conf);
    $config = $stmt_conf->fetchAll(PDO::FETCH_KEY_PAIR);
    $logo_prefeitura = !empty($config['prefeitura_logo']) ? $config['prefeitura_logo'] : 'imagens/logo-up.png';
    $titulo_portal = $config['prefeitura_titulo'] ?? 'Gestão de Transparência';
} else {
    // Branding Global SaaS
    $logo_prefeitura = 'imagens/logo-up.png';
    $titulo_portal = 'Painel Administrativo Central';
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_ou_email = $_POST['usuario'];
    $senha            = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT id, usuario, email, nome, senha, perfil, id_perfil, is_superadmin, id_prefeitura FROM usuarios_admin WHERE usuario = ? OR email = ?");
    $stmt->execute([$usuario_ou_email, $usuario_ou_email]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha'])) {
        // Verifica se a prefeitura está ativa (apenas para usuários comuns)
        if ($user['is_superadmin'] == 0 && $user['id_prefeitura']) {
            $stmt_status = $pdo->prepare("SELECT status FROM prefeituras WHERE id = ?");
            $stmt_status->execute([$user['id_prefeitura']]);
            $status_prefeitura = $stmt_status->fetchColumn();

            if ($status_prefeitura === 'suspenso') {
                registrar_log($pdo, 'BLOQUEADO', 'usuarios_admin', "Tentativa de login bloqueada: Prefeitura suspensa.");
                $erro = "Seu acesso está suspenso temporariamente. Entre em contato com o suporte.";
                // Interrompe o login
            }
        }

        if (empty($erro)) {
            $_SESSION['admin_logged_in']      = true;
            $_SESSION['admin_user_id']        = $user['id'];
            $_SESSION['admin_user_id_perfil'] = $user['id_perfil'];
            $_SESSION['admin_user_nome']      = $user['usuario'];
            $_SESSION['admin_user_nome_real'] = $user['nome']; 
            $_SESSION['admin_user_perfil']    = $user['perfil'];
            $_SESSION['is_superadmin']        = (int)$user['is_superadmin'];
            $_SESSION['id_prefeitura']        = $user['id_prefeitura'] ?? null;

            registrar_log($pdo, 'LOGIN', 'usuarios_admin', "Usuário logou com sucesso.");

            if ($_SESSION['is_superadmin'] === 1) {
                header("Location: /sistemas/transparencia2026/admin/super_dashboard.php");
            } else {
                header("Location: /sistemas/transparencia2026/admin/dashboard.php");
            }
            exit;
        }
    } else {
        registrar_log($pdo, 'FALHA-LOGIN', 'usuarios_admin', "Tentativa de login falhou.");
        $erro = "Usuário ou senha inválidos!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Restrito - <?php echo htmlspecialchars($titulo_portal); ?></title>
    <script>
    (function () {
        try {
            var n = parseInt(localStorage.getItem('fontSize'), 10);
            if (!isNaN(n) && n >= 12 && n <= 32) document.documentElement.style.fontSize = n + 'px';
            if (localStorage.getItem('highContrast') === 'true') document.documentElement.classList.add('high-contrast');
        } catch (e) {}
    })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --primary-purple: #6366f1;
            --dark-bg: #0f172a;
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%);
            color: #fff;
            overflow: auto;
        }

        .login-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            position: relative;
            z-index: 1;
        }

        /* Efeito de rede/plexus no fundo global */
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: radial-gradient(rgba(99, 102, 241, 0.15) 1.5px, transparent 1.5px);
            background-size: 30px 30px;
            z-index: -1;
            opacity: 0.5;
        }

        .login-content {
            display: flex;
            width: 100%;
            max-width: 1100px;
            gap: 80px;
            align-items: center;
            justify-content: center;
        }

        /* --- LADO ESQUERDO (CONTEÚDO) --- */
        .left-side {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center; /* Centraliza itens */
            text-align: center; /* Centraliza texto */
        }

        .brand-container {
            margin-bottom: 40px;
            animation: fadeInDown 0.8s ease-out;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .brand-logo-wrapper {
            width: 130px;
            height: 130px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            margin-bottom: 25px;
            position: relative;
            box-shadow: 0 0 30px rgba(99, 102, 241, 0.2);
        }

        .brand-logo-wrapper img {
            max-width: 85%;
            max-height: 85%;
            filter: drop-shadow(0 0 5px rgba(255,255,255,0.3));
        }

        .brand-logo-wrapper::after {
            content: '';
            position: absolute;
            inset: -8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: pulse 4s infinite;
        }

        .brand-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: -1px;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-content p.subtitle {
            font-size: 1.25rem;
            color: #94a3b8;
            margin-bottom: 30px;
        }

        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: left; /* Lista alinhada à esquerda para leitura */
            align-self: flex-start; /* Alinha a lista à esquerda do container centralizado */
            margin-left: auto;
            margin-right: auto;
            display: inline-block;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 18px;
            font-size: 1.05rem;
            color: #cbd5e1;
            animation: fadeInLeft 0.8s ease-out both;
        }

        .feature-item i {
            width: 26px;
            height: 26px;
            background: rgba(99, 102, 241, 0.2);
            border: 1px solid rgba(99, 102, 241, 0.4);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #818cf8;
            font-size: 0.8rem;
        }

        .more-text {
            margin-top: 25px;
            font-style: italic;
            color: #64748b;
            font-size: 0.9rem;
        }

        /* --- LADO DIREITO (LOGIN) --- */
        .right-side {
            flex: 0.9;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 32px;
            padding: 45px;
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.5);
            animation: fadeInRight 0.8s ease-out;
        }

        .login-card-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .icon-box {
            width: 55px;
            height: 55px;
            background: rgba(99, 102, 241, 0.2);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-purple);
            font-size: 1.3rem;
            margin-bottom: 15px;
        }

        .login-card-header h2 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: #fff;
        }

        .login-card-header p {
            color: #64748b;
            font-size: 0.9rem;
        }

        .form-label {
            color: #64748b;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .input-group {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .input-group:focus-within {
            border-color: var(--primary-purple);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .input-group-text {
            background: transparent;
            border: none;
            color: #475569;
            padding-left: 15px;
        }

        .form-control {
            background: transparent;
            border: none;
            color: #fff;
            padding: 13px 15px;
            font-size: 0.95rem;
        }

        .form-control:focus {
            background: transparent;
            color: #fff;
            box-shadow: none;
        }

        .btn-login {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 700;
            color: white;
            transition: all 0.3s ease;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
            filter: brightness(1.1);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            padding: 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-links {
            text-align: center;
            margin-top: 25px;
            font-size: 0.8rem;
            color: #64748b;
        }

        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
        }

        .footer-links a:hover {
            color: #fff;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.3; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Responsividade */
        @media (max-width: 992px) {
            .login-content { flex-direction: column; gap: 50px; }
            .left-side { flex: none; }
            .right-side { flex: none; width: 100%; }
            .brand-content h1 { font-size: 2.5rem; }
            .feature-list { align-self: center; }
        }

        html.high-contrast body {
            background: #000000 !important;
            background-image: none !important;
        }
        html.high-contrast .login-card {
            background: #111111 !important;
            border-color: #ffffff !important;
            color: #ffffff !important;
        }
        html.high-contrast .brand-content h1 {
            -webkit-text-fill-color: #ffffff;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-content">
            <!-- LADO ESQUERDO -->
            <div class="left-side">
                <div class="brand-container">
                    <div class="brand-logo-wrapper">
                        <?php if ($id_prefeitura_contexto && !empty($config['prefeitura_logo'])): ?>
                            <img src="<?php echo htmlspecialchars($logo_prefeitura); ?>" alt="Logo Prefeitura">
                        <?php else: ?>
                            <i class="bi bi-bank text-white" style="font-size: 4rem;"></i>
                        <?php endif; ?>
                    </div>
                    <div class="brand-content">
                        <h1>Realize o seu Login</h1>
                        <p class="subtitle"><?php echo htmlspecialchars($titulo_portal); ?></p>
                    </div>
                </div>

                <div class="feature-wrapper">
                    <ul class="feature-list">
                        <li class="feature-item" style="animation-delay: 0.1s">
                            <i class="bi bi-check-lg"></i>
                            Gerenciamento centralizado de dados da Transparência
                        </li>
                        <li class="feature-item" style="animation-delay: 0.2s">
                            <i class="bi bi-check-lg"></i>
                            Controle de acesso e logs de auditoria
                        </li>
                        <li class="feature-item" style="animation-delay: 0.3s">
                            <i class="bi bi-check-lg"></i>
                            Monitoramento em tempo real de licitações e contratos
                        </li>
                        <li class="feature-item" style="animation-delay: 0.4s">
                            <i class="bi bi-check-lg"></i>
                            Relatórios dinâmicos e inteligência de dados
                        </li>
                        <li class="feature-item" style="animation-delay: 0.5s">
                            <i class="bi bi-check-lg"></i>
                            Suporte técnico especializado
                        </li>
                    </ul>
                </div>

                <p class="more-text">E muito mais...</p>
            </div>

            <!-- LADO DIREITO -->
            <div class="right-side">
                <div class="login-card">
                    <div class="login-card-header">
                        <div class="icon-box">
                            <i class="bi bi-lock-fill"></i>
                        </div>
                        <h2>Bem-vindo de volta</h2>
                        <p>Acesse sua conta para continuar</p>
                    </div>

                    <?php if ($erro): ?>
                        <div class="alert-error">
                            <i class="bi bi-exclamation-circle"></i>
                            <?php echo $erro; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" autocomplete="off">
                        <div class="mb-4">
                            <label for="usuario" class="form-label">Usuário / E-mail</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="usuario" name="usuario" placeholder="admin" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="senha" class="form-label">Senha de Acesso</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                <input type="password" class="form-control" id="senha" name="senha" placeholder="********" required>
                                <span class="input-group-text" id="togglePassword" style="cursor: pointer;">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </span>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-login">
                                <i class="bi bi-box-arrow-in-right"></i> Entrar no Sistema
                            </button>
                        </div>
                    </form>

                    <div class="footer-links">
                        <p class="mb-1"><i class="bi bi-shield-check me-1"></i> Conexão segura e criptografada</p>
                        <p>Desenvolvido por <a href="<?php echo get_config_global($pdo, 'copyright_dev_site', 'https://upgyn.com.br'); ?>" target="_blank"><?php echo get_config_global($pdo, 'copyright_dev_nome', 'UPGYN'); ?></a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#senha');
        const eyeIcon = document.querySelector('#eyeIcon');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            if (type === 'text') {
                eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    </script>
    <script src="../js/acessibilidade.js"></script>

</body>
</html>