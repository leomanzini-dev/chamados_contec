<?php
// admin_kb.php
$titulo_pagina = "Base de Conhecimento";
$css_pagina = "admin.css"; // Usa o novo ficheiro CSS
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Lógica PHP para buscar os artigos, já corrigida
$sql = "SELECT 
            a.id, 
            a.titulo, 
            a.data_ultima_atualizacao, 
            u.nome_completo AS autor
        FROM kb_artigos AS a
        JOIN usuarios AS u ON a.id_autor = u.id
        ORDER BY a.data_ultima_atualizacao DESC";
$resultado = $conexao->query($sql);
$artigos = $resultado->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-content">
    <div class="admin-header">
        <h1><?php echo $titulo_pagina; ?></h1>
        <a href="adicionar_artigo.php" class="btn-add-new">
            <i class="fa-solid fa-plus"></i>
            Adicionar Novo Artigo
        </a>
    </div>

    <div class="content-body">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Última Atualização</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($artigos)): ?>
                        <tr>
                            <td colspan="4" class="nenhum-resultado">Nenhum artigo encontrado na base de conhecimento.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($artigos as $artigo): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($artigo['titulo']); ?></td>
                                <td><?php echo htmlspecialchars($artigo['autor']); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($artigo['data_ultima_atualizacao']))); ?></td>
                                <td>
                                    <div class="actions-cell">
                                        <a href="editar_artigo.php?id=<?php echo $artigo['id']; ?>" class="btn-action edit" title="Editar">
                                            <i class="fa-solid fa-pencil"></i>
                                        </a>
                                         <a href="excluir_artigo.php?id=<?php echo $artigo['id']; ?>" class="btn-action delete" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este artigo?');">
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
