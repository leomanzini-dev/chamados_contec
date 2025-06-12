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
// Contar não lidas
$sql_notificacoes = "SELECT COUNT(id) AS total_nao_lidas FROM notificacoes WHERE id_usuario_destino = ? AND lida = FALSE";
$stmt_notificacoes = $conexao->prepare($sql_notificacoes);
$stmt_notificacoes->bind_param("i", $id_usuario_logado);
$stmt_notificacoes->execute();
$resultado_notificacoes = $stmt_notificacoes->get_result()->fetch_assoc();
$total_nao_lidas = $resultado_notificacoes['total_nao_lidas'];
$stmt_notificacoes->close();

// Buscar as 5 últimas notificações para o dropdown
$sql_lista_notif = "SELECT id, id_ticket, mensagem, data_criacao FROM notificacoes WHERE id_usuario_destino = ? ORDER BY data_criacao DESC LIMIT 5";
$stmt_lista = $conexao->prepare($sql_lista_notif);
$stmt_lista->bind_param("i", $id_usuario_logado);
$stmt_lista->execute();
$lista_notificacoes = $stmt_lista->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_lista->close();
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
</head>
<body>
    <div class="dashboard-container">
