<?php
// editar_artigo.php
$titulo_pagina = "Editar Artigo";
$css_pagina = "formularios.css"; // Reutilizamos o nosso CSS de formulários
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Apenas utilizadores 'ti' podem aceder a esta página
if ($tipo_usuario != 'ti') {
    header("Location: painel.php");
    exit();
}

// 1. Validar e obter o ID do artigo da URL
$id_artigo_editar = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_artigo_editar) {
    $_SESSION['form_message_type'] = 'error';
    $_SESSION['form_message'] = "ID de artigo inválido.";
    header("Location: admin_kb.php");
    exit();
}

// 2. Buscar os dados do artigo no banco de dados
$sql_artigo = "SELECT titulo, conteudo, id_categoria FROM kb_artigos WHERE id = ?";
$stmt = $conexao->prepare($sql_artigo);
$stmt->bind_param("i", $id_artigo_editar);
$stmt->execute();
$resultado = $stmt->get_result();
if ($resultado->num_rows === 0) {
    $_SESSION['form_message_type'] = 'error';
    $_SESSION['form_message'] = "Artigo não encontrado.";
    header("Location: admin_kb.php");
    exit();
}
$artigo_editar = $resultado->fetch_assoc();
$stmt->close();

// Buscar as categorias para preencher o dropdown
$categorias = $conexao->query("SELECT id, nome FROM categorias ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-content">
    <div class="main-header">
        <h1><?php echo $titulo_pagina; ?></h1>
    </div>

    <div class="content-body">
        
        <form action="processa_editar_artigo.php" method="POST">
            <!-- Campo oculto para enviar o ID do artigo que está sendo editado -->
            <input type="hidden" name="id_artigo" value="<?php echo htmlspecialchars($id_artigo_editar); ?>">

            <div class="form-group">
                <label for="titulo">Título do Artigo</label>
                <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($artigo_editar['titulo']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="id_categoria">Categoria</label>
                <select id="id_categoria" name="id_categoria" required>
                    <option value="">-- Selecione uma categoria --</option>
                    <?php foreach($categorias as $categoria): ?>
                        <option value="<?php echo htmlspecialchars($categoria['id']); ?>" <?php echo ($artigo_editar['id_categoria'] == $categoria['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categoria['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="conteudo">Conteúdo do Artigo</label>
                <textarea id="conteudo" name="conteudo" rows="15" required><?php echo htmlspecialchars($artigo_editar['conteudo']); ?></textarea>
                <small>Pode usar HTML básico para formatação (ex: &lt;b&gt;negrito&lt;/b&gt;, &lt;ul&gt;&lt;li&gt;item&lt;/li&gt;&lt;/ul&gt;).</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Salvar Alterações</button>
                <a href="admin_kb.php" class="btn-cancelar">Cancelar</a>
            </div>
        </form>

    </div>
</div>

<?php
$conexao->close();
?>
    </div> <!-- Fecho da .dashboard-container -->
</body>
</html>
