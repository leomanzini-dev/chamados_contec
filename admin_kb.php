<?php
// admin_kb.php (VERSÃO CORRIGIDA COM SCRIPT)
$titulo_pagina = "Base de Conhecimento";
$css_pagina = "admin.css";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

if ($tipo_usuario != 'ti') {
    header("Location: painel.php");
    exit();
}

$sql = "SELECT a.id, a.titulo, a.data_ultima_atualizacao, u.nome_completo AS autor
        FROM kb_artigos AS a
        JOIN usuarios AS u ON a.id_autor = u.id
        ORDER BY a.data_ultima_atualizacao DESC";
$resultado = $conexao->query($sql);
$artigos = $resultado->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-content">
    <div class="admin-header">
        <h1><?php echo $titulo_pagina; ?></h1>
        <a href="adicionar_artigo.php" class="btn btn-primary">
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
                                        <a href="editar_artigo.php?id=<?php echo $artigo['id']; ?>" class="btn btn-action edit" title="Editar">
                                            <i class="fa-solid fa-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-action delete btn-excluir" title="Excluir"
                                                data-id="<?php echo $artigo['id']; ?>" 
                                                data-nome="<?php echo htmlspecialchars($artigo['titulo']); ?>"
                                                data-tipo="artigo">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
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

<?php
if($conexao) { $conexao->close(); }
?>

<script src="js/modal_exclusao.js"></script>

</div> </body>
</html>