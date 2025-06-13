<?php
// includes/header.php
require_once __DIR__ . '/../config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$id_usuario_logado = $_SESSION['usuario_id'];
$nome_usuario = $_SESSION['usuario_nome'];
$tipo_usuario = $_SESSION['usuario_tipo'];
$pagina_atual = basename($_SERVER['PHP_SELF']);

// --- LÓGICA PARA BUSCAR NOTIFICAÇÕES ---
// ... (a sua lógica de buscar notificações continua aqui)
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title><?php echo isset($titulo_pagina) ? $titulo_pagina . ' - ' : ''; ?>Sistema de Chamados Contec</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <?php if (isset($css_pagina)): ?>
        <link rel="stylesheet" href="css/<?php echo $css_pagina; ?>">
    <?php endif; ?>
    <script src="js/dashboard.js" defer></script>
    <script src="js/push_manager.js" defer></script> <!-- Carrega o gestor de notificações push -->
</head>
<body data-tipo-usuario="<?php echo htmlspecialchars($tipo_usuario); ?>">
    
    <!-- A div do popup-container foi removida daqui -->

    <div class="dashboard-container">
