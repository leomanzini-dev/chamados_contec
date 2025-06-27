<?php
// meus_chamados.php

$titulo_pagina = "Meus Chamados";
$css_pagina = "tabelas.css"; // Continua carregando o seu CSS
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Busca os chamados do usuário logado no banco de dados
$chamados = [];
$sql = "SELECT t.id, t.id_chamado_usuario, t.motivo_chamado, t.data_criacao, c.nome AS nome_categoria, s.nome AS nome_status
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
        <h1><?php echo htmlspecialchars($titulo_pagina); ?></h1>

        <a href="abrir_chamado.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> Abrir Novo Chamado
        </a>
    </div>

    <div class="content-body">
        <div class="table-container">
            <h2 style="margin: 0 0 20px 10px;">Seu Histórico de Chamados</h2>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nº do Seu Chamado</th>
                        <th>Assunto</th>
                        <th>Categoria</th>
                        <th>Data de Abertura</th>
                        <th>Status</th>
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
                                <td>#<?php echo htmlspecialchars($chamado['id_chamado_usuario'] ?? $chamado['id']); ?></td>
                                <td><?php echo htmlspecialchars($chamado['motivo_chamado']); ?></td>
                                <td><?php echo htmlspecialchars($chamado['nome_categoria']); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($chamado['data_criacao']))); ?></td>
                                <td>
                                    <span class="status status-<?php echo strtolower(str_replace(' ', '-', $chamado['nome_status'])); ?>">
                                        <?php echo htmlspecialchars($chamado['nome_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="detalhes_chamado.php?id=<?php echo $chamado['id']; ?>" class="btn btn-acao">Ver Detalhes</a>
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

</div>
</body>
</html>