<?php
// detalhes_chamado.php (VERSÃO FINAL COM FILTRO DE PRIVACIDADE)

$titulo_pagina = "Detalhes do Chamado";
$css_pagina = "detalhes_chamado.css";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// =======================================================
// 1. PREPARAÇÃO E VALIDAÇÃO DOS DADOS
// =======================================================

$id_chamado = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Se o ID for inválido, redireciona com uma notificação de erro
if (!$id_chamado) {
    $_SESSION['mensagem_erro'] = "ID do chamado inválido.";
    header("Location: gerenciar_chamados.php");
    exit();
}

// --- BUSCA DE DADOS DO CHAMADO PRINCIPAL ---
$sql_chamado = "SELECT t.*, t.id_chamado_usuario, solicitante.nome_completo AS nome_solicitante, agente.nome_completo AS nome_agente, c.nome AS nome_categoria, p.nome AS nome_prioridade, s.nome AS nome_status FROM tickets AS t JOIN usuarios AS solicitante ON t.id_solicitante = solicitante.id LEFT JOIN usuarios AS agente ON t.id_agente_atribuido = agente.id JOIN categorias AS c ON t.id_categoria = c.id JOIN prioridades AS p ON t.id_prioridade = p.id JOIN status_tickets AS s ON t.id_status = s.id WHERE t.id = ?";
$stmt = $conexao->prepare($sql_chamado);
$stmt->bind_param("i", $id_chamado);
$stmt->execute();
$chamado = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Se o chamado não for encontrado, redireciona com erro
if (!$chamado) {
    $_SESSION['mensagem_erro'] = "Chamado não encontrado.";
    header("Location: gerenciar_chamados.php");
    exit();
}

// Se o usuário não for TI e não for o dono do chamado, redireciona com erro
if ($tipo_usuario != 'ti' && $chamado['id_solicitante'] != $id_usuario_logado) {
    $_SESSION['mensagem_erro'] = "Você não tem permissão para visualizar este chamado.";
    header("Location: meus_chamados.php");
    exit();
}

// --- BUSCA DE DADOS SECUNDÁRIOS (COMENTÁRIOS, ANEXOS, ETC.) ---

// LÓGICA ATUALIZADA PARA PRIVACIDADE DE COMENTÁRIOS
$sql_comentarios = "SELECT c.*, u.nome_completo AS nome_usuario, u.tipo_usuario
                    FROM comentarios_tickets AS c 
                    JOIN usuarios AS u ON c.id_usuario = u.id 
                    WHERE c.id_ticket = ?";

// Se o usuário logado NÃO for de TI, adiciona a condição para ver apenas comentários públicos
if ($tipo_usuario != 'ti') {
    $sql_comentarios .= " AND c.interno = FALSE";
}

$sql_comentarios .= " ORDER BY c.data_comentario ASC";

$stmt_comentarios = $conexao->prepare($sql_comentarios);
$stmt_comentarios->bind_param("i", $id_chamado);
$stmt_comentarios->execute();
$comentarios = $stmt_comentarios->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_comentarios->close();


$anexos_gerais = [];
$anexos_por_comentario = [];
$todos_anexos = $conexao->execute_query("SELECT * FROM anexos_tickets WHERE id_ticket = ?", [$id_chamado])->fetch_all(MYSQLI_ASSOC);
foreach ($todos_anexos as $anexo) {
    if (is_null($anexo['id_comentario'])) {
        $anexos_gerais[] = $anexo;
    } else {
        $anexos_por_comentario[$anexo['id_comentario']][] = $anexo;
    }
}

