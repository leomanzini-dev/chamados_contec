<?php
// abrir_chamado.php
$titulo_pagina = "Abrir Novo Chamado";
$css_pagina = "formularios.css"; // Continuamos usando o mesmo CSS
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Busca dados para os menus suspensos
$categorias = $conexao->query("SELECT id, nome FROM categorias ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);
$prioridades = $conexao->query("SELECT id, nome FROM prioridades ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$agentes_ti = $conexao->query("SELECT id, nome_completo FROM usuarios WHERE tipo_usuario = 'ti' AND ativo = 1 ORDER BY nome_completo ASC")->fetch_all(MYSQLI_ASSOC);

// --- NOVO: Busca os 3 artigos mais populares ---
$artigos_populares = $conexao->query("SELECT id, titulo FROM kb_artigos WHERE visivel_para = 'todos' ORDER BY visualizacoes DESC, votos_uteis DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-content">
    <div class="main-header">
        <h1><?php echo $titulo_pagina; ?></h1>
    </div>

    <!-- O content-body agora engloba tudo -->
    <div class="content-body">
        
        <!-- Card Principal do Formulário -->
        <div class="form-card">
            <form id="form-abrir-chamado" action="processa_abertura_chamado.php" method="POST" enctype="multipart/form-data">
                <div class="sugestoes-kb-wrapper">
                    <div class="form-group">
                        <label for="motivo_chamado">Assunto do Chamado</label>
                        <input type="text" id="motivo_chamado" name="motivo_chamado" required autocomplete="off" placeholder="Ex: Impressora não funciona na contabilidade">
                    </div>
                    <div id="sugestoes-kb"></div>
                </div>

                <div class="form-grid-3-col">
                    <div class="form-group">
                        <label for="id_categoria">Categoria</label>
                        <select id="id_categoria" name="id_categoria" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo htmlspecialchars($categoria['id']); ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_prioridade">Prioridade</label>
                        <select id="id_prioridade" name="id_prioridade" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($prioridades as $prioridade): ?>
                                <option value="<?php echo htmlspecialchars($prioridade['id']); ?>"><?php echo htmlspecialchars($prioridade['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_agente_designado">Direcionar para (Opcional)</label>
                        <select id="id_agente_designado" name="id_agente_designado">
                            <option value="0">-- Toda a equipe de TI --</option>
                            <?php foreach ($agentes_ti as $agente): ?>
                                <option value="<?php echo htmlspecialchars($agente['id']); ?>"><?php echo htmlspecialchars($agente['nome_completo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="descricao_detalhada">Descrição Detalhada do Problema</label>
                    <textarea id="descricao_detalhada" name="descricao_detalhada" rows="8" required></textarea>
                </div>

                <div class="form-group">
                    <label for="anexos">Anexar Arquivos</label>
                    <input type="file" id="anexos" name="anexos[]" multiple>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Abrir Chamado</button>
                    <a href="painel.php" class="btn-cancelar">Cancelar</a>
                </div>
            </form>
        </div>

        <!-- Nova Seção de Ajuda -->
        <div class="ajuda-container">
            <div class="ajuda-card">
                <h4><i class="fa-solid fa-lightbulb"></i> Dicas para um bom chamado</h4>
                <ul>
                    <li>Use um assunto claro e objetivo.</li>
                    <li>Descreva o problema com o máximo de detalhes possível.</li>
                    <li>Se houver uma mensagem de erro, anexe um print da tela.</li>
                    <li>Informe os passos que você já tentou para resolver.</li>
                </ul>
            </div>
            <div class="ajuda-card">
                <h4><i class="fa-solid fa-star"></i> Artigos Populares</h4>
                <?php if (empty($artigos_populares)): ?>
                    <p class="sem-artigos">Nenhum artigo na Base de Conhecimento ainda.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($artigos_populares as $artigo): ?>
                            <li>
                                <a href="ver_artigo.php?id=<?php echo $artigo['id']; ?>" target="_blank">
                                    <i class="fa-solid fa-book-open"></i> <?php echo htmlspecialchars($artigo['titulo']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- O HTML da sua animação continua aqui -->
<div class="loading-overlay" id="loading-overlay">
    <div class="spinner"></div>
</div>
<div class="success-modal" id="success-modal">
    <svg class="success-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
        <circle class="success-checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
        <path class="success-checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
    </svg>
    <h2>Chamado Aberto!</h2>
    <p id="success-message"></p>
</div>

<?php $conexao->close(); ?>
    </div> <!-- Fechamento da .dashboard-container -->
</body>

<!-- O JavaScript da sua animação e busca inteligente continua aqui -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const campoAssunto = document.getElementById('motivo_chamado');
    const containerSugestoes = document.getElementById('sugestoes-kb');
    let timeoutBusca = null;

    if (campoAssunto) {
        campoAssunto.addEventListener('keyup', function() {
            clearTimeout(timeoutBusca);
            const termo = this.value;

            if (termo.length < 3) {
                containerSugestoes.style.display = 'none';
                containerSugestoes.innerHTML = '';
                return;
            }

            timeoutBusca = setTimeout(() => {
                fetch(`buscar_artigos.php?termo=${encodeURIComponent(termo)}`)
                    .then(response => response.json())
                    .then(artigos => {
                        containerSugestoes.innerHTML = '';
                        if (artigos.length > 0) {
                            const header = document.createElement('div');
                            header.className = 'sugestoes-header';
                            header.innerHTML = '<span><i class="fa-solid fa-lightbulb"></i> Artigos Sugeridos</span>';
                            containerSugestoes.appendChild(header);

                            artigos.forEach(artigo => {
                                const linkArtigo = document.createElement('a');
                                linkArtigo.href = `ver_artigo.php?id=${artigo.id}`;
                                linkArtigo.target = '_blank';
                                linkArtigo.className = 'sugestao-item';
                                linkArtigo.innerHTML = `<i class="fa-solid fa-book-open"></i> ${artigo.titulo}`;
                                containerSugestoes.appendChild(linkArtigo);
                            });
                            containerSugestoes.style.display = 'block';
                        } else {
                            containerSugestoes.style.display = 'none';
                        }
                    })
                    .catch(error => console.error('Erro na busca:', error));
            }, 500);
        });

        document.addEventListener('click', function(e) {
            if (!containerSugestoes.contains(e.target)) {
                containerSugestoes.style.display = 'none';
            }
        });
    }

    const form = document.getElementById('form-abrir-chamado');
    const loadingOverlay = document.getElementById('loading-overlay');
    const successModal = document.getElementById('success-modal');
    const successMessage = document.getElementById('success-message');

    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            loadingOverlay.style.display = 'flex';
            const formData = new FormData(form);

            fetch('processa_abertura_chamado.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loadingOverlay.style.display = 'none';
                if (data.success) {
                    successMessage.textContent = 'Seu chamado nº ' + data.ticket_id + ' foi registrado.';
                    successModal.style.display = 'flex';
                    setTimeout(() => {
                        window.location.href = 'painel.php';
                    }, 3000);
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                loadingOverlay.style.display = 'none';
                alert('Ocorreu um erro de comunicação. Tente novamente.');
                console.error('Error:', error);
            });
        });
    }
});
</script>
</html>
