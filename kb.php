<?php
// kb.php
$titulo_pagina = "Base de Conhecimento";
$css_pagina = "kb.css";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Verifica os parâmetros da URL
$termo_busca = trim(filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING));
$id_categoria_selecionada = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);

// Inicializa as variáveis
$artigos_encontrados = [];
$categorias = [];
$nome_categoria_selecionada = '';

if (!empty($termo_busca)) {
    // MODO DE BUSCA: Procura por artigos com base no termo de busca
    $sql = "SELECT a.id, a.titulo, c.nome AS nome_categoria
            FROM kb_artigos a
            JOIN categorias c ON a.id_categoria = c.id
            WHERE (a.titulo LIKE ? OR a.conteudo LIKE ?) AND a.visivel_para = 'todos'
            ORDER BY a.titulo";
    $termo_busca_like = "%" . $termo_busca . "%";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("ss", $termo_busca_like, $termo_busca_like);
    $stmt->execute();
    $artigos_encontrados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} elseif ($id_categoria_selecionada) {
    // MODO DE CATEGORIA: Mostra os artigos de uma categoria específica
    $sql_artigos = "SELECT id, titulo FROM kb_artigos WHERE id_categoria = ? AND visivel_para = 'todos' ORDER BY titulo";
    $stmt_artigos = $conexao->prepare($sql_artigos);
    $stmt_artigos->bind_param("i", $id_categoria_selecionada);
    $stmt_artigos->execute();
    $artigos_encontrados = $stmt_artigos->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_artigos->close();

    // Busca o nome da categoria para exibir no título
    $sql_nome_cat = "SELECT nome FROM categorias WHERE id = ?";
    $stmt_nome = $conexao->prepare($sql_nome_cat);
    $stmt_nome->bind_param("i", $id_categoria_selecionada);
    $stmt_nome->execute();
    $result_nome = $stmt_nome->get_result();
    if($result_nome->num_rows > 0){
        $nome_categoria_selecionada = $result_nome->fetch_assoc()['nome'];
    }
    $stmt_nome->close();

} else {
    // MODO PADRÃO: Mostra a lista de todas as categorias
    $sql_categorias = "SELECT id, nome FROM categorias ORDER BY nome ASC";
    $categorias = $conexao->query($sql_categorias)->fetch_all(MYSQLI_ASSOC);
}

?>

<div class="main-content">
    <div class="main-header">
        <h1><?php echo $titulo_pagina; ?></h1>
    </div>

    <div class="content-body">
        
        <div class="kb-header">
            <h2>Como podemos ajudar?</h2>
            <p>Encontre respostas rápidas para os problemas mais comuns.</p>
            <form action="kb.php" method="GET" class="search-form">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="search" name="q" placeholder="Digite sua dúvida ou palavra-chave..." value="<?php echo htmlspecialchars($termo_busca); ?>">
            </form>
        </div>

        <?php if (!empty($termo_busca)): ?>
            <!-- SEÇÃO DE RESULTADOS DA BUSCA -->
            <h3 class="section-title">Resultados para "<?php echo htmlspecialchars($termo_busca); ?>"</h3>
            <div class="search-results">
                <?php if (empty($artigos_encontrados)): ?>
                    <p>Nenhum artigo encontrado. Tente outros termos ou <a href="abrir_chamado.php">abra um novo chamado</a>.</p>
                <?php else: ?>
                    <?php foreach ($artigos_encontrados as $artigo): ?>
                        <a href="ver_artigo.php?id=<?php echo $artigo['id']; ?>" class="article-list-item">
                            <div class="titulo"><?php echo htmlspecialchars($artigo['titulo']); ?></div>
                            <div class="categoria">Em: <?php echo htmlspecialchars($artigo['nome_categoria']); ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php elseif ($id_categoria_selecionada): ?>
            <!-- SEÇÃO DE ARTIGOS POR CATEGORIA -->
            <h3 class="section-title">Artigos em "<?php echo htmlspecialchars($nome_categoria_selecionada); ?>"</h3>
            <a href="kb.php" class="back-link">&larr; Voltar para todas as categorias</a>
            <div class="search-results">
                <?php if (empty($artigos_encontrados)): ?>
                    <p>Nenhum artigo encontrado nesta categoria.</p>
                <?php else: ?>
                    <?php foreach ($artigos_encontrados as $artigo): ?>
                        <a href="ver_artigo.php?id=<?php echo $artigo['id']; ?>" class="article-list-item">
                            <div class="titulo"><?php echo htmlspecialchars($artigo['titulo']); ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- SEÇÃO PADRÃO COM A LISTA DE CATEGORIAS -->
            <h3 class="section-title">Navegar por Categoria</h3>
            <div class="category-grid">
                <?php foreach ($categorias as $categoria): ?>
                    <a href="kb.php?categoria=<?php echo $categoria['id']; ?>" class="category-card">
                        <span class="icon"><i class="fa-solid fa-folder"></i></span>
                        <h4><?php echo htmlspecialchars($categoria['nome']); ?></h4>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php
$conexao->close();
?>
    </div>
</body>
</html>
