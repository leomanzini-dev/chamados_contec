<?php
// includes/header.php (VERSÃO OTIMIZADA)
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
$departamento_usuario = $_SESSION['usuario_departamento'] ?? null;
$pagina_atual = basename($_SERVER['PHP_SELF']);

// --- LÓGICA PARA BUSCAR NOTIFICAÇÕES ---
// (Seu código de notificações continua aqui, o omiti por brevidade)
// ...
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title><?php echo isset($titulo_pagina) ? $titulo_pagina . ' - ' : ''; ?>Sistema de Chamados Contec</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="stylesheet" href="/chamados_contec/css/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/chamados_contec/css/sidebar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/chamados_contec/css/dashboard.css?v=<?php echo time(); ?>">
    
    <?php if (!empty($css_pagina)): ?>
        <?php
            $caminho_css_final = (strpos($css_pagina, '../') === 0) 
                                ? str_replace('../', '/chamados_contec/', $css_pagina)
                                : '/chamados_contec/css/' . $css_pagina;
        ?>
        <link rel="stylesheet" href="<?php echo $caminho_css_final; ?>?v=<?php echo time(); ?>">
    <?php endif; ?>

    <script src="/chamados_contec/js/dashboard.js?v=<?php echo time(); ?>" defer></script>
    <script src="/chamados_contec/js/websocket_client.js?v=<?php echo time(); ?>" defer></script>
    <script src="/chamados_contec/js/notificacoes.js?v=<?php echo time(); ?>"></script>

</head>
<body data-usuario-id="<?php echo htmlspecialchars($id_usuario_logado); ?>"
      data-pagina-atual="<?php echo pathinfo($pagina_atual, PATHINFO_FILENAME); ?>"
      data-id-chamado="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : '0'; ?>">
    
<div id="toast-container"></div>
    
    <div class="dashboard-container">

<?php
$tipos_mensagem = [
    'mensagem_sucesso' => 'sucesso',
    'mensagem_erro' => 'erro',
    'mensagem_aviso' => 'aviso'
];

foreach ($tipos_mensagem as $chave_sessao => $tipo_toast) {
    if (isset($_SESSION[$chave_sessao])) {
        $mensagem_js = json_encode($_SESSION[$chave_sessao]);
        
        // Chama a função na ordem correta: showToast(MENSAGEM, TIPO)
        echo "<script> document.addEventListener('DOMContentLoaded', function() { showToast($mensagem_js, '$tipo_toast'); }); </script>";
        
        unset($_SESSION[$chave_sessao]);
    }
}
?>