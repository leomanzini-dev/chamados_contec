<?php
// painel.php - VERSÃO COM ATUALIZAÇÃO COMPLETA PARA TODOS OS UTILIZADORES

$titulo_pagina = "Painel Principal";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// --- LÓGICA PHP PARA BUSCAR OS DADOS INICIAIS DA PÁGINA ---
$stats = [];
$ultimos_chamados = [];
$novos_chamados_nao_atribuidos = [];
$meus_chamados_ativos = [];
$artigos_populares = [];

if ($tipo_usuario == 'ti') {
    $sql_stats_ti = "SELECT (SELECT COUNT(id) FROM tickets WHERE id_status = 1) AS abertos, (SELECT COUNT(id) FROM tickets WHERE id_status = 2) AS andamento, (SELECT COUNT(id) FROM tickets WHERE id_status = 5) AS resolvidos_total FROM DUAL";
    $stats = $conexao->query($sql_stats_ti)->fetch_assoc();
    $sql_novos = "SELECT t.id, t.motivo_chamado, u.nome_completo as nome_solicitante FROM tickets t JOIN usuarios u ON t.id_solicitante = u.id WHERE t.id_agente_atribuido IS NULL AND t.id_status = 1 ORDER BY t.data_criacao DESC LIMIT 15";
    $novos_chamados_nao_atribuidos = $conexao->query($sql_novos)->fetch_all(MYSQLI_ASSOC);
    $sql_meus_ativos = "SELECT t.id, t.motivo_chamado, s.nome as nome_status FROM tickets t JOIN status_tickets s ON t.id_status = s.id WHERE t.id_agente_atribuido = ? AND s.nome NOT IN ('Resolvido', 'Cancelado') ORDER BY t.data_ultima_atualizacao DESC LIMIT 5";
    $stmt_meus = $conexao->prepare($sql_meus_ativos);
    if ($stmt_meus) { $stmt_meus->bind_param("i", $id_usuario_logado); $stmt_meus->execute(); $meus_chamados_ativos = $stmt_meus->get_result()->fetch_all(MYSQLI_ASSOC); $stmt_meus->close(); }
} else { 
    $sql_stats_colab = "SELECT (SELECT COUNT(id) FROM tickets WHERE id_solicitante = ? AND id_status NOT IN (5, 6)) AS meus_abertos, (SELECT COUNT(id) FROM tickets WHERE id_solicitante = ? AND id_status = 5) AS meus_resolvidos FROM DUAL";
    $stmt_stats = $conexao->prepare($sql_stats_colab);
    if ($stmt_stats) { $stmt_stats->bind_param("ii", $id_usuario_logado, $id_usuario_logado); $stmt_stats->execute(); $stats = $stmt_stats->get_result()->fetch_assoc(); $stmt_stats->close(); }
    $sql_ultimos_colab = "SELECT t.id, t.motivo_chamado, s.nome as nome_status FROM tickets t JOIN status_tickets s ON t.id_status = s.id WHERE t.id_solicitante = ? ORDER BY t.data_ultima_atualizacao DESC LIMIT 5";
    $stmt_chamados = $conexao->prepare($sql_ultimos_colab);
    if ($stmt_chamados) { $stmt_chamados->bind_param("i", $id_usuario_logado); $stmt_chamados->execute(); $ultimos_chamados = $stmt_chamados->get_result()->fetch_all(MYSQLI_ASSOC); $stmt_chamados->close(); }
    $sql_kb = "SELECT id, titulo FROM kb_artigos WHERE visivel_para = 'todos' ORDER BY visualizacoes DESC, votos_uteis DESC LIMIT 3";
    $artigos_populares = $conexao->query($sql_kb)->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="main-content">
    <div class="main-header">
        <h1><?php echo htmlspecialchars($titulo_pagina); ?></h1>
        <div class="user-menu">
            <div class="notificacao-sino">
                <i class="fa-solid fa-bell"></i>
                <span class="contador" id="contador-notificacoes" style="<?php echo (!isset($total_nao_lidas) || $total_nao_lidas == 0) ? 'display: none;' : ''; ?>"><?php echo $total_nao_lidas ?? 0; ?></span>
                <div class="notificacoes-dropdown">
                    <div class="notificacoes-header">Notificações</div>
                    <div class="notificacoes-body" id="notificacoes-body">
                        <?php if (empty($lista_notificacoes)): ?>
                            <div class="notificacao-item"><div class="mensagem">Nenhuma notificação nova.</div></div>
                        <?php else: ?>
                            <?php foreach ($lista_notificacoes as $notif): ?>
                                <a href="detalhes_chamado.php?id=<?php echo $notif['id_ticket']; ?>" class="notificacao-item">
                                    <div class="icon"><i class="fa-solid fa-ticket"></i></div>
                                    <div>
                                        <div class="mensagem"><?php echo htmlspecialchars($notif['mensagem']); ?></div>
                                        <div class="data"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($notif['data_criacao']))); ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="notificacoes-footer"><a href="notificacoes.php">Ver todas as notificações</a></div>
                </div>
            </div>
            <span>Olá, <?php echo htmlspecialchars($nome_usuario); ?>!</span>
            <a href="logout.php" class="logout-link">Sair</a>
        </div>
    </div>

    <div class="content-body">
        <?php
        if (isset($_SESSION['mensagem_erro'])) { echo '<div class="alerta erro">' . htmlspecialchars($_SESSION['mensagem_erro']) . '</div>'; unset($_SESSION['mensagem_erro']); }
        if (isset($_SESSION['mensagem_aviso'])) { echo '<div class="alerta aviso">' . htmlspecialchars($_SESSION['mensagem_aviso']) . '</div>'; unset($_SESSION['mensagem_aviso']); }
        if (isset($_SESSION['mensagem_sucesso'])) { echo '<div class="alerta sucesso">' . htmlspecialchars($_SESSION['mensagem_sucesso']) . '</div>'; unset($_SESSION['mensagem_sucesso']); }
        ?>
        <div class="dashboard-grid">
            <?php if ($tipo_usuario == 'ti'): ?>
                <div class="stat-card abertos"><div class="icon"><i class="fa-solid fa-folder-open"></i></div><div class="info"><span class="number" id="stat-abertos"><?php echo $stats['abertos'] ?? 0; ?></span><span class="label">Chamados Abertos</span></div></div>
                <div class="stat-card andamento"><div class="icon"><i class="fa-solid fa-person-running"></i></div><div class="info"><span class="number" id="stat-andamento"><?php echo $stats['andamento'] ?? 0; ?></span><span class="label">Em Andamento</span></div></div>
                <div class="stat-card resolvidos"><div class="icon"><i class="fa-solid fa-check-double"></i></div><div class="info"><span class="number" id="stat-resolvidos"><?php echo $stats['resolvidos_total'] ?? 0; ?></span><span class="label">Total de Resolvidos</span></div></div>
            <?php else: ?>
                <div class="stat-card meus-chamados"><div class="icon"><i class="fa-solid fa-ticket"></i></div><div class="info"><span class="number" id="stat-colab-abertos"><?php echo $stats['meus_abertos'] ?? 0; ?></span><span class="label">Meus Chamados Ativos</span></div></div>
                <div class="stat-card resolvidos"><div class="icon"><i class="fa-solid fa-check"></i></div><div class="info"><span class="number" id="stat-colab-resolvidos"><?php echo $stats['meus_resolvidos'] ?? 0; ?></span><span class="label">Meus Chamados Resolvidos</span></div></div>
                <a href="abrir_chamado.php" class="stat-card action-card"><div class="icon"><i class="fa-solid fa-plus"></i></div><div class="info"><span class="label">Precisa de Ajuda?</span><span class="action-text">Abrir Novo Chamado</span></div></a>
            <?php endif; ?>
        </div>
        <div class="dashboard-grid-listas">
            <?php if ($tipo_usuario == 'ti'): ?>
                <div class="recentes-card">
                    <h3>Novos Chamados (Não Atribuídos)</h3>
                    <ul id="lista-novos-chamados">
                        <?php if (empty($novos_chamados_nao_atribuidos)): ?>
                            <li class="nenhum-chamado"><p>Nenhum chamado novo aguardando atribuição.</p></li>
                        <?php else: ?>
                            <?php foreach ($novos_chamados_nao_atribuidos as $chamado): ?>
                            <li id="chamado-item-<?php echo $chamado['id']; ?>"><div class="chamado-info"><a href="detalhes_chamado.php?id=<?php echo $chamado['id']; ?>">Chamado #<?php echo $chamado['id']; ?>: <?php echo htmlspecialchars($chamado['motivo_chamado']); ?></a><div class="sub-info">Solicitado por: <?php echo htmlspecialchars($chamado['nome_solicitante']); ?></div></div><a href="atender_chamado.php?id=<?php echo $chamado['id']; ?>" class="btn-acao-sm">Atender</a></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="recentes-card">
                    <h3>Meus Chamados Ativos</h3>
                    <ul id="lista-meus-chamados">
                        <?php if (empty($meus_chamados_ativos)): ?>
                            <li class="nenhum-chamado"><p>Você não tem chamados ativos atribuídos.</p></li>
                        <?php else: ?>
                            <?php foreach ($meus_chamados_ativos as $chamado): ?>
                            <li><div class="chamado-info"><a href="detalhes_chamado.php?id=<?php echo $chamado['id']; ?>">Chamado #<?php echo $chamado['id']; ?>: <?php echo htmlspecialchars($chamado['motivo_chamado']); ?></a></div><span class="status status-<?php echo strtolower(str_replace(' ', '-', $chamado['nome_status'])); ?>"><?php echo htmlspecialchars($chamado['nome_status']); ?></span></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="recentes-card">
                    <h3>Últimos 5 Chamados Atualizados</h3>
                    <ul id="lista-ultimos-chamados">
                        <?php if (empty($ultimos_chamados)): ?>
                            <li class="nenhum-chamado"><p>Nenhuma atividade recente em seus chamados.</p></li>
                        <?php else: ?>
                            <?php foreach ($ultimos_chamados as $chamado): ?>
                            <li><div class="chamado-info"><a href="detalhes_chamado.php?id=<?php echo $chamado['id']; ?>">Chamado #<?php echo $chamado['id']; ?>: <?php echo htmlspecialchars($chamado['motivo_chamado']); ?></a></div><span class="status status-<?php echo strtolower(str_replace(' ', '-', $chamado['nome_status'])); ?>"><?php echo htmlspecialchars($chamado['nome_status']); ?></span></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="recentes-card">
                    <h3>Artigos Populares</h3>
                    <?php if (empty($artigos_populares)): ?>
                        <p>Nenhum artigo disponível no momento.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($artigos_populares as $artigo): ?>
                            <li><div class="chamado-info"><a href="ver_artigo.php?id=<?php echo $artigo['id']; ?>"><i class="fa-solid fa-book-open icon-lista"></i> <?php echo htmlspecialchars($artigo['titulo']); ?></a></div></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($conexao) { $conexao->close(); } ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // =======================================================
    // PARTE 1: OUVINTES DE EVENTOS WEBSOCKET
    // Esta parte "ouve" os anúncios feitos pelo websocket_client.js e chama a função certa.
    // =======================================================

    // Ouve pelo evento que adiciona um novo chamado na lista de "Não Atribuídos"
    document.addEventListener('ws:dashboard_new_ticket', function(event) {
        if (typeof adicionarChamadosNaLista === 'function') {
            console.log("Recebido evento 'dashboard_new_ticket'. Chamando a função para atualizar a lista...");
            adicionarChamadosNaLista([event.detail]); // A função espera um array
        }
    });

    // Ouve pelo evento que atualiza os números das estatísticas
    document.addEventListener('ws:update_dashboard_stats', function(event) {
        console.log("Recebido evento 'update_dashboard_stats'. Chamando a função para atualizar estatísticas...");
        if (typeof atualizarEstatisticasTI === 'function') {
            atualizarEstatisticasTI(event.detail);
        }
        if (typeof atualizarEstatisticasColaborador === 'function') {
            atualizarEstatisticasColaborador(event.detail);
        }
    });
    
    // Ouve pela notificação global para atualizar o sino
    document.addEventListener('ws:global_notification', function(event) {
        if (typeof atualizarNotificacoes === 'function') {
            console.log("Recebido evento 'global_notification'. Chamando a função para atualizar o sino...");
             // No futuro, o ideal é o backend enviar o novo total e a lista
             // Por agora, vamos simular para teste
             const contador = document.getElementById('contador-notificacoes');
             const contagemAtual = parseInt(contador.textContent) || 0;
             atualizarNotificacoes(contagemAtual + 1, [ { mensagem: event.detail.message, id_ticket: 0, data_criacao: new Date() } ]);
        }
    });


    // =================================================================
    // PARTE 2: FUNÇÕES QUE ATUALIZAM A INTERFACE (O SEU CÓDIGO ORIGINAL)
    // Estas funções são as que você já tinha. Elas continuam aqui, prontas para serem chamadas.
    // =================================================================

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
        setTimeout(() => { element.style.backgroundColor = ''; }, 1000);
    }

    function atualizarEstatisticasColaborador(estatisticas) {
        const elAbertos = document.getElementById('stat-colab-abertos');
        const elResolvidos = document.getElementById('stat-colab-resolvidos');
        if (elAbertos && estatisticas.meus_abertos && elAbertos.innerText != estatisticas.meus_abertos) {
            elAbertos.innerText = estatisticas.meus_abertos;
            flashElement(elAbertos.closest('.stat-card'));
        }
        if (elResolvidos && estatisticas.meus_resolvidos && elResolvidos.innerText != estatisticas.meus_resolvidos) {
            elResolvidos.innerText = estatisticas.meus_resolvidos;
            flashElement(elResolvidos.closest('.stat-card'));
        }
    }

    function atualizarUltimosChamados(chamados) {
        // Esta função pode ser chamada no futuro por um evento específico para o colaborador
    }

    function atualizarEstatisticasTI(estatisticas) {
        const elAbertos = document.getElementById('stat-abertos');
        if(elAbertos && estatisticas.abertos && elAbertos.innerText != estatisticas.abertos) {
            elAbertos.innerText = estatisticas.abertos;
            flashElement(elAbertos.closest('.stat-card'));
        }
        // Adicione aqui a lógica para os outros stats de TI se necessário
    }

    function adicionarChamadosNaLista(chamados) {
        const listaNovosChamados = document.getElementById('lista-novos-chamados');
        if (!listaNovosChamados) return;

        chamados.forEach(chamado => {
            if (document.getElementById(`chamado-item-${chamado.id}`)) return; // Não adiciona se já existir
            
            const itemNenhumChamado = listaNovosChamados.querySelector('.nenhum-chamado');
            if (itemNenhumChamado) itemNenhumChamado.parentElement.remove();
            
            const novoItem = document.createElement('li');
            novoItem.id = `chamado-item-${chamado.id}`;
            novoItem.innerHTML = `<div class="chamado-info"><a href="detalhes_chamado.php?id=${chamado.id}">Chamado #${chamado.id}: ${escapeHTML(chamado.motivo_chamado)}</a><div class="sub-info">Solicitado por: ${escapeHTML(chamado.nome_solicitante)}</div></div><a href="atender_chamado.php?id=${chamado.id}" class="btn-acao-sm">Atender</a>`;
            
            listaNovosChamados.prepend(novoItem);
            flashElement(novoItem);
        });
    }

    function atualizarNotificacoes(contagem, listaNotificacoes) {
        const contador = document.getElementById('contador-notificacoes');
        const corpoDropdown = document.getElementById('notificacoes-body');
        if (!contador || !corpoDropdown) return;

        if (contagem > 0) {
            contador.innerText = contagem;
            contador.style.display = 'inline-block';
            flashElement(contador);
        } else {
            contador.style.display = 'none';
        }

        // Esta parte pode ser melhorada para adicionar ao topo em vez de substituir tudo
        const itemAtual = corpoDropdown.innerHTML;
        let novoHtml = '';
        listaNotificacoes.forEach(notif => {
            const dataFormatada = new Date(notif.data_criacao).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            novoHtml += `<a href="detalhes_chamado.php?id=${notif.id_ticket}" class="notificacao-item"><div class="icon"><i class="fa-solid fa-ticket"></i></div><div><div class="mensagem">${escapeHTML(notif.mensagem)}</div><div class="data">${dataFormatada}</div></div></a>`;
        });
        corpoDropdown.innerHTML = novoHtml + itemAtual.replace('<div class="notificacao-item"><div class="mensagem">Nenhuma notificação nova.</div></div>', '');
    }
});
</script>

</body>
</html>
