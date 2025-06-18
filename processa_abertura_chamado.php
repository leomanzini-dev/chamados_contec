<?php
// processa_abertura_chamado.php - VERSÃO CORRIGIDA E COMPLETA

session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';
require_once __DIR__ . '/includes/push_service.php';
require_once __DIR__ . '/includes/websocket_service.php';

header('Content-Type: application/json');

function enviar_resposta($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (!isset($_SESSION['usuario_id'])) {
        enviar_resposta(false, 'Sessão inválida. Por favor, faça login novamente.');
    }

    $id_solicitante = $_SESSION['usuario_id'];
    $nome_solicitante = $_SESSION['usuario_nome'];
    $motivo_chamado = trim($_POST['motivo_chamado']);
    $descricao_detalhada = trim($_POST['descricao_detalhada']);
    $id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
    $id_prioridade = filter_input(INPUT_POST, 'id_prioridade', FILTER_VALIDATE_INT);
    $id_agente_designado = filter_input(INPUT_POST, 'id_agente_designado', FILTER_VALIDATE_INT);

    if (empty($motivo_chamado) || empty($descricao_detalhada) || empty($id_categoria) || empty($id_prioridade)) {
        enviar_resposta(false, 'Todos os campos obrigatórios devem ser preenchidos.');
    }
    
    $conexao->begin_transaction();

    try {
        $id_status_aberto = 1;
        $id_agente_para_salvar = ($id_agente_designado > 0) ? $id_agente_designado : null;

        $sql_ticket = "INSERT INTO tickets (id_solicitante, id_agente_atribuido, motivo_chamado, descricao_detalhada, id_categoria, id_prioridade, id_status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_ticket = $conexao->prepare($sql_ticket);
        $stmt_ticket->bind_param("isssiii", $id_solicitante, $id_agente_para_salvar, $motivo_chamado, $descricao_detalhada, $id_categoria, $id_prioridade, $id_status_aberto);
        $stmt_ticket->execute();
        $id_novo_ticket = $stmt_ticket->insert_id;
        $stmt_ticket->close();

        if (isset($_FILES['anexos']) && count($_FILES['anexos']['name']) > 0 && $_FILES['anexos']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $pasta_uploads = PROJECT_ROOT_PATH . '/uploads/';
            if (!is_dir($pasta_uploads)) { mkdir($pasta_uploads, 0777, true); }
            $sql_anexo = "INSERT INTO anexos_tickets (id_ticket, caminho_arquivo, nome_arquivo_original, tamanho_bytes) VALUES (?, ?, ?, ?)";
            $stmt_anexo = $conexao->prepare($sql_anexo);
            foreach ($_FILES['anexos']['name'] as $key => $nome_original) {
                if ($_FILES['anexos']['error'][$key] === UPLOAD_ERR_OK) {
                    $nome_tmp = $_FILES['anexos']['tmp_name'][$key];
                    $tamanho_bytes = $_FILES['anexos']['size'][$key];
                    $nome_unico = uniqid('chamado' . $id_novo_ticket . '_', true) . '-' . basename($nome_original);
                    $caminho_final = $pasta_uploads . $nome_unico;
                    if (move_uploaded_file($nome_tmp, $caminho_final)) {
                        $caminho_relativo = 'uploads/' . $nome_unico;
                        $stmt_anexo->bind_param("issi", $id_novo_ticket, $caminho_relativo, $nome_original, $tamanho_bytes);
                        $stmt_anexo->execute();
                    }
                }
            }
            $stmt_anexo->close();
        }

        $destinatarios_ids = [];
        $mensagem_notificacao_app = "Novo chamado #" . $id_novo_ticket . " aberto por " . htmlspecialchars($nome_solicitante) . ".";
        
        if ($id_agente_para_salvar) {
            $destinatarios_ids[] = $id_agente_para_salvar;
        } else {
            $result_ti = $conexao->query("SELECT id FROM usuarios WHERE tipo_usuario = 'ti' AND ativo = 1");
            while($row = $result_ti->fetch_assoc()) { $destinatarios_ids[] = $row['id']; }
        }

        if (!empty($destinatarios_ids)) {
            $sql_nova_notif = $conexao->prepare("INSERT INTO notificacoes (id_usuario_destino, id_ticket, mensagem) VALUES (?, ?, ?)");
            foreach($destinatarios_ids as $id_dest) {
                $sql_nova_notif->bind_param("iis", $id_dest, $id_novo_ticket, $mensagem_notificacao_app);
                $sql_nova_notif->execute();
            }
            $sql_nova_notif->close();
            
            enviar_notificacao_push(
                $conexao, $destinatarios_ids, $id_novo_ticket,
                'Novo Chamado Aberto: #' . $id_novo_ticket,
                htmlspecialchars($nome_solicitante) . " abriu um chamado sobre: " . htmlspecialchars($motivo_chamado),
                'novo-chamado'
            );

            foreach($destinatarios_ids as $id_dest) {
                enviar_para_usuario($id_dest, [ 'type' => 'global_notification', 'message' => $mensagem_notificacao_app ]);
            }
            
            $payload_novo_chamado = [ 'type' => 'dashboard_new_ticket', 'payload' => [
                    'id' => $id_novo_ticket, 'motivo_chamado' => $motivo_chamado, 'nome_solicitante' => $nome_solicitante
                ]
            ];
            enviar_para_topico('dashboard-ti', $payload_novo_chamado);

            $stats_ti_result = $conexao->query("SELECT (SELECT COUNT(id) FROM tickets WHERE id_status = 1) AS abertos FROM DUAL")->fetch_assoc();
            $payload_stats = [ 'type' => 'update_dashboard_stats', 'payload' => $stats_ti_result ];
            enviar_para_topico('dashboard-ti', $payload_stats);
        }

        // ===== SEÇÃO RESTAURADA (ESSENCIAL) =====
        $conexao->commit();
        enviar_resposta(true, 'Chamado aberto com sucesso!', ['ticket_id' => $id_novo_ticket]);

    } catch (Exception $e) {
        $conexao->rollback();
        error_log("Erro ao abrir chamado: " . $e->getMessage());
        enviar_resposta(false, 'Ocorreu um erro no servidor.');
    }
    // ===== FIM DA SEÇÃO RESTAURADA =====

} else {
    enviar_resposta(false, 'Método de requisição inválido.');
}
?>