<?php
// adicionar_artigo.php
$titulo_pagina = "Adicionar Novo Artigo";
$css_pagina = "formularios.css"; // Reutilizamos o nosso CSS de formulários
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Apenas utilizadores 'ti' podem aceder a esta página
if ($tipo_usuario != 'ti') {
    $_SESSION['form_message_type'] = 'error';
    $_SESSION['form_message'] = "Acesso negado.";
    header("Location: painel.php");
    exit();
}

// Buscar as categorias para preencher o dropdown
$categorias = $conexao->query("SELECT id, nome FROM categorias ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);

?>

<div class="main-content">
    <div class="main-header">
        <h1><?php echo $titulo_pagina; ?></h1>
    </div>

    <div class="content-body">
        
        <form action="processa_adicionar_artigo.php" method="POST">
            <div class="form-group">
                <label for="titulo">Título do Artigo</label>
                <input type="text" id="titulo" name="titulo" required>
            </div>
            
            <div class="form-group">
                <label for="id_categoria">Categoria</label>
                <select id="id_categoria" name="id_categoria" required>
                    <option value="">-- Selecione uma categoria --</option>
                    <?php foreach($categorias as $categoria): ?>
                        <option value="<?php echo htmlspecialchars($categoria['id']); ?>">
                            <?php echo htmlspecialchars($categoria['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="conteudo">Conteúdo do Artigo</label>
                <textarea id="conteudo" name="conteudo" rows="15" required></textarea>
                <small>Pode usar HTML básico para formatação (ex: &lt;b&gt;negrito&lt;/b&gt;, &lt;ul&gt;&lt;li&gt;item&lt;/li&gt;&lt;/ul&gt;).</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Salvar Artigo</button>
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
