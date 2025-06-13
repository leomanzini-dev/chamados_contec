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

        // --- LÓGICA DE UPLOAD DE ANEXOS ---
        if (isset($_FILES['anexos']) && count($_FILES['anexos']['name']) > 0 && $_FILES['anexos']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            
            $pasta_uploads = PROJECT_ROOT_PATH . '/uploads/';
            if (!is_dir($pasta_uploads)) {
                mkdir($pasta_uploads, 0777, true);
            }

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

        // --- LÓGICA DE NOTIFICAÇÃO ---
        $destinatarios_notif = [];
        $mensagem_notificacao = "Novo chamado #" . $id_novo_ticket . " aberto por " . htmlspecialchars($nome_solicitante) . ".";
        
        if ($id_agente_para_salvar) {
            $sql_dest = "SELECT id FROM usuarios WHERE id = ? AND tipo_usuario = 'ti' AND ativo = 1";
            $stmt = $conexao->prepare($sql_dest);
            $stmt->bind_param("i", $id_agente_para_salvar);
        } else {
            $sql_dest = "SELECT id FROM usuarios WHERE tipo_usuario = 'ti' AND ativo = 1";
            $stmt = $conexao->prepare($sql_dest);
        }
        $stmt->execute();
        $destinatarios_notif = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (!empty($destinatarios_notif)) {
            $sql_nova_notif = "INSERT INTO notificacoes (id_usuario_destino, id_ticket, mensagem) VALUES (?, ?, ?)";
            $stmt_nova_notif = $conexao->prepare($sql_nova_notif);
            foreach ($destinatarios_notif as $destinatario) {
                $stmt_nova_notif->bind_param("iis", $destinatario['id'], $id_novo_ticket, $mensagem_notificacao);
                $stmt_nova_notif->execute();
            }
            $stmt_nova_notif->close();
        }

        $conexao->commit();
        enviar_resposta(true, 'Chamado aberto com sucesso!', ['ticket_id' => $id_novo_ticket]);

    } catch (Exception $e) {
        $conexao->rollback();
        error_log("Erro ao abrir chamado: " . $e->getMessage());
        enviar_resposta(false, 'Ocorreu um erro no servidor ao abrir seu chamado.');
    }
} else {
    enviar_resposta(false, 'Método de requisição inválido.');
}
?>
