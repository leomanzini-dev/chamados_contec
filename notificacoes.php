<?php
// notificacoes.php
$titulo_pagina = "Todas as Notificações";
$css_pagina = "notificacoes.css"; // Um CSS dedicado para esta página
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// --- AÇÃO 1: MARCAR TODAS AS NOTIFICAÇÕES COMO LIDAS ---
// Assim que o utilizador visita esta página, atualizamos o estado no banco de dados.
$sql_update = "UPDATE notificacoes SET lida = TRUE WHERE id_usuario_destino = ?";
$stmt_update = $conexao->prepare($sql_update);
if ($stmt_update) {
    $stmt_update->bind_param("i", $id_usuario_logado);
    $stmt_update->execute();
    $stmt_update->close();
}

// --- AÇÃO 2: BUSCAR TODAS AS NOTIFICAÇÕES DO UTILIZADOR ---
$sql_fetch = "SELECT id, id_ticket, mensagem, data_criacao FROM notificacoes WHERE id_usuario_destino = ? ORDER BY data_criacao DESC";
$stmt_fetch = $conexao->prepare($sql_fetch);
$todas_notificacoes = [];
if ($stmt_fetch) {
    $stmt_fetch->bind_param("i", $id_usuario_logado);
    $stmt_fetch->execute();
    $resultado = $stmt_fetch->get_result();
    $todas_notificacoes = $resultado->fetch_all(MYSQLI_ASSOC);
    $stmt_fetch->close();
}
?>

<div class="main-content">
    <div class="main-header">
        <h1><?php echo $titulo_pagina; ?></h1>
        <div class="user-menu">
            <div class="notificacao-sino">
                <i class="fa-solid fa-bell"></i>
                <!-- O contador não deve aparecer aqui, pois todas as notificações foram marcadas como lidas -->
            </div>
            <span>Olá, <?php echo htmlspecialchars($nome_usuario); ?>!</span>
            <a href="logout.php" class="logout-link">Sair</a>
        </div>
    </div>

    <div class="content-body">
        <div class="lista-notificacoes-container">
            <?php if (empty($todas_notificacoes)): ?>
                <div class="notificacao-item-vazio">
                    <p>Você não tem nenhuma notificação no seu histórico.</p>
                </div>
            <?php else: ?>
                <?php foreach ($todas_notificacoes as $notif): ?>
                    <a href="detalhes_chamado.php?id=<?php echo $notif['id_ticket']; ?>" class="notificacao-item-pagina">
                        <div class="icon">
                            <i class="fa-solid fa-ticket-alt"></i>
                        </div>
                        <div class="conteudo">
                            <div class="mensagem"><?php echo htmlspecialchars($notif['mensagem']); ?></div>
                            <div class="data"><?php echo htmlspecialchars(date('d/m/Y \à\s H:i', strtotime($notif['data_criacao']))); ?></div>
                        </div>
                        <div class="acao">
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
if ($conexao) {
    $conexao->close();
}
// O seu footer.php provavelmente fecha as tags body e html
// require_once 'includes/footer.php'; 
?>
