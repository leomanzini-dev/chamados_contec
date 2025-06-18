<?php
// meus_chamados.php
$titulo_pagina = "Meus Chamados";
$css_pagina = "tabelas.css"; // Usa o novo CSS para as tabelas
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Busca inicial de todos os chamados do colaborador
$chamados = [];
$sql = "SELECT t.id, t.motivo_chamado, t.data_criacao, c.nome AS nome_categoria, s.nome AS nome_status
        FROM tickets AS t
        JOIN categorias AS c ON t.id_categoria = c.id
        JOIN status_tickets AS s ON t.id_status = s.id
        WHERE t.id_solicitante = ?
        ORDER BY t.data_criacao DESC";

if ($stmt = $conexao->prepare($sql)) {
    $stmt->bind_param("i", $id_usuario_logado);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        $chamados = $resultado->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}
?>

<div class="main-content">
    <div class="main-header">
        <h1><?php echo $titulo_pagina; ?></h1>
        <div class="user-menu">
            <div class="notificacao-sino">
                <i class="fa-solid fa-bell"></i>
                <span class="contador" id="contador-notificacoes" style="<?php echo (!isset($total_nao_lidas) || $total_nao_lidas == 0) ? 'display: none;' : ''; ?>"><?php echo $total_nao_lidas ?? 0; ?></span>
                <div class="notificacoes-dropdown">
                    <div class="notificacoes-header">Notificações</div>
                    <div class="notificacoes-body" id="notificacoes-body">
                        <?php if (empty($lista_notificacoes)): ?>
                            <div class="notificacao-item"><div class="mensagem">Nenhuma notificação nova.</div></div>
                        <?php else: ?>
                            <?php foreach ($lista_notificacoes as $notif): ?>
                                <a href="detalhes_chamado.php?id=<?php echo $notif['id_ticket']; ?>" class="notificacao-item">
                                    <div class="icon"><i class="fa-solid fa-ticket"></i></div>
                                    <div>
                                        <div class="mensagem"><?php echo htmlspecialchars($notif['mensagem']); ?></div>
                                        <div class="data"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($notif['data_criacao']))); ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="notificacoes-footer"><a href="notificacoes.php">Ver todas as notificações</a></div>
                </div>
            </div>
            <span>Olá, <?php echo htmlspecialchars($nome_usuario); ?>!</span>
            <a href="logout.php" class="logout-link">Sair</a>
        </div>
    </div>

    <div class="content-body">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nº Chamado</th>
                        <th>Assunto</th>
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Data de Abertura</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="tabela-meus-chamados-corpo">
                    <?php if (empty($chamados)): ?>
                        <tr>
                            <td colspan="6" class="nenhum-chamado">Você ainda não abriu nenhum chamado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($chamados as $chamado): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($chamado['id']); ?></td>
                                <td><?php echo htmlspecialchars($chamado['motivo_chamado']); ?></td>
                                <td><?php echo htmlspecialchars($chamado['nome_categoria']); ?></td>
                                <td>
                                    <span class="status status-<?php echo strtolower(str_replace(' ', '-', $chamado['nome_status'])); ?>">
                                        <?php echo htmlspecialchars($chamado['nome_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($chamado['data_criacao']))); ?></td>
                                <td>
                                    <a href="detalhes_chamado.php?id=<?php echo $chamado['id']; ?>" class="btn-acao">Ver Detalhes</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
if ($conexao) {
    $conexao->close();
}
?>

</body>
</html>
