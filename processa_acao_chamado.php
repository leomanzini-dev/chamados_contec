<?php
// processa_acao_chamado.php - VERSÃO FINAL COM E-MAIL E WEBSOCKET

session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

// Carrega os serviços necessários
require_once __DIR__ . '/includes/websocket_service.php';
require_once __DIR__ . '/includes/email_service.php';
require_once __DIR__ . '/includes/email_templates.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 'ti') {
    http_response_code(403);
    die("Acesso negado.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_chamado = filter_input(INPUT_POST, 'id_chamado', FILTER_VALIDATE_INT);
    $novo_id_status = filter_input(INPUT_POST, 'id_status', FILTER_VALIDATE_INT);
    $novo_id_agente = filter_input(INPUT_POST, 'id_agente', FILTER_VALIDATE_INT);

    if (!$id_chamado || !$novo_id_status) {
        die("Dados inválidos.");
    }

    $id_agente_logado = $_SESSION['usuario_id'];
    $nome_agente_logado = $_SESSION['usuario_nome'];

    $conexao->begin_transaction();

    try {
        // 1. BUSCA O ESTADO ATUAL DO CHAMADO E NOMES
        $sql_estado_atual = "
            SELECT 
                t.id_status, 
                t.id_agente_atribuido, 
                t.id_solicitante,
                t.motivo_chamado,
                s.nome AS nome_status_antigo,
                solicitante.nome_completo AS nome_solicitante,
                solicitante.email AS email_solicitante
            FROM tickets t
            JOIN status_tickets s ON t.id_status = s.id
            JOIN usuarios solicitante ON t.id_solicitante = solicitante.id
            WHERE t.id = ?
        ";
        $stmt_atual = $conexao->prepare($sql_estado_atual);
        $stmt_atual->bind_param("i", $id_chamado);
        $stmt_atual->execute();
        $estado_atual = $stmt_atual->get_result()->fetch_assoc();
        $stmt_atual->close();

        if (!$estado_atual) {
            throw new Exception("Chamado não encontrado.");
        }

        $antigo_id_status = $estado_atual['id_status'];
        $nome_antigo_status = $estado_atual['nome_status_antigo'];
        $antigo_id_agente = $estado_atual['id_agente_atribuido'];
        $id_solicitante = $estado_atual['id_solicitante'];

        $mudanca_de_status = ($novo_id_status != $antigo_id_status);
        $id_agente_para_salvar = ($novo_id_agente > 0) ? $novo_id_agente : null;
        $mudanca_de_agente = ($id_agente_para_salvar != $antigo_id_agente);

        // Se nada mudou, não faz nada.
        if (!$mudanca_de_status && !$mudanca_de_agente) {
            $_SESSION['mensagem_aviso'] = "Nenhuma alteração foi feita.";
            header("Location: detalhes_chamado.php?id=" . $id_chamado);
            exit();
        }

        // 2. ATUALIZA O TICKET
        $sql_update_ticket = "UPDATE tickets SET id_status = ?, id_agente_atribuido = ?, data_ultima_atualizacao = NOW() WHERE id = ?";
        $stmt_update = $conexao->prepare($sql_update_ticket);
        $stmt_update->bind_param("iii", $novo_id_status, $id_agente_para_salvar, $id_chamado);
        $stmt_update->execute();
        $stmt_update->close();

        // 3. GERA OS LOGS E DEFINE A LISTA DE DESTINATÁRIOS
        $logs_de_mudanca = [];
        $nome_novo_status = $nome_antigo_status;

        if ($mudanca_de_status) {
            $stmt_nomes = $conexao->prepare("SELECT nome FROM status_tickets WHERE id = ?");
            $stmt_nomes->bind_param("i", $novo_id_status);
            $stmt_nomes->execute();
            $nome_novo_status = $stmt_nomes->get_result()->fetch_assoc()['nome'];
            $stmt_nomes->close();
            $logs_de_mudanca[] = "Status alterado para '" . htmlspecialchars($nome_novo_status) . "'.";
        }

        if ($mudanca_de_agente) {
            $stmt_nomes_agentes = $conexao->prepare("SELECT nome_completo FROM usuarios WHERE id = ?");
            $stmt_nomes_agentes->bind_param("i", $id_agente_para_salvar);
            $stmt_nomes_agentes->execute();
            $nome_novo_agente = $stmt_nomes_agentes->get_result()->fetch_assoc()['nome_completo'] ?? 'Ninguém';
            $stmt_nomes_agentes->close();
            $logs_de_mudanca[] = "Chamado atribuído para '" . htmlspecialchars($nome_novo_agente) . "'.";
        }

        // 4. SALVA LOGS (comentário visível - interno = 0)
        $log_completo = "Ação realizada por " . htmlspecialchars($nome_agente_logado) . ": " . implode(' ', $logs_de_mudanca);
        $stmt_log = $conexao->prepare("INSERT INTO comentarios_tickets (id_ticket, id_usuario, comentario, interno) VALUES (?, ?, ?, ?)");
        $interno = 0;
        $stmt_log->bind_param("iisi", $id_chamado, $id_agente_logado, $log_completo, $interno);
        $stmt_log->execute();
        $stmt_log->close();

        // 5. SALVA NOTIFICAÇÃO PARA O SOLICITANTE
        if ($id_solicitante != $id_agente_logado) {
            $mensagem_notificacao_db = "O chamado #{$id_chamado} que você abriu foi atualizado.";
            $sql_notif = $conexao->prepare("INSERT INTO notificacoes (id_usuario_destino, id_ticket, mensagem) VALUES (?, ?, ?)");
            $sql_notif->bind_param("iis", $id_solicitante, $id_chamado, $mensagem_notificacao_db);
            $sql_notif->execute();
            $sql_notif->close();
        }

        $_SESSION['mensagem_sucesso'] = implode(' ', $logs_de_mudanca);

        // 6. COMMIT
        $conexao->commit();

        // 7. ENVIA E-MAIL SE MUDOU O STATUS
        if ($mudanca_de_status && $id_solicitante != $id_agente_logado) {
            $corpo_email = criar_corpo_email_mudanca_status(
                $estado_atual['nome_solicitante'],
                $id_chamado,
                $estado_atual['motivo_chamado'],
                $nome_antigo_status,
                $nome_novo_status
            );

            enviar_notificacao_email(
                $estado_atual['email_solicitante'],
                $estado_atual['nome_solicitante'],
                "Status do seu Chamado #{$id_chamado} foi atualizado para '{$nome_novo_status}'",
                $corpo_email
            );
        }

        // 8. ENVIA WEBSOCKET
        enviar_para_usuario($id_solicitante, ['type' => 'refresh_dashboard']);
        if ($id_agente_para_salvar && $id_agente_para_salvar != $id_agente_logado) {
            enviar_para_usuario($id_agente_para_salvar, ['type' => 'refresh_dashboard']);
        }
        enviar_para_topico('dashboard-ti', ['type' => 'refresh_dashboard']);

        $sql_dados_ws = "
            SELECT 
                t.data_ultima_atualizacao, 
                agente.nome_completo AS nome_agente, 
                s.nome AS nome_status 
            FROM tickets t 
            LEFT JOIN usuarios agente ON t.id_agente_atribuido = agente.id 
            JOIN status_tickets s ON t.id_status = s.id 
            WHERE t.id = ?
        ";
        $stmt_ws = $conexao->prepare($sql_dados_ws);
        $stmt_ws->bind_param("i", $id_chamado);
        $stmt_ws->execute();
        $dados_para_ws = $stmt_ws->get_result()->fetch_assoc();
        $stmt_ws->close();

        if ($dados_para_ws) {
            enviar_para_topico("chamado-{$id_chamado}", [
                'type' => 'update_ticket_details',
                'payload' => $dados_para_ws
            ]);
        }

    } catch (Exception $e) {
        $conexao->rollback();
        error_log("Erro ao atualizar chamado: " . $e->getMessage());
        $_SESSION['mensagem_erro'] = "Erro ao atualizar o chamado.";
    }

    header("Location: detalhes_chamado.php?id=" . $id_chamado);
    exit();
}
