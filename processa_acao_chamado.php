<?php
// processa_acao_chamado.php - VERSÃO FINAL COM NOTIFICAÇÕES COMPLETAS E PERSONALIZADAS

session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';
require_once __DIR__ . '/includes/websocket_service.php';
require_once __DIR__ . '/includes/push_service.php';

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
    
    $conexao->begin_transaction();

    try {
        // 1. BUSCA O ESTADO ATUAL DO CHAMADO
        $stmt_atual = $conexao->prepare("SELECT id_status, id_agente_atribuido, id_solicitante FROM tickets WHERE id = ?");
        $stmt_atual->bind_param("i", $id_chamado);
        $stmt_atual->execute();
        $estado_atual = $stmt_atual->get_result()->fetch_assoc();
        $stmt_atual->close();

        if(!$estado_atual) { throw new Exception("Chamado não encontrado."); }

        $antigo_id_status = $estado_atual['id_status'];
        $antigo_id_agente = $estado_atual['id_agente_atribuido'];
        $id_solicitante = $estado_atual['id_solicitante'];

        // 2. ATUALIZA O TICKET
        $id_agente_para_salvar = ($novo_id_agente > 0) ? $novo_id_agente : null;
        $sql_update_ticket = "UPDATE tickets SET id_status = ?, id_agente_atribuido = ?, data_ultima_atualizacao = NOW() WHERE id = ?";
        $stmt_update = $conexao->prepare($sql_update_ticket);
        $stmt_update->bind_param("iii", $novo_id_status, $id_agente_para_salvar, $id_chamado);
        $stmt_update->execute();
        $stmt_update->close();
        
        // 3. GERA OS LOGS E DEFINE A LISTA DE DESTINATÁRIOS
        $logs_de_mudanca = [];
        $destinatarios_ids = [];

        if ($id_solicitante != $id_agente_logado) {
            $destinatarios_ids[] = $id_solicitante;
        }

        if ($novo_id_status != $antigo_id_status) {
            $stmt_nomes = $conexao->prepare("SELECT (SELECT nome FROM status_tickets WHERE id = ?) as nome_novo");
            $stmt_nomes->bind_param("i", $novo_id_status);
            $stmt_nomes->execute();
            $nome_novo_status = $stmt_nomes->get_result()->fetch_assoc()['nome_novo'];
            $stmt_nomes->close();
            $logs_de_mudanca[] = "Status alterado para '" . htmlspecialchars($nome_novo_status) . "'.";
        }

        if ($id_agente_para_salvar != $antigo_id_agente) {
            if ($id_agente_para_salvar && $id_agente_para_salvar != $id_agente_logado) {
                $destinatarios_ids[] = $id_agente_para_salvar;
            }
            $stmt_nomes_agentes = $conexao->prepare("SELECT nome_completo FROM usuarios WHERE id = ?");
            $stmt_nomes_agentes->bind_param("i", $id_agente_para_salvar);
            $stmt_nomes_agentes->execute();
            $nome_novo_agente = $stmt_nomes_agentes->get_result()->fetch_assoc()['nome_completo'] ?? 'Ninguém';
            $stmt_nomes_agentes->close();
            $logs_de_mudanca[] = "Chamado atribuído para '" . htmlspecialchars($nome_novo_agente) . "'.";
        }

        // 4. SE HOUVE MUDANÇAS, SALVA TUDO NO BANCO
        if (!empty($logs_de_mudanca)) {
            $log_completo = "Ação realizada por " . htmlspecialchars($nome_agente_logado) . ": " . implode(' ', $logs_de_mudanca);
            
            // Salva log como comentário interno
            $stmt_log = $conexao->prepare("INSERT INTO comentarios_tickets (id_ticket, id_usuario, comentario, interno) VALUES (?, ?, ?, 1)");
            $stmt_log->bind_param("iis", $id_chamado, $id_agente_logado, $log_completo);
            $stmt_log->execute();
            $stmt_log->close();

            // Salva notificação no banco para cada destinatário
            $destinatarios_ids = array_unique($destinatarios_ids);
            if (!empty($destinatarios_ids)) {
                $mensagem_notificacao_db = "O chamado #{$id_chamado} que você segue foi atualizado.";
                $sql_notif = $conexao->prepare("INSERT INTO notificacoes (id_usuario_destino, id_ticket, mensagem) VALUES (?, ?, ?)");
                foreach($destinatarios_ids as $id_dest) {
                    $sql_notif->bind_param("iis", $id_dest, $id_chamado, $mensagem_notificacao_db);
                    $sql_notif->execute();
                }
                $sql_notif->close();
            }
            // Define a mensagem de sucesso para o Toast
            $_SESSION['mensagem_sucesso'] = implode(' ', $logs_de_mudanca);
        } else {
            $_SESSION['mensagem_aviso'] = "Nenhuma alteração foi feita.";
        }

        // 5. CONFIRMA NO BANCO E ENVIA NOTIFICAÇÕES EM TEMPO REAL
        $conexao->commit();

        if (!empty($logs_de_mudanca)) {
            // Mensagem Personalizada para o Push
            $corpo_push = "Seu chamado #{$id_chamado} foi atualizado: " . implode(' ', $logs_de_mudanca);
            enviar_notificacao_push($conexao, $destinatarios_ids, $id_chamado, "Seu Chamado Foi Atualizado!", $corpo_push, 'chamado-' . $id_chamado);

            // Envia um sinal para o painel de todos os envolvidos se atualizar
            foreach($destinatarios_ids as $id_dest) {
                enviar_para_usuario($id_dest, ['type' => 'refresh_dashboard']);
            }
            // Também atualiza o painel de TI
            enviar_para_topico('dashboard-ti', ['type' => 'refresh_dashboard']);

            // Envia atualização para a página de detalhes para todos que estiverem vendo
            $sql_dados_ws = "SELECT t.data_ultima_atualizacao, agente.nome_completo AS nome_agente, s.nome AS nome_status FROM tickets t LEFT JOIN usuarios agente ON t.id_agente_atribuido = agente.id JOIN status_tickets s ON t.id_status = s.id WHERE t.id = ?";
            $stmt_ws = $conexao->prepare($sql_dados_ws);
            $stmt_ws->bind_param("i", $id_chamado);
            $stmt_ws->execute();
            $dados_para_ws = $stmt_ws->get_result()->fetch_assoc();
            $stmt_ws->close();
            if ($dados_para_ws) {
                enviar_para_topico("chamado-{$id_chamado}", ['type' => 'update_ticket_details', 'payload' => $dados_para_ws]);
            }
        }

    } catch (Exception $e) {
        $conexao->rollback();
        error_log("Erro ao atualizar chamado: " . $e->getMessage());
        $_SESSION['mensagem_erro'] = "Erro ao atualizar o chamado.";
    }

    header("Location: detalhes_chamado.php?id=" . $id_chamado);
    exit();
}
?>