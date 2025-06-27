<?php
// atender_chamado.php - VERSÃO FINAL COM NOTIFICAÇÃO COMPLETA

session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';
require_once __DIR__ . '/includes/push_service.php';
require_once __DIR__ . '/includes/websocket_service.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 'ti') {
    $_SESSION['mensagem_erro'] = "Você não tem permissão para executar esta ação.";
    header('Location: painel.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensagem_erro'] = "ID do chamado inválido.";
    header('Location: painel.php');
    exit();
}

$id_chamado = intval($_GET['id']);
$id_agente = $_SESSION['usuario_id'];
$nome_agente = $_SESSION['usuario_nome'];

$conexao->begin_transaction();

try {
    // Busca informações essenciais do ticket
    $stmt_info = $conexao->prepare("SELECT id_solicitante FROM tickets WHERE id = ?");
    $stmt_info->bind_param("i", $id_chamado);
    $stmt_info->execute();
    $ticket_info = $stmt_info->get_result()->fetch_assoc();
    $stmt_info->close();
    
    if (!$ticket_info) { throw new Exception("Chamado não encontrado."); }
    $id_solicitante = $ticket_info['id_solicitante'];

    // 1. ATUALIZA O TICKET (Status e Agente)
    $id_status_em_andamento = 2; // Supondo que 'Em Andamento' seja ID 2
    $sql_update = "UPDATE tickets SET id_agente_atribuido = ?, id_status = ?, data_ultima_atualizacao = NOW() WHERE id = ? AND id_agente_atribuido IS NULL";
    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->bind_param("iii", $id_agente, $id_status_em_andamento, $id_chamado);
    $stmt_update->execute();

    if ($stmt_update->affected_rows === 0) {
        $_SESSION['mensagem_aviso'] = "Este chamado já foi atribuído a outro agente.";
        $conexao->rollback();
        header('Location: painel.php');
        exit();
    }
    $stmt_update->close();

    // 2. INSERE REGISTRO NO HISTÓRICO E NOTIFICAÇÃO NO BANCO
    $comentario_sistema = "Chamado atribuído ao agente " . htmlspecialchars($nome_agente) . " e status alterado para 'Em Andamento'.";
    $stmt_comentario = $conexao->prepare("INSERT INTO comentarios_tickets (id_ticket, id_usuario, comentario) VALUES (?, ?, ?)");
    $stmt_comentario->bind_param("iis", $id_chamado, $id_agente, $comentario_sistema);
    $stmt_comentario->execute();
    $stmt_comentario->close();
    
    $mensagem_notificacao = "Seu chamado #" . $id_chamado . " foi recebido e já está em andamento.";
    $stmt_notificacao = $conexao->prepare("INSERT INTO notificacoes (id_usuario_destino, id_ticket, mensagem) VALUES (?, ?, ?)");
    $stmt_notificacao->bind_param("iis", $id_solicitante, $id_chamado, $mensagem_notificacao);
    $stmt_notificacao->execute();
    $stmt_notificacao->close();

    // 3. SALVA TUDO NO BANCO
    $conexao->commit();

    // 4. ENVIA AS NOTIFICAÇÕES EM TEMPO REAL
    // Envia Push
    enviar_notificacao_push($conexao, [$id_solicitante], $id_chamado, "Seu chamado está em andamento!", $mensagem_notificacao, 'chamado-' . $id_chamado);

    // Envia sinal para o painel do TI (para remover o chamado da lista de não atribuídos)
    enviar_para_topico('dashboard-ti', ['type' => 'refresh_dashboard']);

    // Envia sinal para o painel do colaborador
    enviar_para_usuario($id_solicitante, ['type' => 'refresh_dashboard']);

    // Envia atualização para a página de detalhes do chamado
    $sql_dados_ws = "SELECT t.data_ultima_atualizacao, agente.nome_completo AS nome_agente, s.nome AS nome_status FROM tickets t LEFT JOIN usuarios agente ON t.id_agente_atribuido = agente.id JOIN status_tickets s ON t.id_status = s.id WHERE t.id = ?";
    $stmt_ws = $conexao->prepare($sql_dados_ws);
    $stmt_ws->bind_param("i", $id_chamado);
    $stmt_ws->execute();
    $dados_para_ws = $stmt_ws->get_result()->fetch_assoc();
    $stmt_ws->close();
    if($dados_para_ws) {
        enviar_para_topico("chamado-{$id_chamado}", ['type' => 'update_ticket_details', 'payload' => $dados_para_ws]);
    }
    
    $_SESSION['mensagem_sucesso'] = "Chamado #" . $id_chamado . " atribuído a você com sucesso!";
    header('Location: detalhes_chamado.php?id=' . $id_chamado);
    exit();

} catch (Exception $e) {
    $conexao->rollback();
    error_log("Erro na transação de atendimento de chamado: " . $e->getMessage());
    $_SESSION['mensagem_erro'] = "Ocorreu um erro crítico ao tentar atender o chamado.";
    header('Location: painel.php');
    exit();
}
?>