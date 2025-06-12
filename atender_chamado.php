<?php
// atender_chamado.php - VERSÃO FINAL COM HISTÓRICO E NOTIFICAÇÃO

require_once 'includes/header.php';

// --- VERIFICAÇÕES DE SEGURANÇA E PARÂMETROS ---
if (!isset($tipo_usuario) || $tipo_usuario != 'ti') {
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
$id_agente = $id_usuario_logado; 
$nome_agente = $nome_usuario; // Pegando o nome do agente logado, que já vem do header.php

// --- BUSCAR DADOS ESSENCIAIS ANTES DA ATUALIZAÇÃO ---
// <<< NOVO >>> Precisamos do ID do solicitante para poder notificá-lo.
$sql_ticket_info = "SELECT id_solicitante FROM tickets WHERE id = ?";
$stmt_info = $conexao->prepare($sql_ticket_info);
if (!$stmt_info) {
    // Tratar erro de preparação, se necessário
    $_SESSION['mensagem_erro'] = "Erro ao preparar consulta de informações do ticket.";
    header('Location: painel.php');
    exit();
}
$stmt_info->bind_param("i", $id_chamado);
$stmt_info->execute();
$resultado_info = $stmt_info->get_result();
if ($resultado_info->num_rows === 0) {
    $_SESSION['mensagem_erro'] = "Chamado não encontrado.";
    header('Location: painel.php');
    exit();
}
$ticket_info = $resultado_info->fetch_assoc();
$id_solicitante = $ticket_info['id_solicitante'];
$stmt_info->close();


// --- TRANSAÇÃO NO BANCO DE DADOS ---
// Usar uma transação garante que todas as 3 operações (Update, Insert Comentário, Insert Notificação)
// ocorram com sucesso. Se uma falhar, todas são desfeitas.
$conexao->begin_transaction();

try {
    // 1. ATUALIZAR O TICKET (Status e Agente)
    $id_status_em_andamento = 2; // Supondo que o ID de 'Em Andamento' seja 2, conforme depuração.
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

    // 2. <<< NOVO >>> INSERIR REGISTRO NO HISTÓRICO DE COMENTÁRIOS
    $comentario_sistema = "Chamado atribuído ao agente " . htmlspecialchars($nome_agente) . " e status alterado para 'Em Andamento'.";
    $sql_comentario = "INSERT INTO comentarios_tickets (id_ticket, id_usuario, comentario, data_comentario, interno) VALUES (?, ?, ?, NOW(), 0)";
    $stmt_comentario = $conexao->prepare($sql_comentario);
    // O comentário é feito em nome do agente que atendeu. O "interno = 0" torna visível para o colaborador.
    $stmt_comentario->bind_param("iis", $id_chamado, $id_agente, $comentario_sistema);
    $stmt_comentario->execute();
    $stmt_comentario->close();

    // 3. <<< NOVO >>> INSERIR NOTIFICAÇÃO PARA O COLABORADOR
    $mensagem_notificacao = "Seu chamado #" . $id_chamado . " foi recebido e já está em andamento.";
    $sql_notificacao = "INSERT INTO notificacoes (id_usuario_destino, id_ticket, mensagem, data_criacao, lida) VALUES (?, ?, ?, NOW(), 0)";
    $stmt_notificacao = $conexao->prepare($sql_notificacao);
    // A notificação é para o 'id_solicitante' que pegamos no início do script.
    $stmt_notificacao->bind_param("iis", $id_solicitante, $id_chamado, $mensagem_notificacao);
    $stmt_notificacao->execute();
    $stmt_notificacao->close();

    // Se todas as operações foram bem-sucedidas, confirma as alterações no banco.
    $conexao->commit();

    $_SESSION['mensagem_sucesso'] = "Chamado #" . $id_chamado . " atribuído a você com sucesso!";
    header('Location: detalhes_chamado.php?id=' . $id_chamado);
    exit();

} catch (Exception $e) {
    // Em caso de qualquer erro, desfaz todas as operações.
    $conexao->rollback();
    error_log("Erro na transação de atendimento de chamado: " . $e->getMessage()); // Loga o erro real para o admin
    $_SESSION['mensagem_erro'] = "Ocorreu um erro crítico ao tentar atender o chamado. A operação foi cancelada.";
    header('Location: painel.php');
    exit();
}
?>