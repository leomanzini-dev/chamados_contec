<?php
// admin_usuarios.php (VERSÃO FINAL COMPLETA COM MODAL SEGURO)
$titulo_pagina = "Administração de Usuários";
$css_pagina = "admin.css";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Apenas usuários 'ti' podem acessar
if ($tipo_usuario != 'ti') {
    header("Location: painel.php");
    exit();
}

// --- LÓGICA DA BUSCA ---
$filtro_busca = trim(filter_input(INPUT_GET, 'busca', FILTER_SANITIZE_STRING));
$sql = "SELECT id, nome_completo, email, departamento, tipo_usuario, ativo FROM usuarios";
$params = [];
$types = '';
if (!empty($filtro_busca)) {
    $sql .= " WHERE (nome_completo LIKE ? OR email LIKE ? OR departamento LIKE ?)";
    $like_param = "%" . $filtro_busca . "%";
    $params = [$like_param, $like_param, $like_param];
    $types = 'sss';
}
$sql .= " ORDER BY nome_completo ASC";
$stmt = $conexao->prepare($sql);
if ($stmt && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}
if ($stmt) {
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuarios = $resultado->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $usuarios = [];
}
?>

<div class="main-content">
    <div class="admin-header">
        <h1><?php echo $titulo_pagina; ?></h1>
        <a href="adicionar_usuario.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i>
            Adicionar Novo Usuário
        </a>
    </div>

    <div class="content-body">
        <div class="filtro-container-admin">
            <form action="admin_usuarios.php" method="GET" class="filtro-form-admin">
                <input type="text" name="busca" id="busca" value="<?php echo htmlspecialchars($filtro_busca); ?>" placeholder="Buscar por nome, e-mail ou departamento...">
                <div class="filtro-botoes-admin">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <a href="admin_usuarios.php" class="btn btn-secondary">Limpar</a>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nome Completo</th>
                        <th>Departamento</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="5" class="nenhum-resultado">Nenhum usuário encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <span class="user-name"><?php echo htmlspecialchars($usuario['nome_completo']); ?></span>
                                        <span class="user-email"><?php echo htmlspecialchars($usuario['email']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($usuario['departamento']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($usuario['tipo_usuario'])); ?></td>
                                <td>
                                    <span class="status-pill <?php echo $usuario['ativo'] ? 'status-ativo' : 'status-inativo'; ?>">
                                        <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-action edit" title="Editar"><i class="fa-solid fa-pencil"></i></a>
                                        <button type="button" class="btn btn-action delete btn-excluir" title="Excluir" data-id="<?php echo $usuario['id']; ?>" data-nome="<?php echo htmlspecialchars($usuario['nome_completo']); ?>" data-tipo="usuario"><i class="fa-solid fa-trash-can"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modal-confirmacao" class="modal-overlay" style="display: none;">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Confirmar Exclusão</h3>
            <button id="modal-fechar" class="modal-close-btn">&times;</button>
        </div>
        <form id="form-excluir-generico" method="POST">
            <div class="modal-body">
                <p id="modal-mensagem"></p>
                <p style="margin-top: 15px;">Para confirmar esta ação, digite <strong>EXCLUIR</strong> no campo abaixo:</p>
                <input type="hidden" name="id" id="id-excluir-hidden">
                <input type="text" id="input-confirmacao-generico" autocomplete="off" style="margin-top: 5px;">
            </div>
            <div class="modal-footer">
                <button id="modal-cancelar" type="button" class="btn btn-secondary">Cancelar</button>
                <button id="modal-confirmar-submit" type="submit" class="btn btn-danger" disabled>Confirmar Exclusão</button>
            </div>
        </form>
    </div>
</div>

<?php if($conexao) { $conexao->close(); } ?>
<script src="js/modal_exclusao.js"></script>
</div> </body>
</html>  