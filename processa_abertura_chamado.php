<?php
// processa_abertura_chamado.php (VERSÃO FINAL E CORRIGIDA)

session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';
require_once PROJECT_ROOT_PATH . '/includes/websocket_service.php';
require_once PROJECT_ROOT_PATH . '/includes/email_service.php';
require_once PROJECT_ROOT_PATH . '/includes/email_templates.php';

header('Content-Type: application/json');

function enviar_resposta($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (!isset($_SESSION['usuario_id'])) {
        enviar_resposta(false, 'Sessão inválida. Por favor, faça login novamente.');
    }

    // --- CORREÇÃO: Apenas os campos do formulário de abertura são lidos ---
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

        $sql_count = "SELECT COUNT(id) as total FROM tickets WHERE id_solicitante = ?";
        $stmt_count = $conexao->prepare($sql_count);
        $stmt_count->bind_param("i", $id_solicitante);
        $stmt_count->execute();
        $total_chamados_usuario = $stmt_count->get_result()->fetch_assoc()['total'];
        $stmt_count->close();
        
        $sql_update_seq = "UPDATE tickets SET id_chamado_usuario = ? WHERE id = ?";
        $stmt_update_seq = $conexao->prepare($sql_update_seq);
        $stmt_update_seq->bind_param("ii", $total_chamados_usuario, $id_novo_ticket);
        $stmt_update_seq->execute();
        $stmt_update_seq->close();

        if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
            $pasta_uploads = PROJECT_ROOT_PATH . '/uploads/';
            if (!is_dir($pasta_uploads)) { mkdir($pasta_uploads, 0777, true); }
            
            $sql_anexo = "INSERT INTO anexos_tickets (id_ticket, id_comentario, caminho_arquivo, nome_arquivo_armazenado, nome_arquivo_original, tamanho_bytes) VALUES (?, NULL, ?, ?, ?, ?)";
            $stmt_anexo = $conexao->prepare($sql_anexo);

            foreach ($_FILES['anexos']['name'] as $key => $nome_original) {
                if ($_FILES['anexos']['error'][$key] === UPLOAD_ERR_OK) {
                    $nome_tmp = $_FILES['anexos']['tmp_name'][$key];
                    $tamanho_bytes = $_FILES['anexos']['size'][$key];
                    $nome_unico = uniqid('chamado' . $id_novo_ticket . '_', true) . '-' . basename($nome_original);
                    $caminho_final = $pasta_uploads . $nome_unico;
                    
                    if (move_uploaded_file($nome_tmp, $caminho_final)) {
                        $caminho_relativo = 'uploads/' . $nome_unico;
                        $stmt_anexo->bind_param("isssi", $id_novo_ticket, $caminho_relativo, $nome_unico, $nome_original, $tamanho_bytes);
                        $stmt_anexo->execute();
                    }
                }
            }
            $stmt_anexo->close();
        }
        
        $conexao->commit();

        $destinatarios_info = [];
        if ($id_agente_para_salvar) {
            $stmt_agente = $conexao->prepare("SELECT nome_completo AS nome, email FROM usuarios WHERE id = ? AND ativo = 1");
            $stmt_agente->bind_param("i", $id_agente_para_salvar);
            $stmt_agente->execute();
            $result_agente = $stmt_agente->get_result();
            if ($result_agente->num_rows > 0) {
                $destinatarios_info = $result_agente->fetch_all(MYSQLI_ASSOC);
            }
            $stmt_agente->close();
        } else {
            $result_ti = $conexao->query("SELECT nome_completo AS nome, email FROM usuarios WHERE tipo_usuario = 'ti' AND ativo = 1");
            if ($result_ti->num_rows > 0) {
                $destinatarios_info = $result_ti->fetch_all(MYSQLI_ASSOC);
            }
        }
        
        if (!empty($destinatarios_info)) {
            foreach ($destinatarios_info as $destinatario) {
                $corpo_email = criar_corpo_email_novo_chamado(
                    $destinatario['nome'],
                    $id_novo_ticket,
                    $motivo_chamado,
                    $nome_solicitante
                );
                
                enviar_notificacao_email(
                    $destinatario['email'],
                    $destinatario['nome'],
                    "Novo Chamado Aberto [#{$id_novo_ticket}]: " . htmlspecialchars($motivo_chamado),
                    $corpo_email
                );
            }
        }
        
        enviar_para_topico('dashboard-ti', ['type' => 'refresh_dashboard']);
        
        enviar_resposta(true, 'Chamado aberto com sucesso!', ['ticket_id' => $total_chamados_usuario]);

    } catch (Exception $e) {
        $conexao->rollback();
        error_log("Erro ao abrir chamado: " . $e->getMessage());
        enviar_resposta(false, 'Ocorreu um erro no servidor. Verifique o log para detalhes.');
    }
    
} else {
    enviar_resposta(false, 'Método de requisição inválido.');
}