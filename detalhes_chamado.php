<?php
// detalhes_chamado.php

$id_chamado_titulo = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$titulo_pagina = "Detalhes do Chamado #" . ($id_chamado_titulo ?: '');
$css_pagina = "detalhes_chamado.css"; 
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$id_chamado = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_chamado) {
    echo "<div class='main-content'><div class='content-body'><p class='error-message'>ID do chamado inválido.</p></div></div>";
    exit();
}

$sql_chamado = "SELECT t.*, solicitante.nome_completo AS nome_solicitante, agente.nome_completo AS nome_agente, c.nome AS nome_categoria, p.nome AS nome_prioridade, s.nome AS nome_status FROM tickets AS t JOIN usuarios AS solicitante ON t.id_solicitante = solicitante.id LEFT JOIN usuarios AS agente ON t.id_agente_atribuido = agente.id JOIN categorias AS c ON t.id_categoria = c.id JOIN prioridades AS p ON t.id_prioridade = p.id JOIN status_tickets AS s ON t.id_status = s.id WHERE t.id = ?";
$stmt = $conexao->prepare($sql_chamado);
$stmt->bind_param("i", $id_chamado);
$stmt->execute();
$resultado_chamado = $stmt->get_result();
if ($resultado_chamado->num_rows === 0) {
    echo "<div class='main-content'><div class='content-body'><p class='error-message'>Chamado não encontrado.</p></div></div>";
    exit();
}
$chamado = $resultado_chamado->fetch_assoc();
$stmt->close();

if ($tipo_usuario != 'ti' && $chamado['id_solicitante'] != $id_usuario_logado) {
    echo "<div class='main-content'><div class='content-body'><p class='error-message'>Você não tem permissão para visualizar este chamado.</p></div></div>";
    exit();
}

$sql_comentarios = "SELECT c.*, u.nome_completo AS nome_usuario FROM comentarios_tickets AS c JOIN usuarios AS u ON c.id_usuario = u.id WHERE c.id_ticket = ? ORDER BY c.data_comentario ASC";
$stmt_comentarios = $conexao->prepare($sql_comentarios);
$stmt_comentarios->bind_param("i", $id_chamado);
$stmt_comentarios->execute();
$comentarios = $stmt_comentarios->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_comentarios->close();

$sql_anexos = "SELECT * FROM anexos_tickets WHERE id_ticket = ?";
$stmt_anexos = $conexao->prepare($sql_anexos);
$stmt_anexos->bind_param("i", $id_chamado);
$stmt_anexos->execute();
$anexos = $stmt_anexos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_anexos->close();

