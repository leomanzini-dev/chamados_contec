<?php
// processa_comentario.php - VERSÃO FINAL COM NOTIFICAÇÕES COMPLETAS

session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';
require_once __DIR__ . '/includes/push_service.php';
require_once __DIR__ . '/includes/websocket_service.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    die("Acesso não autorizado.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_chamado = filter_input(INPUT_POST, 'id_chamado', FILTER_VALIDATE_INT);
    $comentario_texto = trim($_POST['comentario']);
    $id_usuario_comentou = $_SESSION['usuario_id'];
    $nome_usuario_comentou = $_SESSION['usuario_nome'];
    $tipo_usuario = $_SESSION['usuario_tipo'];
    $eh_interno = ($tipo_usuario == 'ti' && isset($_POST['comentario_interno'])) ? 1 : 0;

    if (!$id_chamado || (empty($comentario_texto) && empty($_FILES['anexos']['name'][0]))) {
        $_SESSION['mensagem_erro'] = "Você precisa escrever um comentário ou anexar um ficheiro.";
        header("Location: detalhes_chamado.php?id=" . $id_chamado);
        exit();
    }

    $sql_perm = "SELECT id_solicitante, id_agente_atribuido FROM tickets WHERE id = ? LIMIT 1";
    $stmt_perm = $conexao->prepare($sql_perm);
    $stmt_perm->bind_param("i", $id_chamado);
    $stmt_perm->execute();
    $ticket_info = $stmt_perm->get_result()->fetch_assoc();
    $stmt_perm->close();

    if (!$ticket_info) { die("Chamado não encontrado."); }
    if ($tipo_usuario != 'ti' && $ticket_info['id_solicitante'] != $id_usuario_comentou) { die("Sem permissão."); }

    $conexao->begin_transaction();

    try {
        // 1. INSERE O COMENTÁRIO E ANEXOS
        $sql_insert = "INSERT INTO comentarios_tickets (id_ticket, id_usuario, comentario, interno) VALUES (?, ?, ?, ?)";
        $stmt_insert = $conexao->prepare($sql_insert);
        $stmt_insert->bind_param("iisi", $id_chamado, $id_usuario_comentou, $comentario_texto, $eh_interno);
        $stmt_insert->execute();
        $id_novo_comentario = $stmt_insert->insert_id;
        $stmt_insert->close();

        if (isset($_FILES['anexos']) && count($_FILES['anexos']['name']) > 0 && $_FILES['anexos']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $pasta_uploads = PROJECT_ROOT_PATH . '/uploads/';
            if (!is_dir($pasta_uploads)) { mkdir($pasta_uploads, 0777, true); }
            $sql_anexo = "INSERT INTO anexos_tickets (id_ticket, id_comentario, caminho_arquivo, nome_arquivo_original, tamanho_bytes) VALUES (?, ?, ?, ?, ?)";
            $stmt_anexo = $conexao->prepare($sql_anexo);
            foreach ($_FILES['anexos']['name'] as $key => $nome_original) {
                if ($_FILES['anexos']['error'][$key] === UPLOAD_ERR_OK) {
                    $nome_tmp = $_FILES['anexos']['tmp_name'][$key];
                    $tamanho_bytes = $_FILES['anexos']['size'][$key];
                    $nome_unico = uniqid('comentario' . $id_novo_comentario . '_', true) . '-' . basename($nome_original);
                    $caminho_final = $pasta_uploads . $nome_unico;
                    if (move_uploaded_file($nome_tmp, $caminho_final)) {
                        $caminho_relativo = 'uploads/' . $nome_unico;
                        $stmt_anexo->bind_param("iissi", $id_chamado, $id_novo_comentario, $caminho_relativo, $nome_original, $tamanho_bytes);
                        $stmt_anexo->execute();
                    }
                }
            }
            $stmt_anexo->close();
        }
        
        // 2. ATUALIZA A DATA DO TICKET E SALVA A NOTIFICAÇÃO NO BANCO
        $sql_update = "UPDATE tickets SET data_ultima_atualizacao = NOW() WHERE id = ?";
        $stmt_update = $conexao->prepare($sql_update);
        $stmt_update->bind_param("i", $id_chamado);
        $stmt_update->execute();
        $stmt_update->close();

        $destinatarios_ids = [];
        $mensagem_notificacao_app = "";
        
        if ($eh_interno == 0) {
            if ($tipo_usuario == 'ti') {
                $destinatarios_ids[] = $ticket_info['id_solicitante'];
                $mensagem_notificacao_app = "A equipe de TI comentou no seu chamado #" . $id_chamado . ".";
            } else {
                if (!empty($ticket_info['id_agente_atribuido'])) {
                    $destinatarios_ids[] = $ticket_info['id_agente_atribuido'];
                } else {
                    $sql_ti = "SELECT id FROM usuarios WHERE tipo_usuario = 'ti' AND ativo = 1";
                    $result_ti = $conexao->query($sql_ti);
                    while($row = $result_ti->fetch_assoc()) { $destinatarios_ids[] = $row['id']; }
                }
                $mensagem_notificacao_app = "O solicitante comentou no chamado #" . $id_chamado . ".";
            }

            $destinatarios_ids = array_filter($destinatarios_ids, function($id) use ($id_usuario_comentou) { return $id != $id_usuario_comentou; });
            $destinatarios_ids = array_unique($destinatarios_ids);

            if (!empty($destinatarios_ids)) {
                $sql_nova_notif = "INSERT INTO notificacoes (id_usuario_destino, id_ticket, mensagem) VALUES (?, ?, ?)";
                $stmt_nova_notif = $conexao->prepare($sql_nova_notif);
                foreach ($destinatarios_ids as $id_destinatario) {
                    $stmt_nova_notif->bind_param("iis", $id_destinatario, $id_chamado, $mensagem_notificacao_app);
                    $stmt_nova_notif->execute();
                }
                $stmt_nova_notif->close();
            }
        }
        
        // 3. SALVA TUDO PERMANENTEMENTE NO BANCO DE DADOS
        $conexao->commit();
        
        // 4. SÓ DEPOIS DE SALVAR, ENVIA AS NOTIFICAÇÕES EM TEMPO REAL
        if ($eh_interno == 0 && !empty($destinatarios_ids)) {
            // Envia a Notificação Push
            $titulo_push = "Novo Comentário no Chamado #{$id_chamado}";
            $corpo_push = htmlspecialchars($nome_usuario_comentou) . ": " . (strlen($comentario_texto) > 50 ? substr($comentario_texto, 0, 50) . '...' : $comentario_texto);
            enviar_notificacao_push($conexao, $destinatarios_ids, $id_chamado, $titulo_push, $corpo_push, 'chamado-' . $id_chamado);

            // Envia a atualização para a página de detalhes do chamado (para todos que a veem)
            $payload_comentario = ['type' => 'new_comment_added', 'payload' => [
                'nome_usuario' => $nome_usuario_comentou, 'comentario' => $comentario_texto,
                'interno' => $eh_interno, 'data_comentario' => date('Y-m-d H:i:s')
            ]];
            enviar_para_topico("chamado-{$id_chamado}", $payload_comentario);
            
            // Envia um sinal para o painel de cada destinatário se atualizar
            foreach($destinatarios_ids as $id_dest) {
                enviar_para_usuario($id_dest, ['type' => 'refresh_dashboard']);
            }
        }
        
    } catch (Exception $e) {
        $conexao->rollback();
        error_log("Erro ao processar comentário: " . $e->getMessage());
        $_SESSION['mensagem_erro'] = "Ocorreu um erro ao processar sua solicitação.";
    }
    
    // Redireciona de volta para a página de detalhes no final
    header("Location: detalhes_chamado.php?id=" . $id_chamado);
    exit();
}
?>