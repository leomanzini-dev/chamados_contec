<?php
session_start(); // Inicia a sessão para poder manipulá-la

// 1. Desfaz todas as variáveis da sessão
$_SESSION = array();

// 2. Se é desejável destruir a sessão completamente, apague também o cookie de sessão.
// Nota: Isso destruirá a sessão, e não apenas os dados da sessão!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, // Define um tempo no passado para expirar o cookie
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finalmente, destrói a sessão no servidor
session_destroy();

// 4. Redireciona para a página de login
header("Location: login.php");
exit(); // Garante que nenhum código adicional seja executado após o redirecionamento
?>