$todos_status = [];
$todos_agentes = [];
if ($tipo_usuario == 'ti') {
    $resultado_todos_status = $conexao->query("SELECT id, nome FROM status_tickets ORDER BY nome ASC");
    if ($resultado_todos_status) $todos_status = $resultado_todos_status->fetch_all(MYSQLI_ASSOC);
    $resultado_todos_agentes = $conexao->query("SELECT id, nome_completo FROM usuarios WHERE tipo_usuario = 'ti' AND ativo = 1 ORDER BY nome_completo ASC");
    if ($resultado_todos_agentes) $todos_agentes = $resultado_todos_agentes->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="main-content">
    <div class="main-header">
        <h1>Detalhes do Chamado #<?php echo htmlspecialchars($chamado['id']); ?></h1>
        <div class="user-menu">
            <span>Olá, <?php echo htmlspecialchars($nome_usuario); ?>!</span>
            <a href="logout.php" class="logout-link">Sair</a>
        </div>
    </div>

    <div class="content-body">
        
        <div class="detalhes-grid">
            <div class="info-chamado">
                <h2><?php echo htmlspecialchars($chamado['motivo_chamado']); ?></h2>
                <p><strong>Solicitante:</strong> <?php echo htmlspecialchars($chamado['nome_solicitante']); ?></p>
                <p id="detalhes-agente"><strong>Agente Atribuído:</strong> <?php echo $chamado['nome_agente'] ? htmlspecialchars($chamado['nome_agente']) : 'Não atribuído'; ?></p>
                <p><strong>Data de Abertura:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($chamado['data_criacao']))); ?></p>
                <p id="detalhes-ultima-atualizacao"><strong>Última Atualização:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($chamado['data_ultima_atualizacao']))); ?></p>
            </div>
            
            <div class="status-chamado">
                <div class="info-item">
                    <span>Status</span>
                    <p id="detalhes-status" class="status status-<?php echo strtolower(str_replace(' ', '-', $chamado['nome_status'])); ?>"><?php echo htmlspecialchars($chamado['nome_status']); ?></p>
                </div>
                <div class="info-item">
                    <span>Prioridade</span>
                    <p><?php echo htmlspecialchars($chamado['nome_prioridade']); ?></p>
                </div>
                <div class="info-item">
                    <span>Categoria</span>
                    <p><?php echo htmlspecialchars($chamado['nome_categoria']); ?></p>
                </div>
            </div>
        </div>

        <?php if ($tipo_usuario == 'ti'): ?>
        <div class="gerenciamento-ti">
            <h3>Ações de Gerenciamento</h3>
            <form action="processa_acao_chamado.php" method="POST">
                <input type="hidden" name="id_chamado" value="<?php echo htmlspecialchars($chamado['id']); ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="id_status">Alterar Status:</label>
                        <select name="id_status" id="id_status">
                            <?php foreach ($todos_status as $status): ?>
                                <option value="<?php echo $status['id']; ?>" <?php echo ($status['id'] == $chamado['id_status']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($status['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_agente">Atribuir Agente:</label>
                        <select name="id_agente" id="id_agente">
                            <option value="0">-- Não atribuído --</option>
                            <?php foreach ($todos_agentes as $agente): ?>
                                <option value="<?php echo $agente['id']; ?>" <?php echo ($agente['id'] == $chamado['id_agente_atribuido']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($agente['nome_completo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-atualizar">Atualizar Chamado</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="descricao-completa">
            <h3>Descrição Inicial</h3>
            <p><?php echo nl2br(htmlspecialchars($chamado['descricao_detalhada'])); ?></p>
        </div>
        
        <?php if (!empty($anexos)): ?>
            <div class="anexos-chamado">
                <h3>Anexos</h3>
                <ul>
                    <?php foreach($anexos as $anexo): ?>
                        <li><a href="<?php echo htmlspecialchars($anexo['caminho_arquivo']); ?>" target="_blank"><?php echo htmlspecialchars($anexo['nome_arquivo_original']); ?></a> (<?php echo round($anexo['tamanho_bytes'] / 1024, 2); ?> KB)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="historico-chamado">
            <h3>Histórico e Comentários</h3>
            <div id="lista-comentarios">
                <?php if (empty($comentarios)): ?>
                    <p class="nenhum-comentario">Nenhum comentário ainda.</p>
                <?php else: ?>
                    <?php foreach($comentarios as $comentario): ?>
                        <div class="comentario <?php echo $comentario['interno'] ? 'interno' : ''; ?>">
                            <div class="comentario-header">
                                <strong><?php echo htmlspecialchars($comentario['nome_usuario']); ?></strong> comentou em <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($comentario['data_comentario']))); ?>
                                <?php if ($comentario['interno']): ?>
                                    <span class="tag-interno">INTERNO</span>
                                <?php endif; ?>
                            </div>
                            <div class="comentario-corpo">
                                <?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- << CORRIGIDO >> Formulário de novo comentário restaurado -->
        <div class="novo-comentario">
            <h3>Adicionar Novo Comentário</h3>
            <form action="processa_comentario.php" method="POST">
                <input type="hidden" name="id_chamado" value="<?php echo htmlspecialchars($chamado['id']); ?>">
                <textarea name="comentario" rows="5" placeholder="Digite seu comentário aqui..." required></textarea>
                <?php if ($tipo_usuario == 'ti'): ?>
                    <div class="checkbox-interno">
                        <input type="checkbox" id="comentario_interno" name="comentario_interno" value="1">
                        <label for="comentario_interno">Marcar como comentário interno (visível apenas para a equipe de TI)</label>
                    </div>
                <?php endif; ?>
                <button type="submit">Enviar Comentário</button>
            </form>
        </div>

    </div> <!-- fecha content-body -->
</div> <!-- fecha main-content -->

<?php
if($conexao) {
    $conexao->close();
}
?>
    </div> <!-- fecha dashboard-container (do header.php) -->
</body>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const idChamado = <?php echo $id_chamado; ?>;
    const tipoUsuario = "<?php echo $tipo_usuario; ?>";

    function escapeHTML(str) {
        if (typeof str !== 'string') return '';
        const p = document.createElement('p');
        p.textContent = str;
        return p.innerHTML;
    }
    
    function flashElement(element) {
        if (!element) return;
        element.style.transition = 'background-color 0.2s';
        element.style.backgroundColor = '#fffacd';
        setTimeout(() => {
            element.style.backgroundColor = '';
        }, 1500);
    }

    function atualizarDetalhes(detalhes) {
        if (!detalhes) return;
        
        const elStatus = document.getElementById('detalhes-status');
        const elAgente = document.getElementById('detalhes-agente');
        const elAtualizacao = document.getElementById('detalhes-ultima-atualizacao');
        
        const novoStatusClass = 'status status-' + detalhes.nome_status.toLowerCase().replace(/ /g, '-');
        if (elStatus.innerText !== detalhes.nome_status) {
            elStatus.innerText = detalhes.nome_status;
            elStatus.className = novoStatusClass;
            flashElement(elStatus);
        }

        const novoAgenteTexto = '<strong>Agente Atribuído:</strong> ' + (detalhes.nome_agente ? escapeHTML(detalhes.nome_agente) : 'Não atribuído');
        if(elAgente.innerHTML !== novoAgenteTexto) {
            elAgente.innerHTML = novoAgenteTexto;
            flashElement(elAgente);
        }
        
        const novaData = new Date(detalhes.data_ultima_atualizacao).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        const novaAtualizacaoTexto = '<strong>Última Atualização:</strong> ' + novaData;
        if(elAtualizacao.innerHTML.trim() !== novaAtualizacaoTexto.trim()) {
            elAtualizacao.innerHTML = novaAtualizacaoTexto;
            flashElement(elAtualizacao);
        }
    }

    function atualizarComentarios(comentarios) {
        const lista = document.getElementById('lista-comentarios');
        if (!lista) return;

        let novoHtml = '';
        if (comentarios.length === 0) {
            novoHtml = '<p class="nenhum-comentario">Nenhum comentário ainda.</p>';
        } else {
            comentarios.forEach(comentario => {
                if (tipoUsuario !== 'ti' && comentario.interno == 1) {
                    return;
                }
                
                const dataFormatada = new Date(comentario.data_comentario).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                const tagInterno = comentario.interno == 1 ? '<span class="tag-interno">INTERNO</span>' : '';
                const classeInterno = comentario.interno == 1 ? 'interno' : '';
                const corpoComentario = comentario.comentario.replace(/\n/g, '<br>');

                novoHtml += `
                    <div class="comentario ${classeInterno}">
                        <div class="comentario-header">
                            <strong>${escapeHTML(comentario.nome_usuario)}</strong> comentou em ${dataFormatada}
                            ${tagInterno}
                        </div>
                        <div class="comentario-corpo">
                            ${corpoComentario}
                        </div>
                    </div>
                `;
            });
        }
        
        if (lista.innerHTML.replace(/\s/g, '') !== novoHtml.replace(/\s/g, '')) {
            const scrollNoFundo = (window.innerHeight + window.scrollY) >= document.body.offsetHeight - 50;
            
            lista.innerHTML = novoHtml;
            flashElement(lista.closest('.historico-chamado'));
            
            if(scrollNoFundo){
                window.scrollTo(0, document.body.scrollHeight);
            }
        }
    }

    async function verificarAtualizacoes() {
        try {
            const cacheBuster = new Date().getTime();
            const url = `/chamados_contec/verificar_updates.php?id_chamado=${idChamado}&t=${cacheBuster}`;
            const response = await fetch(url);
            const data = await response.json();

            if (data.error) { return; }
            
            if (data.detalhes_chamado) {
                atualizarDetalhes(data.detalhes_chamado);
            }
            if (data.comentarios) {
                atualizarComentarios(data.comentarios);
            }
        } catch (error) {
            console.error("Erro na verificação:", error);
        }
    }
    
    setInterval(verificarAtualizacoes, 3000);
});
</script>

</html>
