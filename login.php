<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: painel.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Chamados Contec</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <!-- Coluna da Esquerda (Branding) -->
        <div class="login-left">
            <div class="branding-content">
                <!-- << NOVO >> Logo adicionada aqui -->
                <img src="img/logo_contec.png" alt="Logo Contec" class="login-logo">
                <h1>Seja Bem-Vindo</h1>
                <p>Estamos aqui para ajudar — ou pelo menos parecer que sabemos o que estamos fazendo!</p>
            </div>
        </div>

        <!-- Coluna da Direita (Formulário) -->
        <div class="login-right">
            <!-- Container do Formulário -->
            <div id="form-container">
                <h2>Entrar</h2>
                <div id="error-message-container">
                    <?php
                    if (isset($_SESSION['login_error'])) {
                        echo '<div class="error-message">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
                        unset($_SESSION['login_error']);
                    }
                    ?>
                </div>
                <form id="login-form" action="processa_login.php" method="POST">
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-at input-icon"></i>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" id="senha" name="senha" required>
                            <i class="fa-solid fa-eye-slash" id="toggle-senha"></i>
                        </div>
                    </div>
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember">Lembrar-me
                        </label>
                    </div>
                    <div class="form-group">
                        <button type="submit" id="submit-button">Acessar Sistema</button>
                    </div>
                </form>
            </div>

            <!-- Container da Animação de Sucesso (escondido por padrão) -->
            <div id="success-animation" class="hidden">
                <svg class="success-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="success-checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="success-checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg>
                <h2>Login Efetuado!</h2>
                <p>A redirecionar para o painel...</p>
            </div>
        </div>
    </div>

    <!-- Chamando o novo ficheiro JavaScript -->
    <script src="js/login_animation.js"></script>
</body>
</html>
