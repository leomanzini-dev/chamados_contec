<?php
// admin_usuarios.php
$titulo_pagina = "Administração de Usuários";
$css_pagina = "admin.css"; // Usa o novo ficheiro CSS
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Lógica PHP para buscar os utilizadores reais do banco de dados
$sql = "SELECT id, nome_completo, email, departamento, tipo_usuario, ativo 
        FROM usuarios 
        ORDER BY nome_completo ASC";

$resultado = $conexao->query($sql);
$usuarios = $resultado->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-content">
    <div class="admin-header">
        <h1><?php echo $titulo_pagina; ?></h1>
        <a href="adicionar_usuario.php" class="btn-add-new">
            <i class="fa-solid fa-plus"></i>
            Adicionar Novo Usuário
        </a>
    </div>

    <div class="content-body">
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
                            <td colspan="5" class="nenhum-chamado">Nenhum usuário encontrado.</td>
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
                                    <?php if ($usuario['ativo']): ?>
                                        <span class="status-pill status-ativo">Ativo</span>
                                    <?php else: ?>
                                        <span class="status-pill status-inativo">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn-action edit" title="Editar">
                                            <i class="fa-solid fa-pencil"></i>
                                        </a>
                                        <a href="excluir_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn-action delete" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
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
