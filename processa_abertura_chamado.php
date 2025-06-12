<?php
// processa_abertura_chamado.php
session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

// Define o cabeçalho para retornar JSON
header('Content-Type: application/json');

// Função para enviar resposta e terminar o script
function enviar_resposta($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validação básica de login
    if (!isset($_SESSION['usuario_id'])) {
        enviar_resposta(false, 'Sessão inválida. Por favor, faça login novamente.');
    }

    // Coleta e validação dos dados do formulário
    $id_solicitante = $_SESSION['usuario_id'];
    $nome_solicitante = $_SESSION['usuario_nome']; // Pega o nome para a mensagem
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

        // (Aqui entraria sua lógica de upload de anexos, se necessário)
        
        // --- LÓGICA DE NOTIFICAÇÃO ADICIONADA ---
        $destinatarios_notif = [];
        $mensagem_notificacao = "Novo chamado #" . $id_novo_ticket . " aberto por " . htmlspecialchars($nome_solicitante) . ".";
        
        // Se um agente específico foi escolhido, busca o ID dele
        if ($id_agente_para_salvar) {
            $sql_agente = "SELECT id FROM usuarios WHERE id = ? AND tipo_usuario = 'ti' AND ativo = 1";
            $stmt = $conexao->prepare($sql_agente);
            $stmt->bind_param("i", $id_agente_para_salvar);
            $stmt->execute();
            $destinatarios_notif = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else { // Se não, notifica toda a equipe de TI
            $sql_ti = "SELECT id FROM usuarios WHERE tipo_usuario = 'ti' AND ativo = 1";
            $destinatarios_notif = $conexao->query($sql_ti)->fetch_all(MYSQLI_ASSOC);
        }

        // Insere a notificação para cada destinatário encontrado
        if (!empty($destinatarios_notif)) {
            $sql_nova_notif = "INSERT INTO notificacoes (id_usuario_destino, id_ticket, mensagem) VALUES (?, ?, ?)";
            $stmt_nova_notif = $conexao->prepare($sql_nova_notif);
            foreach ($destinatarios_notif as $destinatario) {
                $stmt_nova_notif->bind_param("iis", $destinatario['id'], $id_novo_ticket, $mensagem_notificacao);
                $stmt_nova_notif->execute();
            }
            $stmt_nova_notif->close();
        }
        // --- FIM DA LÓGICA DE NOTIFICAÇÃO ---

        $conexao->commit();

        // Envia uma resposta de sucesso com o ID do novo ticket
        enviar_resposta(true, 'Chamado aberto com sucesso!', ['ticket_id' => $id_novo_ticket]);

    } catch (Exception $e) {
        $conexao->rollback();
        // Em produção, seria ideal logar o erro: error_log($e->getMessage());
        enviar_resposta(false, 'Ocorreu um erro no servidor ao abrir seu chamado.');
    }
} else {
    enviar_resposta(false, 'Método de requisição inválido.');
}
?>
