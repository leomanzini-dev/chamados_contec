<?php
// gerenciar_chamados.php
$titulo_pagina = "Gerenciar Todos os Chamados";
$css_pagina = "tabelas.css"; // Garante que o CSS da tabela é carregado
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Apenas usuários 'ti' podem acessar
if ($tipo_usuario != 'ti') {
    header("Location: painel.php");
    exit();
}

// --- LÓGICA DOS FILTROS ---
$filtro_status = filter_input(INPUT_GET, 'status', FILTER_VALIDATE_INT);
$filtro_prioridade = filter_input(INPUT_GET, 'prioridade', FILTER_VALIDATE_INT);
$filtro_agente = filter_input(INPUT_GET, 'agente', FILTER_VALIDATE_INT);
$filtro_busca = trim(filter_input(INPUT_GET, 'busca', FILTER_SANITIZE_STRING));

// Monta a consulta SQL dinamicamente
$sql = "SELECT 
            t.id, t.motivo_chamado, t.data_ultima_atualizacao,
            c.nome AS nome_categoria, p.nome AS nome_prioridade, s.nome AS nome_status,
            solicitante.nome_completo AS nome_solicitante,
            agente.nome_completo AS nome_agente
        FROM tickets AS t
        JOIN usuarios AS solicitante ON t.id_solicitante = solicitante.id
        LEFT JOIN usuarios AS agente ON t.id_agente_atribuido = agente.id
        JOIN categorias AS c ON t.id_categoria = c.id
        JOIN prioridades AS p ON t.id_prioridade = p.id
        JOIN status_tickets AS s ON t.id_status = s.id";

$where_clauses = [];
$params = [];
$types = '';

if ($filtro_status) { $where_clauses[] = "t.id_status = ?"; $params[] = $filtro_status; $types .= 'i'; }
if ($filtro_prioridade) { $where_clauses[] = "t.id_prioridade = ?"; $params[] = $filtro_prioridade; $types .= 'i'; }
if ($filtro_agente) { $where_clauses[] = "t.id_agente_atribuido = ?"; $params[] = $filtro_agente; $types .= 'i'; }
if (!empty($filtro_busca)) { $where_clauses[] = "(t.motivo_chamado LIKE ? OR solicitante.nome_completo LIKE ?)"; $params[] = "%" . $filtro_busca . "%"; $params[] = "%" . $filtro_busca . "%"; $types .= 'ss'; }

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY t.data_ultima_atualizacao DESC";

$stmt = $conexao->prepare($sql);
if ($stmt && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}
if ($stmt) {
    $stmt->execute();
    $resultado = $stmt->get_result();
    $chamados = $resultado->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $chamados = [];
}

