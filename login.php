<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: painel.php");
    exit();
}
$titulo_pagina = "Login";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> - Sistema de Chamados Contec</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/login.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="login-page-container">
        <div class="login-branding" id="animated-bg">
            <div class="branding-overlay">
                <h1>Suporte Inteligente. Soluções Reais.</h1>
                <p>Acesse o sistema para abrir e gerenciar seus chamados de forma simples e eficiente.</p>
            </div>
        </div>

        <div class="login-form-area">
            <div id="form-container">
                <img src="img/logo_contec.png" alt="Logo Contec" class="form-logo">
                
                <form id="login-form" action="processa_login.php" method="POST" novalidate>
                    <h2>Acesse sua Conta</h2>
                    <div id="error-message-container">
                        <?php
                        if (isset($_SESSION['login_error'])) {
                            echo '<div class="error-message">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
                            unset($_SESSION['login_error']);
                        }
                        ?>
                    </div>
                    
                    <div class="form-group">
                        <i class="fa-solid fa-at input-icon"></i>
                        <input type="email" id="email" name="email" required>
                        <label for="email">E-mail</label>
                    </div>

                    <div class="form-group">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="senha" name="senha" required>
                        <label for="senha">Senha</label>
                        <i class="fa-solid fa-eye" id="toggle-senha"></i>
                    </div>

                    <div class="form-options">
                        <label class="remember-me"><input type="checkbox" name="remember"> Lembrar-me</label>
                        <a href="#" class="forgot-password">Esqueceu a senha?</a>
                    </div>
                    
                    <button type="submit" id="submit-button" class="btn-login">Acessar Sistema</button>
                </form>
            </div>

            <div id="success-animation" class="hidden">
                <svg class="success-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="success-checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="success-checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg>
                <h2>Login Efetuado!</h2>
                <p>Redirecionando para o painel...</p>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.net.min.js"></script>
    <script src="js/login_animation.js?v=<?php echo time(); ?>"></script>
</body>
</html>