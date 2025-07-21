<?php
// processa_comentario.php (VERSÃO 100% COMPLETA E FINAL DE PRODUÇÃO)

session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/includes/websocket_service.php';
require_once __DIR__ . '/includes/email_service.php';
require_once __DIR__ . '/includes/email_templates.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: painel.php");
    exit();
}

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['mensagem_erro'] = "Acesso não autorizado. Por favor, faça login.";
    header("Location: login.php");
    exit();
}

$id_chamado = filter_input(INPUT_POST, 'id_chamado', FILTER_VALIDATE_INT);
$comentario_texto = trim($_POST['comentario'] ?? '');
$id_usuario_comentou = $_SESSION['usuario_id'];
$nome_usuario_comentou = $_SESSION['usuario_nome'];
$tipo_usuario = $_SESSION['usuario_tipo'];
$eh_interno = ($tipo_usuario == 'ti' && isset($_POST['comentario_interno'])) ? 1 : 0;

if (!$id_chamado || (empty($comentario_texto) && empty($_FILES['anexos']['name'][0]))) {
    $_SESSION['mensagem_erro'] = "Você precisa escrever um comentário ou anexar um arquivo.";
    header("Location: detalhes_chamado.php?id=" . $id_chamado);
    exit();
}

try {
    $sql_perm = "SELECT id_solicitante, id_agente_atribuido, motivo_chamado FROM tickets WHERE id = ? LIMIT 1";
    $stmt_perm = $conexao->prepare($sql_perm);
    $stmt_perm->bind_param("i", $id_chamado);
    $stmt_perm->execute();
    $ticket_info = $stmt_perm->get_result()->fetch_assoc();
    $stmt_perm->close();

    if (!$ticket_info) { throw new Exception("Chamado não encontrado."); }
    if ($tipo_usuario != 'ti' && $ticket_info['id_solicitante'] != $id_usuario_comentou) { throw new Exception("Sem permissão para comentar."); }

    $conexao->begin_transaction();

    $sql_insert = "INSERT INTO comentarios_tickets (id_ticket, id_usuario, comentario, interno, data_comentario) VALUES (?, ?, ?, ?, NOW())";
    $stmt_insert = $conexao->prepare($sql_insert);
    $stmt_insert->bind_param("iisi", $id_chamado, $id_usuario_comentou, $comentario_texto, $eh_interno);
    $stmt_insert->execute();
    $id_novo_comentario = $stmt_insert->insert_id;
    $stmt_insert->close();

    $anexos_processados = 0;
    if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
        $pasta_uploads = PROJECT_ROOT_PATH . '/uploads/comentarios/';
        if (!is_dir($pasta_uploads)) { mkdir($pasta_uploads, 0775, true); }

        $sql_anexo = "INSERT INTO anexos_tickets (id_ticket, id_comentario, nome_arquivo_original, nome_arquivo_armazenado, caminho_arquivo, tamanho_bytes, tipo_mime, data_upload) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt_anexo = $conexao->prepare($sql_anexo);

        foreach ($_FILES['anexos']['name'] as $key => $nome_original) {
            if ($_FILES['anexos']['error'][$key] === UPLOAD_ERR_OK) {
                $nome_tmp = $_FILES['anexos']['tmp_name'][$key];
                $tamanho_bytes = $_FILES['anexos']['size'][$key];
                $tipo_mime = mime_content_type($nome_tmp);
                $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
                $nome_arquivo_armazenado = 'comentario_' . $id_novo_comentario . '_' . uniqid() . '.' . $extensao;
                $caminho_final = $pasta_uploads . $nome_arquivo_armazenado;

                if (move_uploaded_file($nome_tmp, $caminho_final)) {
                    $caminho_relativo_db = 'uploads/comentarios/' . $nome_arquivo_armazenado;
                    $stmt_anexo->bind_param("iisssis", $id_chamado, $id_novo_comentario, $nome_original, $nome_arquivo_armazenado, $caminho_relativo_db, $tamanho_bytes, $tipo_mime);
                    $stmt_anexo->execute();
                    $anexos_processados++;
                }
            }
        }
        $stmt_anexo->close();
    }

    $sql_update = "UPDATE tickets SET data_ultima_atualizacao = NOW() WHERE id = ?";
    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->bind_param("i", $id_chamado);
    $stmt_update->execute();
    $stmt_update->close();
    
    // =========================================================================
    // INÍCIO DA LÓGICA DE NOTIFICAÇÕES (PÚBLICAS E INTERNAS)
    // =========================================================================

    if ($eh_interno == 0) { // LÓGICA PARA COMENTÁRIOS PÚBLICOS
        $destinatarios_ids = [];
        if ($tipo_usuario == 'ti') {
            $destinatarios_ids[] = $ticket_info['id_solicitante'];
            $mensagem_notificacao_app = "A equipe de TI comentou no seu chamado #" . $id_chamado . ".";
        } else {
            if (!empty($ticket_info['id_agente_atribuido'])) {
                $destinatarios_ids[] = $ticket_info['id_agente_atribuido'];
            } else {
                $sql_ti = "SELECT id FROM usuarios WHERE tipo_usuario = 'ti' AND ativo = 1";
                $result_ti = $conexao->query($sql_ti);
                while ($row = $result_ti->fetch_assoc()) { $destinatarios_ids[] = $row['id']; }
            }
            $mensagem_notificacao_app = "O solicitante comentou no chamado #" . $id_chamado . ".";
        }

        $destinatarios_ids = array_filter($destinatarios_ids, fn($id) => $id != $id_usuario_comentou);
        $destinatarios_ids = array_unique($destinatarios_ids);

        if (!empty($destinatarios_ids)) {
            // Insere notificações no App
            $sql_nova_notif = "INSERT INTO notificacoes (id_usuario_destino, id_ticket, mensagem) VALUES (?, ?, ?)";
            $stmt_nova_notif = $conexao->prepare($sql_nova_notif);
            foreach ($destinatarios_ids as $id_destinatario) {
                $stmt_nova_notif->bind_param("iis", $id_destinatario, $id_chamado, $mensagem_notificacao_app);
                $stmt_nova_notif->execute();
            }
            $stmt_nova_notif->close();
            
            // Busca e-mails para enviar
            $placeholders = implode(',', array_fill(0, count($destinatarios_ids), '?'));
            $types = str_repeat('i', count($destinatarios_ids));
            $sql_users = "SELECT nome_completo AS nome, email FROM usuarios WHERE id IN ($placeholders)";
            $stmt_users = $conexao->prepare($sql_users);
            $stmt_users->bind_param($types, ...$destinatarios_ids);
            $stmt_users->execute();
            $result_users = $stmt_users->get_result();
            $motivo_chamado = $ticket_info['motivo_chamado'];

            while ($destinatario = $result_users->fetch_assoc()) {
                $corpo_html = criar_corpo_email_comentario($destinatario['nome'], $nome_usuario_comentou, $comentario_texto, $id_chamado, $motivo_chamado);
                enviar_notificacao_email($destinatario['email'], $destinatario['nome'], "Novo Comentário no Chamado #{$id_chamado}: " . htmlspecialchars($motivo_chamado), $corpo_html);
            }
            $stmt_users->close();
        }
        
        // Dispara WebSocket para comentários públicos
        $payload_comentario = ['type' => 'new_comment_added', 'payload' => ['nome_usuario' => $nome_usuario_comentou, 'comentario' => $comentario_texto, 'interno' => $eh_interno, 'data_comentario' => date('Y-m-d H:i:s')]];
        enviar_para_topico("chamado-{$id_chamado}", $payload_comentario);
        if(!empty($destinatarios_ids)) {
            foreach ($destinatarios_ids as $id_dest) {
                enviar_para_usuario($id_dest, ['type' => 'refresh_dashboard']);
            }
        }
        
    } else { // NOVA LÓGICA PARA COMENTÁRIOS INTERNOS
        
        // Busca todos os agentes de TI ativos para notificar
        $sql_agentes = "SELECT id, nome_completo, email FROM usuarios WHERE tipo_usuario = 'ti' AND ativo = 1";
        $agentes_ti = $conexao->query($sql_agentes)->fetch_all(MYSQLI_ASSOC);

        $motivo_chamado = $ticket_info['motivo_chamado'];
        $assunto_email = "[NOTA INTERNA] Chamado #{$id_chamado}: " . htmlspecialchars($motivo_chamado);
        
        foreach ($agentes_ti as $agente) {
            // Não envia e-mail para a pessoa que fez o comentário
            if ($agente['id'] == $id_usuario_comentou) {
                continue;
            }

            // Cria o corpo do e-mail usando o novo template de nota interna
            $corpo_html = criar_corpo_email_nota_interna($agente['nome_completo'], $nome_usuario_comentou, $comentario_texto, $id_chamado, $motivo_chamado);
            
            // Envia o e-mail usando seu serviço
            enviar_notificacao_email($agente['email'], $agente['nome_completo'], $assunto_email, $corpo_html);
        }
        
        // Dispara WebSocket para notificar a equipe de TI em tempo real
        $payload_interno = ['type' => 'new_internal_note_added', 'payload' => ['ticket_id' => $id_chamado]];
        enviar_para_topico('ti_agents', $payload_interno);
    }
    
    // =========================================================================
    // FIM DA LÓGICA DE NOTIFICAÇÕES
    // =========================================================================

    $conexao->commit();

    $mensagem_sucesso = "Comentário adicionado com sucesso!";
    if ($anexos_processados > 0) {
        $mensagem_sucesso .= " ($anexos_processados arquivo(s) anexado(s)).";
    }
    $_SESSION['mensagem_sucesso'] = $mensagem_sucesso;

} catch (Exception $e) {
    if (isset($conexao) && $conexao->ping()) {
        $conexao->rollback();
    }
    error_log("Erro Crítico em processa_comentario.php: " . $e->getMessage());
    $_SESSION['mensagem_erro'] = "Ocorreu um erro inesperado. Verifique os logs para mais detalhes.";
} finally {
    if (isset($conexao)) {
        $conexao->close();
    }
}

header("Location: detalhes_chamado.php?id=" . $id_chamado);
exit();