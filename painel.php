<?php
// painel.php - VERSÃO FINAL COM TODAS AS CORREÇÕES E FUNCIONALIDADES

$titulo_pagina = "Painel Principal";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// --- LÓGICA PHP PARA BUSCAR OS DADOS INICIAIS DA PÁGINA ---
$stats = [];
$ultimos_chamados = [];
$novos_chamados_nao_atribuidos = [];
$meus_chamados_ativos = [];
$artigos_populares = [];
$lista_notificacoes = [];
$total_nao_lidas = 0;

// Busca notificações para o usuário logado
$stmt_notif_count = $conexao->prepare("SELECT COUNT(id) as total FROM notificacoes WHERE id_usuario_destino = ? AND lida = FALSE");
$stmt_notif_count->bind_param("i", $id_usuario_logado);
$stmt_notif_count->execute();
$total_nao_lidas = $stmt_notif_count->get_result()->fetch_assoc()['total'];
$stmt_notif_count->close();

$stmt_notif_list = $conexao->prepare("SELECT id, id_ticket, mensagem, data_criacao FROM notificacoes WHERE id_usuario_destino = ? ORDER BY data_criacao DESC LIMIT 5");
$stmt_notif_list->bind_param("i", $id_usuario_logado);
$stmt_notif_list->execute();
$lista_notificacoes = $stmt_notif_list->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_notif_list->close();


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
    
    $sql_ultimos_colab = "SELECT t.id, t.id_chamado_usuario, t.motivo_chamado, s.nome as nome_status 
                          FROM tickets t 
                          JOIN status_tickets s ON t.id_status = s.id 
                          WHERE t.id_solicitante = ? 
                          ORDER BY t.data_ultima_atualizacao DESC LIMIT 5";
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
                <span class="contador" id="contador-notificacoes" style="<?php echo ($total_nao_lidas == 0) ? 'display: none;' : ''; ?>"><?php echo $total_nao_lidas; ?></span>
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
        // ===== ALTERAÇÃO PARA CHAMAR O JAVASCRIPT DO TOAST =====
        if (isset($_SESSION['mensagem_sucesso'])) {
            $mensagem_js = addslashes(htmlspecialchars($_SESSION['mensagem_sucesso']));
            echo "<script>showToast('{$mensagem_js}', 'sucesso');</script>";
            unset($_SESSION['mensagem_sucesso']);
        }
        if (isset($_SESSION['mensagem_erro'])) {
            $mensagem_js = addslashes(htmlspecialchars($_SESSION['mensagem_erro']));
            echo "<script>showToast('{$mensagem_js}', 'erro');</script>";
            unset($_SESSION['mensagem_erro']);
        }
        if (isset($_SESSION['mensagem_aviso'])) {
            $mensagem_js = addslashes(htmlspecialchars($_SESSION['mensagem_aviso']));
            echo "<script>showToast('{$mensagem_js}', 'aviso');</script>";
            unset($_SESSION['mensagem_aviso']);
        }
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
                                <li>
                                    <div class="chamado-info">
                                        <a href="detalhes_chamado.php?id=<?php echo $chamado['id']; ?>">
                                            Chamado #<?php echo htmlspecialchars($chamado['id_chamado_usuario'] ?? $chamado['id']); ?>: <?php echo htmlspecialchars($chamado['motivo_chamado']); ?>
                                        </a>
                                    </div>
                                    <span class="status status-<?php echo strtolower(str_replace(' ', '-', $chamado['nome_status'])); ?>">
                                        <?php echo htmlspecialchars($chamado['nome_status']); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="recentes-card">
                    <h3>Artigos Populares</h3>
                     <ul>
                        <?php if (empty($artigos_populares)): ?>
                           <p>Nenhum artigo disponível no momento.</p>
                        <?php else: ?>
                            <?php foreach ($artigos_populares as $artigo): ?>
                            <li><div class="chamado-info"><a href="ver_artigo.php?id=<?php echo $artigo['id']; ?>"><i class="fa-solid fa-book-open icon-lista"></i> <?php echo htmlspecialchars($artigo['titulo']); ?></a></div></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($conexao) { $conexao->close(); } ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const tipoUsuario = "<?php echo $tipo_usuario; ?>";
    
    // Ouve pelo sinal genérico para atualizar o painel
    document.addEventListener('ws:refresh_dashboard', function(event) {
        console.log("Sinal 'refresh_dashboard' recebido! Verificando atualizações...");
        verificarAtualizacoes();
    });

    let ultimoId = 0; // Usado apenas por TI para buscar somente os mais novos

    if (tipoUsuario === 'ti') {
        const itensIniciais = document.querySelectorAll('#lista-novos-chamados li[id^="chamado-item-"]');
        itensIniciais.forEach(item => {
            const id = parseInt(item.id.replace('chamado-item-', ''));
            if (id > ultimoId) ultimoId = id;
        });
    }

    async function verificarAtualizacoes() {
        try {
            // Este endpoint busca todos os dados necessários para o painel de uma vez
            const url = `/chamados_contec/verificar_updates.php?ultimo_id=${ultimoId}&tipo=${tipoUsuario}`;
            
            const response = await fetch(url);
            if (!response.ok) { return; }
            const data = await response.json();

            if (data.error) { return; }
            
            // Roteia os dados recebidos para as funções que atualizam a tela
            if (tipoUsuario === 'ti') {
                if (data.novos_chamados && data.novos_chamados.length > 0) adicionarChamadosNaLista(data.novos_chamados);
                if (data.estatisticas) atualizarEstatisticasTI(data.estatisticas);
                if (data.meus_chamados_ativos) atualizarMeusChamadosTI(data.meus_chamados_ativos);
            } else { // Colaborador
                if (data.estatisticas) atualizarEstatisticasColaborador(data.estatisticas);
                if (data.ultimos_chamados) atualizarUltimosChamados(data.ultimos_chamados);
            }
            
            // O sino de notificação é atualizado para ambos os tipos de usuário
            if (typeof data.notificacoes_nao_lidas !== 'undefined' && data.lista_notificacoes) {
                atualizarNotificacoes(data.notificacoes_nao_lidas, data.lista_notificacoes);
            }
        } catch (error) {
            console.error("Erro no fetch de atualizações:", error);
        }
    }

    // ===== FUNÇÕES DE ATUALIZAÇÃO DA INTERFACE (COMPLETAS) =====

    function adicionarChamadosNaLista(chamados) {
        const listaNovosChamados = document.getElementById('lista-novos-chamados');
        if (!listaNovosChamados) return;
        chamados.forEach(chamado => {
            if (document.getElementById(`chamado-item-${chamado.id}`)) return;
            const itemNenhumChamado = listaNovosChamados.querySelector('.nenhum-chamado');
            if (itemNenhumChamado) itemNenhumChamado.parentElement.remove();
            const novoItem = document.createElement('li');
            novoItem.id = `chamado-item-${chamado.id}`;
            novoItem.innerHTML = `<div class="chamado-info"><a href="detalhes_chamado.php?id=${chamado.id}">Chamado #${chamado.id}: ${escapeHTML(chamado.motivo_chamado)}</a><div class="sub-info">Solicitado por: ${escapeHTML(chamado.nome_solicitante)}</div></div><a href="atender_chamado.php?id=${chamado.id}" class="btn-acao-sm">Atender</a>`;
            listaNovosChamados.prepend(novoItem);
            flashElement(novoItem);
            if (chamado.id > ultimoId) ultimoId = chamado.id;
        });
    }

    function atualizarEstatisticasTI(estatisticas) {
        const elAbertos = document.getElementById('stat-abertos');
        const elAndamento = document.getElementById('stat-andamento');
        const elResolvidos = document.getElementById('stat-resolvidos');
        if (elAbertos && typeof estatisticas.abertos !== 'undefined' && elAbertos.innerText != estatisticas.abertos) {
            elAbertos.innerText = estatisticas.abertos;
            flashElement(elAbertos.closest('.stat-card'));
        }
        if (elAndamento && typeof estatisticas.andamento !== 'undefined' && elAndamento.innerText != estatisticas.andamento) {
            elAndamento.innerText = estatisticas.andamento;
            flashElement(elAndamento.closest('.stat-card'));
        }
        if (elResolvidos && typeof estatisticas.resolvidos_total !== 'undefined' && elResolvidos.innerText != estatisticas.resolvidos_total) {
            elResolvidos.innerText = estatisticas.resolvidos_total;
            flashElement(elResolvidos.closest('.stat-card'));
        }
    }

    function atualizarMeusChamadosTI(chamados) {
        const lista = document.getElementById('lista-meus-chamados');
        if (!lista) return;
        let novoHtml = '';
        if (chamados.length === 0) {
            novoHtml = `<li class="nenhum-chamado"><p>Você não tem chamados ativos atribuídos.</p></li>`;
        } else {
            chamados.forEach(chamado => {
                const statusClass = 'status-' + chamado.nome_status.toLowerCase().replace(/ /g, '-');
                novoHtml += `<li><div class="chamado-info"><a href="detalhes_chamado.php?id=${chamado.id}">Chamado #${chamado.id}: ${escapeHTML(chamado.motivo_chamado)}</a></div><span class="status ${statusClass}">${escapeHTML(chamado.nome_status)}</span></li>`;
            });
        }
        if (lista.innerHTML.replace(/\s/g, '') !== novoHtml.replace(/\s/g, '')) {
            lista.innerHTML = novoHtml;
            flashElement(lista.closest('.recentes-card'));
        }
    }
    
    function atualizarEstatisticasColaborador(estatisticas) {
        const elAbertos = document.getElementById('stat-colab-abertos');
        const elResolvidos = document.getElementById('stat-colab-resolvidos');
        if (elAbertos && typeof estatisticas.meus_abertos !== 'undefined' && elAbertos.innerText != estatisticas.meus_abertos) {
            elAbertos.innerText = estatisticas.meus_abertos;
            flashElement(elAbertos.closest('.stat-card'));
        }
        if (elResolvidos && typeof estatisticas.meus_resolvidos !== 'undefined' && elResolvidos.innerText != estatisticas.meus_resolvidos) {
            elResolvidos.innerText = estatisticas.meus_resolvidos;
            flashElement(elResolvidos.closest('.stat-card'));
        }
    }

    function atualizarUltimosChamados(chamados) {
        const lista = document.getElementById('lista-ultimos-chamados');
        if (!lista) return;
        let novoHtml = '';
        if (chamados.length === 0) {
            novoHtml = `<li class="nenhum-chamado"><p>Nenhuma atividade recente em seus chamados.</p></li>`;
        } else {
            chamados.forEach(chamado => {
                const statusClass = 'status-' + chamado.nome_status.toLowerCase().replace(/ /g, '-');
                novoHtml += `<li><div class="chamado-info"><a href="detalhes_chamado.php?id=${chamado.id}">Chamado #${escapeHTML(chamado.id_chamado_usuario ?? chamado.id)}: ${escapeHTML(chamado.motivo_chamado)}</a></div><span class="status ${statusClass}">${escapeHTML(chamado.nome_status)}</span></li>`;
            });
        }
        if (lista.innerHTML.replace(/\s/g, '') !== novoHtml.replace(/\s/g, '')) {
            lista.innerHTML = novoHtml;
            flashElement(lista.closest('.recentes-card'));
        }
    }
    
    function atualizarNotificacoes(contagem, listaNotificacoes) {
        const contador = document.getElementById('contador-notificacoes');
        const corpoDropdown = document.getElementById('notificacoes-body');
        if (!contador || !corpoDropdown) return;
        const contagemAtual = parseInt(contador.textContent) || 0;
        if (contagem > 0) {
            contador.innerText = contagem;
            contador.style.display = 'inline-block';
            if (contagem > contagemAtual) flashElement(contador);
        } else {
            contador.style.display = 'none';
        }
        let novoHtml = '';
        if (!listaNotificacoes || listaNotificacoes.length === 0) {
            novoHtml = `<div class="notificacao-item"><div class="mensagem">Nenhuma notificação nova.</div></div>`;
        } else {
            listaNotificacoes.forEach(notif => {
                const dataFormatada = new Date(notif.data_criacao).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                novoHtml += `<a href="detalhes_chamado.php?id=${notif.id_ticket}" class="notificacao-item"><div class="icon"><i class="fa-solid fa-ticket"></i></div><div><div class="mensagem">${escapeHTML(notif.mensagem)}</div><div class="data">${dataFormatada}</div></div></a>`;
            });
        }
        corpoDropdown.innerHTML = novoHtml;
    }
    
    function flashElement(element) {
        if (!element) return;
        element.style.transition = 'background-color 0.2s';
        element.style.backgroundColor = '#fffacd';
        setTimeout(() => { element.style.backgroundColor = ''; }, 1500);
    }
    
    function escapeHTML(str) {
        if (typeof str !== 'string') return '';
        const p = document.createElement('p');
        p.textContent = str;
        return p.innerHTML;
    }
});
</script>

</body>
</html>