<?php
// processa_abertura_chamado.php - VERSÃO FINAL E SIMPLIFICADA

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
        // --- LÓGICA DE BANCO DE DADOS ---
        $id_status_aberto = 1;
        $id_agente_para_salvar = ($id_agente_designado > 0) ? $id_agente_designado : null;

        $sql_ticket = "INSERT INTO tickets (id_solicitante, id_agente_atribuido, motivo_chamado, descricao_detalhada, id_categoria, id_prioridade, id_status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_ticket = $conexao->prepare($sql_ticket);
        $stmt_ticket->bind_param("isssiii", $id_solicitante, $id_agente_para_salvar, $motivo_chamado, $descricao_detalhada, $id_categoria, $id_prioridade, $id_status_aberto);
        $stmt_ticket->execute();
        $id_novo_ticket = $stmt_ticket->insert_id;
        $stmt_ticket->close();

        // Lógica de Anexos ...

        // Lógica para salvar a notificação no banco
        $destinatarios_ids = [];
        if ($id_agente_para_salvar) {
            $destinatarios_ids[] = $id_agente_para_salvar;
        } else {
            $result_ti = $conexao->query("SELECT id FROM usuarios WHERE tipo_usuario = 'ti' AND ativo = 1");
            while($row = $result_ti->fetch_assoc()) { $destinatarios_ids[] = $row['id']; }
        }

        if (!empty($destinatarios_ids)) {
            $mensagem_notificacao_app = "Novo chamado #" . $id_novo_ticket . " aberto por " . htmlspecialchars($nome_solicitante) . ".";
            $sql_nova_notif = $conexao->prepare("INSERT INTO notificacoes (id_usuario_destino, id_ticket, mensagem) VALUES (?, ?, ?)");
            foreach($destinatarios_ids as $id_dest) {
                $sql_nova_notif->bind_param("iis", $id_dest, $id_novo_ticket, $mensagem_notificacao_app);
                $sql_nova_notif->execute();
            }
            $sql_nova_notif->close();
        }

        // 1. PRIMEIRO, SALVA TUDO PERMANENTEMENTE NO BANCO
        $conexao->commit();

        // 2. DEPOIS, ENVIA AS NOTIFICAÇÕES EXTERNAS E OS SINAIS
        if (!empty($destinatarios_ids)) {
            // Envia a notificação Push (que independe da tela)
            enviar_notificacao_push($conexao, $destinatarios_ids, $id_novo_ticket, 'Novo Chamado Aberto: #' . $id_novo_ticket, htmlspecialchars($nome_solicitante) . " abriu um chamado sobre: " . htmlspecialchars($motivo_chamado), 'chamado-' . $id_novo_ticket);

            // ENVIA UM ÚNICO SINAL PARA O PAINEL DE TI
            enviar_para_topico('dashboard-ti', [ 'type' => 'refresh_dashboard' ]);
        }
        
        // 3. Responde ao colaborador que a operação foi um sucesso
        enviar_resposta(true, 'Chamado aberto com sucesso!', ['ticket_id' => $id_novo_ticket]);

    } catch (Exception $e) {
        $conexao->rollback();
        error_log("Erro ao abrir chamado: " . $e->getMessage());
        enviar_resposta(false, 'Ocorreu um erro no servidor.');
    }
    
} else {
    enviar_resposta(false, 'Método de requisição inválido.');
}
?>