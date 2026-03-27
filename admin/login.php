<?php
session_start();
require_once '../conexao.php';

// Se o usuário já está logado, redireciona para o painel
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $senha   = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT id, usuario, nome, senha, perfil, id_perfil FROM usuarios_admin WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['admin_logged_in']      = true;
        $_SESSION['admin_user_id']        = $user['id'];
        $_SESSION['admin_user_id_perfil'] = $user['id_perfil'];
        $_SESSION['admin_user_nome']      = $user['usuario'];
        $_SESSION['admin_user_nome_real'] = $user['nome']; 
        $_SESSION['admin_user_perfil']    = $user['perfil'];

        header("Location: dashboard.php");
        exit;
    } else {
        $erro = "Usuário ou senha inválidos!";
    }

}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Restrito - Portal da Transparência</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.4);
            --primary-accent: #2563eb;
        }

        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Outfit', sans-serif;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f172a; /* Fallback */
        }

        /* --- BACKGROUND ANIMADO --- */
        .bg-animated {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        .circle {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            animation: float 20s infinite alternate ease-in-out;
        }

        .circle-1 {
            width: 500px;
            height: 500px;
            background: rgba(37, 99, 235, 0.15);
            top: -100px;
            left: -100px;
            animation-duration: 15s;
        }

        .circle-2 {
            width: 400px;
            height: 400px;
            background: rgba(147, 51, 234, 0.15);
            bottom: -50px;
            right: -50px;
            animation-duration: 25s;
        }

        .circle-3 {
            width: 300px;
            height: 300px;
            background: rgba(14, 165, 233, 0.1);
            top: 40%;
            left: 30%;
            animation-duration: 18s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(100px, 50px) scale(1.1); }
            100% { transform: translate(-50px, 100px) scale(0.9); }
        }

        /* --- LOGIN CARD GLASSMORPHISM --- */
        .login-container {
            width: 100%;
            max-width: 560px;
            padding: 30px;
            perspective: 2000px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.85); /* Slightly less translucent for better contrast */
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 32px;
            padding: 50px;
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.4);
            animation: cardEnter 1s cubic-bezier(0.22, 1, 0.36, 1);
        }

        @keyframes cardEnter {
            from { opacity: 0; transform: translateY(30px) rotateX(-10deg); }
            to { opacity: 1; transform: translateY(0) rotateX(0); }
        }

        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .login-header i {
            font-size: 3.5rem;
            color: white;
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            width: 90px;
            height: 90px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 24px;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4);
        }

        .login-header h1 {
            font-weight: 700;
            font-size: 2rem;
            color: #1e293b;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .login-header p {
            color: #64748b;
            font-size: 1rem;
            margin-top: 8px;
            font-weight: 400;
        }

        /* --- FORM STYLING --- */
        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #334155;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .input-group {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .input-group:focus-within {
            background: #fff;
            border-color: var(--primary-accent);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .input-group-text {
            background: transparent;
            border: none;
            color: #94a3b8;
            padding-left: 15px;
        }

        .form-control {
            background: transparent;
            border: none;
            padding: 12px 15px;
            font-size: 1rem;
            color: #1e293b;
        }

        .form-control:focus {
            background: transparent;
            box-shadow: none;
            color: #1e293b;
        }

        .btn-login {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            border-radius: 14px;
            padding: 16px;
            font-weight: 700;
            color: white;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            margin-top: 15px;
            letter-spacing: 1px;
        }

        .btn-login:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert-custom {
            border-radius: 12px;
            padding: 12px;
            font-size: 0.9rem;
            border: none;
            background: rgba(239, 68, 68, 0.1);
            color: #b91c1c;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
        }

        .footer-text {
            text-align: center;
            margin-top: 30px;
            color: #94a3b8;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

    <div class="bg-animated">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
        <div class="circle circle-3"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-shield-lock-fill"></i>
                <h1>Painel Administrativo</h1>
                <p>Portal da Transparência 2025</p>
            </div>

            <?php if ($erro): ?>
                <div class="alert-custom" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php echo $erro; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" autocomplete="off">
                <div class="mb-4">
                    <label for="usuario" class="form-label">Usuário</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Digite seu usuário..." required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="senha" class="form-label">Senha</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="password" class="form-control" id="senha" name="senha" placeholder="Digite sua senha..." required>
                        <span class="input-group-text" id="togglePassword" style="cursor: pointer;">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-login">
                        ACESSAR SISTEMA <i class="bi bi-arrow-right-short ms-1"></i>
                    </button>
                </div>
            </form>

            <div class="footer-text">
                &copy; <?php echo date('Y'); ?> - Todos os direitos reservados.
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
            
            // Alterna o ícone
            if (type === 'text') {
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
            } else {
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
            }
        });
    </script>

</body>
</html>