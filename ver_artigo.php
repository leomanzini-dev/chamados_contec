<?php
// ver_artigo.php
$titulo_pagina = "Artigo"; // O título será atualizado dinamicamente
$css_pagina = "ver_artigo.css";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// 1. Validar e obter o ID do artigo da URL
$id_artigo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_artigo) {
    echo "<div class='main-content'><div class='content-body'><p class='error-message'>Artigo não encontrado.</p></div></div>";
    exit();
}

// 2. Buscar os dados do artigo no banco de dados
$sql = "SELECT 
            kb.titulo, kb.conteudo, kb.data_ultima_atualizacao,
            c.nome AS nome_categoria,
            u.nome_completo AS nome_autor
        FROM kb_artigos AS kb
        JOIN categorias AS c ON kb.id_categoria = c.id
        JOIN usuarios AS u ON kb.id_autor = u.id
        WHERE kb.id = ? AND kb.visivel_para = 'todos'";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id_artigo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    echo "<div class='main-content'><div class='content-body'><p class='error-message'>Artigo não encontrado ou não está disponível.</p></div></div>";
    exit();
}
$artigo = $resultado->fetch_assoc();
$stmt->close();

// Atualiza o título da página com o título do artigo
$titulo_pagina = $artigo['titulo'];
?>

<div class="main-content">
    <div class="main-header">
        <h1>Base de Conhecimento</h1>
        <div class="user-menu">
            <!-- Seu menu do usuário aqui, com o sino de notificação -->
        </div>
    </div>

    <div class="content-body">
        <div class="artigo-container">
            <div class="artigo-header">
                <h2><?php echo htmlspecialchars($artigo['titulo']); ?></h2>
                <div class="artigo-meta">
                    <span><strong>Autor:</strong> <?php echo htmlspecialchars($artigo['nome_autor']); ?></span>
                    <span><strong>Categoria:</strong> <?php echo htmlspecialchars($artigo['nome_categoria']); ?></span>
                    <span><strong>Última atualização:</strong> <?php echo date('d/m/Y', strtotime($artigo['data_ultima_atualizacao'])); ?></span>
                </div>
            </div>

            <div class="artigo-conteudo">
                <?php
                // Permite tags HTML básicas para formatação do conteúdo
                echo strip_tags($artigo['conteudo'], '<b><strong><i><em><u><ul><ol><li><br><p><a><h3><h4><h5><h6><blockquote><code><img>');
                ?>
            </div>

            <div class="artigo-feedback" id="feedback-container">
                <h4>Este artigo foi útil?</h4>
                <div class="botoes-feedback">
                    <button class="btn-feedback" id="btn-voto-sim" data-id-artigo="<?php echo $id_artigo; ?>">
                        <i class="fa-solid fa-thumbs-up"></i> Sim
                    </button>
                    <a href="abrir_chamado.php?assunto=<?php echo urlencode($artigo['titulo']); ?>" class="btn-feedback" id="btn-voto-nao">
                        <i class="fa-solid fa-headset"></i> Não, preciso de ajuda
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php $conexao->close(); ?>
    </div> <!-- Fechamento da .dashboard-container -->
</body>

<!-- ===== SCRIPT ADICIONADO PARA O FEEDBACK ===== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnSim = document.getElementById('btn-voto-sim');
    const feedbackContainer = document.getElementById('feedback-container');

    if (btnSim) {
        btnSim.addEventListener('click', function() {
            const artigoId = this.dataset.idArtigo;

            // Envia o voto para o servidor em segundo plano
            fetch('registrar_voto_kb.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_artigo: artigoId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Feedback visual de sucesso: substitui os botões pela mensagem
                    feedbackContainer.innerHTML = '<p class="feedback-agradecimento">Obrigado pelo seu feedback!</p>';
                } else {
                    // Em caso de erro, informa o usuário (opcional)
                    alert('Não foi possível registrar seu voto. Tente novamente.');
                }
            })
            .catch(error => console.error('Erro:', error));
        });
    }
});
</script>
</html>
