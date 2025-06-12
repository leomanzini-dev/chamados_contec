<?php
// processa_comentario.php
session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403); die("Acesso não autorizado.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_chamado = filter_input(INPUT_POST, 'id_chamado', FILTER_VALIDATE_INT);
    $comentario_texto = trim($_POST['comentario']);
    $id_usuario = $_SESSION['usuario_id'];
    $tipo_usuario = $_SESSION['usuario_tipo'];
    $eh_interno = ($tipo_usuario == 'ti' && isset($_POST['comentario_interno']) && $_POST['comentario_interno'] == '1') ? 1 : 0;

    if (!$id_chamado || empty($comentario_texto)) { die("Dados inválidos."); }

    // Busca dados do ticket para permissão e notificação
    $sql_perm = "SELECT id_solicitante, id_agente_atribuido FROM tickets WHERE id = ? LIMIT 1";
    $stmt_perm = $conexao->prepare($sql_perm);
    $stmt_perm->bind_param("i", $id_chamado);
    $stmt_perm->execute();
    $ticket_perm = $stmt_perm->get_result()->fetch_assoc();
    $stmt_perm->close();

    if (!$ticket_perm) { die("Chamado não encontrado."); }
    if ($tipo_usuario != 'ti' && $ticket_perm['id_solicitante'] != $id_usuario) { die("Sem permissão."); }

    // Insere o comentário
    $sql_insert = "INSERT INTO comentarios_tickets (id_ticket, id_usuario, comentario, interno) VALUES (?, ?, ?, ?)";
    $stmt_insert = $conexao->prepare($sql_insert);
    $stmt_insert->bind_param("iisi", $id_chamado, $id_usuario, $comentario_texto, $eh_interno);
    $stmt_insert->execute();
    $stmt_insert->close();

    // --- LÓGICA DE NOTIFICAÇÃO QUANDO HÁ UM NOVO COMENTÁRIO ---
    if ($eh_interno == 0) {
        $id_solicitante_do_ticket = $ticket_perm['id_solicitante'];
        $mensagem_notificacao = "";
        $destinatarios = [];

        // Cenário 1: TI comentou, notifica o colaborador
        if ($tipo_usuario == 'ti' && $id_usuario != $id_solicitante_do_ticket) {
            $mensagem_notificacao = "A equipe de TI comentou no seu chamado #" . $id_chamado . ".";
            $sql_dest = "SELECT id FROM usuarios WHERE id = ? AND ativo = 1";
            $stmt_dest = $conexao->prepare($sql_dest);
            $stmt_dest->bind_param("i", $id_solicitante_do_ticket);
            $stmt_dest->execute();
            $destinatarios = $stmt_dest->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_dest->close();
        } 
        // Cenário 2: Colaborador comentou, notifica a TI
        else if ($tipo_usuario == 'colaborador') {
            $id_agente_atribuido = $ticket_perm['id_agente_atribuido'];
            
            // Se tiver um agente específico, notifica só ele
            if (!empty($id_agente_atribuido)) {
                $mensagem_notificacao = "O solicitante comentou no chamado #" . $id_chamado . ".";
                $sql_dest = "SELECT id FROM usuarios WHERE id = ? AND ativo = 1";
                $stmt_dest = $conexao->prepare($sql_dest);
                $stmt_dest->bind_param("i", $id_agente_atribuido);
            } else { // Se não tiver agente, notifica toda a equipe de TI
                $mensagem_notificacao = "Um colaborador comentou no chamado #" . $id_chamado . " (sem atribuição).";
                $sql_dest = "SELECT id FROM usuarios WHERE tipo_usuario = 'ti' AND ativo = 1";
                $stmt_dest = $conexao->prepare($sql_dest);
            }
            $stmt_dest->execute();
            $destinatarios = $stmt_dest->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_dest->close();
        }

        if (!empty($destinatarios)) {
            $sql_nova_notif = "INSERT INTO notificacoes (id_usuario_destino, id_ticket, mensagem) VALUES (?, ?, ?)";
            $stmt_nova_notif = $conexao->prepare($sql_nova_notif);
            foreach ($destinatarios as $destinatario) {
                $stmt_nova_notif->bind_param("iis", $destinatario['id'], $id_chamado, $mensagem_notificacao);
                $stmt_nova_notif->execute();
            }
            $stmt_nova_notif->close();
        }
    }

    // Atualiza a data do ticket e redireciona
    $sql_update = "UPDATE tickets SET data_ultima_atualizacao = NOW() WHERE id = ?";
    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->bind_param("i", $id_chamado);
    $stmt_update->execute();
    $stmt_update->close();
    header("Location: detalhes_chamado.php?id=" . $id_chamado);
    exit();
}
?>