// Buscar dados para preencher os dropdowns dos filtros
$todos_status = $conexao->query("SELECT id, nome FROM status_tickets ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$todas_prioridades = $conexao->query("SELECT id, nome FROM prioridades ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
$todos_agentes = $conexao->query("SELECT id, nome_completo FROM usuarios WHERE tipo_usuario = 'ti' ORDER BY nome_completo")->fetch_all(MYSQLI_ASSOC);
?>

<div class="main-content">
    <div class="main-header">
        <h1><?php echo $titulo_pagina; ?></h1>
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

    <div class="filtros-container">
        <div class="filtros-header">
            <h4><i class="fa-solid fa-filter"></i> Filtros</h4>
            <button class="toggle-btn"><i class="fa-solid fa-chevron-up"></i></button>
        </div>
        <div class="filtros-body">
            <form action="gerenciar_chamados.php" method="GET" class="filtros-form">
                <div class="filtro-item">
                    <label for="busca">Buscar por Assunto/Solicitante</label>
                    <input type="text" name="busca" id="busca" value="<?php echo htmlspecialchars($filtro_busca); ?>" placeholder="Digite para buscar...">
                </div>
                <div class="filtro-item">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">Todos</option>
                        <?php foreach($todos_status as $status): ?>
                            <option value="<?php echo $status['id']; ?>" <?php echo ($filtro_status == $status['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtro-item">
                    <label for="prioridade">Prioridade</label>
                    <select name="prioridade" id="prioridade">
                        <option value="">Todas</option>
                        <?php foreach($todas_prioridades as $prio): ?>
                            <option value="<?php echo $prio['id']; ?>" <?php echo ($filtro_prioridade == $prio['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prio['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtro-item">
                    <label for="agente">Agente</label>
                    <select name="agente" id="agente">
                        <option value="">Todos</option>
                        <?php foreach($todos_agentes as $agente): ?>
                            <option value="<?php echo $agente['id']; ?>" <?php echo ($filtro_agente == $agente['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($agente['nome_completo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtro-botoes">
                    <button type="submit" class="btn-filtrar">Filtrar</button>
                    <a href="gerenciar_chamados.php" class="btn-limpar">Limpar</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nº</th>
                    <th>Assunto</th>
                    <th>Solicitante</th>
                    <th>Agente</th>
                    <th>Categoria</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Última Atualização</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="tabela-gerenciar-corpo">
                <?php if (empty($chamados)): ?>
                    <tr>
                        <td colspan="9" class="nenhum-chamado">Nenhum chamado encontrado com os filtros aplicados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($chamados as $chamado): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($chamado['id']); ?></td>
                            <td><?php echo htmlspecialchars($chamado['motivo_chamado']); ?></td>
                            <td><?php echo htmlspecialchars($chamado['nome_solicitante']); ?></td>
                            <td><?php echo $chamado['nome_agente'] ? htmlspecialchars($chamado['nome_agente']) : '<em>Não atribuído</em>'; ?></td>
                            <td><?php echo htmlspecialchars($chamado['nome_categoria']); ?></td>
                            <td><?php echo htmlspecialchars($chamado['nome_prioridade']); ?></td>
                            <td>
                                <span class="status status-<?php echo strtolower(str_replace(' ', '-', $chamado['nome_status'])); ?>">
                                    <?php echo htmlspecialchars($chamado['nome_status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($chamado['data_ultima_atualizacao']))); ?></td>
                            <td>
                                <a href="detalhes_chamado.php?id=<?php echo $chamado['id']; ?>" class="btn-acao">Ver Detalhes</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
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

    function atualizarTabelaGerenciar(chamados) {
        const tabelaCorpo = document.getElementById('tabela-gerenciar-corpo');
        if (!tabelaCorpo) return;

        let novoHtml = '';
        if (chamados.length === 0) {
            novoHtml = `<tr><td colspan="9" class="nenhum-chamado">Nenhum chamado encontrado com os filtros aplicados.</td></tr>`;
        } else {
            chamados.forEach(chamado => {
                const statusClass = 'status-' + (chamado.nome_status ? chamado.nome_status.toLowerCase().replace(/ /g, '-') : 'indefinido');
                const agente = chamado.nome_agente ? escapeHTML(chamado.nome_agente) : '<em>Não atribuído</em>';
                const dataFormatada = new Date(chamado.data_ultima_atualizacao).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });

                novoHtml += `
                    <tr>
                        <td>${escapeHTML(chamado.id)}</td>
                        <td>${escapeHTML(chamado.motivo_chamado)}</td>
                        <td>${escapeHTML(chamado.nome_solicitante)}</td>
                        <td>${agente}</td>
                        <td>${escapeHTML(chamado.nome_categoria)}</td>
                        <td>${escapeHTML(chamado.nome_prioridade)}</td>
                        <td><span class="status ${statusClass}">${escapeHTML(chamado.nome_status)}</span></td>
                        <td>${dataFormatada}</td>
                        <td><a href="detalhes_chamado.php?id=${chamado.id}" class="btn-acao">Ver Detalhes</a></td>
                    </tr>
                `;
            });
        }
        
        if (tabelaCorpo.innerHTML.replace(/\s/g, '') !== novoHtml.replace(/\s/g, '')) {
            tabelaCorpo.innerHTML = novoHtml;
            flashElement(tabelaCorpo.closest('.table-container'));
        }
    }
    
    function atualizarNotificacoes(contagem, listaNotificacoes) {
        const contador = document.getElementById('contador-notificacoes');
        const corpoDropdown = document.getElementById('notificacoes-body');
        if (!contador || !corpoDropdown) return;
        const contagemAtual = parseInt(contador.innerText) || 0;
        if (contagem > 0) {
            contador.innerText = contagem;
            contador.style.display = 'inline-block';
            if(contagem > contagemAtual) flashElement(contador);
        } else {
            contador.style.display = 'none';
        }
        let novoHtmlNotif = '';
        if(listaNotificacoes.length === 0){
             novoHtmlNotif = `<div class="notificacao-item"><div class="mensagem">Nenhuma notificação nova.</div></div>`;
        } else {
            listaNotificacoes.forEach(notif => {
                const dataFormatada = new Date(notif.data_criacao).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                novoHtmlNotif += `<a href="detalhes_chamado.php?id=${notif.id_ticket}" class="notificacao-item"><div class="icon"><i class="fa-solid fa-ticket"></i></div><div><div class="mensagem">${escapeHTML(notif.mensagem)}</div><div class="data">${dataFormatada}</div></div></a>`;
            });
        }
        corpoDropdown.innerHTML = novoHtmlNotif;
    }

    async function verificarAtualizacoes() {
        try {
            const cacheBuster = new Date().getTime();
            const urlFiltros = new URLSearchParams(window.location.search);
            const url = `/chamados_contec/verificar_updates_geral.php?${urlFiltros.toString()}&t=${cacheBuster}`;
            
            const response = await fetch(url);
            const data = await response.json();

            if (data.error) { return; }
            
            if (data.chamados_gerenciados) {
                atualizarTabelaGerenciar(data.chamados_gerenciados);
            }
            
            if (typeof data.notificacoes_nao_lidas !== 'undefined' && data.lista_notificacoes) {
                atualizarNotificacoes(data.notificacoes_nao_lidas, data.lista_notificacoes);
            }

        } catch (error) {
            console.error("Erro na verificação:", error);
        }
    }
    
    if ("<?php echo $tipo_usuario; ?>" === 'ti') {
        setInterval(verificarAtualizacoes, 5000);
    }
});
</script>

</body>
</html>
