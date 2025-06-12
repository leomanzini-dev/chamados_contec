<?php
// processa_acao_chamado.php
session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

// Apenas usuários 'ti' podem executar esta ação
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 'ti') {
    http_response_code(403);
    die("Acesso negado.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_chamado = filter_input(INPUT_POST, 'id_chamado', FILTER_VALIDATE_INT);
    $novo_id_status = filter_input(INPUT_POST, 'id_status', FILTER_VALIDATE_INT);
    $novo_id_agente = filter_input(INPUT_POST, 'id_agente', FILTER_VALIDATE_INT);

    if (!$id_chamado || !$novo_id_status) { die("Dados inválidos."); }

    $id_agente_logado = $_SESSION['usuario_id'];
    $nome_agente_logado = $_SESSION['usuario_nome'];
    $log_comentarios = []; // Array para armazenar as mensagens de log

    $conexao->begin_transaction();

    try {
        // Busca o estado atual do chamado ANTES de modificar
        $sql_estado_atual = "SELECT id_status, id_agente_atribuido, id_solicitante FROM tickets WHERE id = ?";
        $stmt_atual = $conexao->prepare($sql_estado_atual);
        $stmt_atual->bind_param("i", $id_chamado);
        $stmt_atual->execute();
        $estado_atual = $stmt_atual->get_result()->fetch_assoc();
        $stmt_atual->close();

        if(!$estado_atual) { throw new Exception("Chamado não encontrado."); }

        $antigo_id_status = $estado_atual['id_status'];
        $antigo_id_agente = $estado_atual['id_agente_atribuido'];
        $id_solicitante = $estado_atual['id_solicitante'];

        // Atualiza o ticket
        $id_agente_para_salvar = ($novo_id_agente > 0) ? $novo_id_agente : null;
        $sql_update_ticket = "UPDATE tickets SET id_status = ?, id_agente_atribuido = ? WHERE id = ?";
        $stmt_update = $conexao->prepare($sql_update_ticket);
        $stmt_update->bind_param("iii", $novo_id_status, $id_agente_para_salvar, $id_chamado);
        $stmt_update->execute();
        $stmt_update->close();
        
        // --- GERAÇÃO DE LOGS PARA O HISTÓRICO DE COMENTÁRIOS ---
        // Log de mudança de status
        if ($novo_id_status != $antigo_id_status) {
            $sql_nomes_status = "SELECT (SELECT nome FROM status_tickets WHERE id = ?) as nome_antigo, (SELECT nome FROM status_tickets WHERE id = ?) as nome_novo";
            $stmt_nomes = $conexao->prepare($sql_nomes_status);
            $stmt_nomes->bind_param("ii", $antigo_id_status, $novo_id_status);
            $stmt_nomes->execute();
            $nomes = $stmt_nomes->get_result()->fetch_assoc();
            $stmt_nomes->close();
            $log_comentarios[] = "Status alterado de '" . htmlspecialchars($nomes['nome_antigo']) . "' para '" . htmlspecialchars($nomes['nome_novo']) . "'.";
        }

        // Log de mudança de agente
        if ($id_agente_para_salvar != $antigo_id_agente) {
            $sql_nomes_agentes = "SELECT (SELECT nome_completo FROM usuarios WHERE id = ?) as nome_antigo, (SELECT nome_completo FROM usuarios WHERE id = ?) as nome_novo";
            $stmt_nomes_agentes = $conexao->prepare($sql_nomes_agentes);
            $stmt_nomes_agentes->bind_param("ii", $antigo_id_agente, $id_agente_para_salvar);
            $stmt_nomes_agentes->execute();
            $nomes_agentes = $stmt_nomes_agentes->get_result()->fetch_assoc();
            $stmt_nomes_agentes->close();
            $nome_antigo = $nomes_agentes['nome_antigo'] ?? 'Ninguém';
            $nome_novo = $nomes_agentes['nome_novo'] ?? 'Ninguém';
            $log_comentarios[] = "Chamado atribuído de '" . htmlspecialchars($nome_antigo) . "' para '" . htmlspecialchars($nome_novo) . "'.";
        }

        // --- INSERIR LOGS COMO COMENTÁRIOS E GERAR NOTIFICAÇÕES ---
        if (!empty($log_comentarios)) {
            $log_texto = "Ação realizada por " . htmlspecialchars($nome_agente_logado) . ":\n- " . implode("\n- ", $log_comentarios);
            
            // 1. Insere o log como um comentário INTERNO no histórico
            $sql_insert_log = "INSERT INTO comentarios_tickets (id_ticket, id_usuario, comentario, interno) VALUES (?, ?, ?, 1)"; // 1 = interno
            $stmt_log = $conexao->prepare($sql_insert_log);
            $stmt_log->bind_param("iis", $id_chamado, $id_agente_logado, $log_texto);
            $stmt_log->execute();
            $stmt_log->close();

            // 2. Cria uma notificação separada para o sino do solicitante (se o status mudou)
            if ($novo_id_status != $antigo_id_status) {
                $mensagem_notificacao = "O status do seu chamado #{$id_chamado} foi atualizado.";
                $sql_notif = "INSERT INTO notificacoes (id_usuario_destino, id_ticket, mensagem) VALUES (?, ?, ?)";
                $stmt_notif = $conexao->prepare($sql_notif);
                $stmt_notif->bind_param("iis", $id_solicitante, $id_chamado, $mensagem_notificacao);
                $stmt_notif->execute();
                $stmt_notif->close();
            }
        }
        
        $conexao->commit();
        $_SESSION['form_message_type'] = 'success';
        $_SESSION['form_message'] = "Chamado atualizado com sucesso!";

    } catch (Exception $e) {
        $conexao->rollback();
        $_SESSION['form_message_type'] = 'error';
        $_SESSION['form_message'] = "Erro ao atualizar o chamado.";
    }

    header("Location: detalhes_chamado.php?id=" . $id_chamado);
    exit();
}
?>