$todos_status = [];
$todos_agentes = [];
if ($tipo_usuario == 'ti') {
    $todos_status = $conexao->query("SELECT id, nome FROM status_tickets ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
    $todos_agentes = $conexao->query("SELECT id, nome_completo FROM usuarios WHERE tipo_usuario = 'ti' AND ativo = 1 ORDER BY nome_completo")->fetch_all(MYSQLI_ASSOC);
}

// =======================================================
// 2. INÍCIO DA APRESENTAÇÃO (HTML REESTRUTURADO)
// =======================================================
?>

<div class="main-content">
    <div class="main-header">
        <h1>Detalhes do Chamado #<?php echo htmlspecialchars($chamado['id']); ?></h1>
    </div>

    <div class="content-body">
        <div class="ticket-layout">

            <div class="ticket-main-content">

                <div class="card-section ticket-header-card">
                    <h2><?php echo htmlspecialchars($chamado['motivo_chamado']); ?></h2>
                    <div class="meta-info">
                        <p>Aberto por <strong><?php echo htmlspecialchars($chamado['nome_solicitante']); ?></strong> em <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($chamado['data_criacao']))); ?></p>
                    </div>
                </div>

                <div class="card-section">
                    <h3>Descrição Inicial do Problema</h3>
                    <div class="card-content">
                        <?php echo nl2br(htmlspecialchars($chamado['descricao_detalhada'])); ?>
                    </div>
                </div>

                <div class="card-section">
                    <h3>Histórico do Chamado</h3>
                    <div class="card-content">
                        <div class="timeline">
                            <?php if (empty($comentarios)): ?>
                                <p>Nenhum comentário ou atualização ainda.</p>
                            <?php else: ?>
                                <?php foreach($comentarios as $comentario): ?>
                                    <?php
                                        // LÓGICA PARA ESCOLHER O ÍCONE
                                        $icone_timeline = 'fa-comment-dots'; // Padrão para colaborador
                                        $classe_timeline = 'comentario';

                                        if ($comentario['interno']) {
                                            $icone_timeline = 'fa-user-secret'; // Ícone para nota interna
                                            $classe_timeline = 'interno';
                                        } elseif ($comentario['tipo_usuario'] == 'ti') {
                                            $icone_timeline = 'fa-user-shield'; // Ícone para agente de TI
                                            $classe_timeline = 'agente-ti';
                                        }
                                    ?>
                                    <div class="timeline-item <?php echo $classe_timeline; ?>">
                                        <div class="timeline-icon">
                                            <i class="fa-solid <?php echo $icone_timeline; ?>"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <div class="timeline-header">
                                                <strong><?php echo htmlspecialchars($comentario['nome_usuario']); ?></strong>
                                                <span>comentou em <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($comentario['data_comentario']))); ?></span>
                                                <?php if ($comentario['interno']): ?><span class="tag-interno">INTERNO</span><?php endif; ?>
                                            </div>
                                            <div class="timeline-body">
                                                <?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?>
                                                
                                                <?php if (isset($anexos_por_comentario[$comentario['id']])): ?>
                                                    <div class="comentario-anexos">
                                                        <strong>Anexos:</strong>
                                                        <ul>
                                                            <?php foreach($anexos_por_comentario[$comentario['id']] as $anexo_comentario): ?>
                                                                <li><a href="<?php echo htmlspecialchars($anexo_comentario['caminho_arquivo']); ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-paperclip"></i> <?php echo htmlspecialchars($anexo_comentario['nome_arquivo_original']); ?></a></li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card-section">
                    <h3>Adicionar Resposta</h3>
                    <div class="card-content">
                        <form id="form-comentario" action="processa_comentario.php" method="POST" enctype="multipart/form-data">
                           <input type="hidden" name="id_chamado" value="<?php echo htmlspecialchars($chamado['id']); ?>">
                           <div class="form-group">
                                <label for="comentario-texto">Comentário:</label>
                                <textarea id="comentario-texto" name="comentario" rows="5" placeholder="Digite seu comentário aqui (você pode colar prints com Ctrl+V)..." required></textarea>
                            </div>
                            <div id="preview-container-comentario" class="paste-preview-container"></div>
                            <div class="form-group">
                                <label for="anexos_comentario">Anexar Outros Arquivos (opcional):</label>
                                <input type="file" id="anexos_comentario" name="anexos[]" multiple>
                            </div>
                            <?php if ($tipo_usuario == 'ti'): ?>
                                <div class="checkbox-interno"><input type="checkbox" id="comentario_interno" name="comentario_interno" value="1"><label for="comentario_interno">Marcar como comentário interno</label></div>
                            <?php endif; ?>
                            <div class="action-button" style="text-align: right; margin-top: 10px;">
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Enviar Comentário</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            <div class="ticket-sidebar">
                <div class="sidebar-card">
                    <h3>Detalhes do Chamado</h3>
                    <div class="card-content ticket-details-list">
                        <div class="detail-item"><span class="label">Status</span><p class="status status-<?php echo strtolower(str_replace(' ', '-', $chamado['nome_status'])); ?>"><?php echo htmlspecialchars($chamado['nome_status']); ?></p></div>
                        <div class="detail-item"><span class="label">Prioridade</span><p class="value"><?php echo htmlspecialchars($chamado['nome_prioridade']); ?></p></div>
                        <div class="detail-item"><span class="label">Categoria</span><p class="value"><?php echo htmlspecialchars($chamado['nome_categoria']); ?></p></div>
                        <div class="detail-item"><span class="label">Agente</span><p class="value"><?php echo $chamado['nome_agente'] ? htmlspecialchars($chamado['nome_agente']) : 'Não atribuído'; ?></p></div>
                    </div>
                </div>

                <?php if ($tipo_usuario == 'ti'): ?>
                <div class="sidebar-card">
                    <h3>Gerenciamento</h3>
                    <div class="card-content">
                        <form action="processa_acao_chamado.php" method="POST">
                            <input type="hidden" name="id_chamado" value="<?php echo htmlspecialchars($chamado['id']); ?>">
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="id_status">Alterar Status:</label>
                                <select name="id_status" id="id_status">
                                    <?php foreach ($todos_status as $status): ?>
                                        <option value="<?php echo $status['id']; ?>" <?php echo ($status['id'] == $chamado['id_status']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($status['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="id_agente">Atribuir Agente:</label>
                                <select name="id_agente" id="id_agente">
                                    <option value="0">-- Não atribuído --</option>
                                    <?php foreach ($todos_agentes as $agente): ?>
                                        <option value="<?php echo $agente['id']; ?>" <?php echo ($agente['id'] == $chamado['id_agente_atribuido']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($agente['nome_completo']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Atualizar</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($todos_anexos)): ?>
                <div class="sidebar-card anexos-chamado">
                    <h3>Anexos</h3>
                    <div class="card-content">
                        <ul>
                            <?php foreach($todos_anexos as $anexo): ?>
                                <li><a href="<?php echo htmlspecialchars($anexo['caminho_arquivo']); ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-paperclip"></i> <?php echo htmlspecialchars($anexo['nome_arquivo_original']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($tipo_usuario == 'ti'): ?>
                <div class="sidebar-card danger-zone">
                    <h3>Zona de Perigo</h3>
                    <div class="card-content">
                        <p style="font-size: 0.9rem; margin-bottom: 15px;">Esta ação é irreversível e excluirá o chamado permanentemente.</p>
                        <button type="button" class="btn btn-danger btn-excluir" 
                                data-id="<?php echo $chamado['id']; ?>" 
                                data-nome="Chamado Nº <?php echo $chamado['id']; ?>"
                                data-tipo="chamado">
                            <i class="fa-solid fa-trash-can"></i> Excluir Chamado
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<?php if($conexao) { $conexao->close(); } ?>
</div>

<script src="js/paste-helper.js?v=<?php echo time(); ?>"></script>
<script src="js/detalhes_chamado.js?v=<?php echo time(); ?>"></script>

</body>
</html>