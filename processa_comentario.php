<?php
session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

// Carrega os serviços necessários
require_once __DIR__ . '/includes/websocket_service.php';
require_once __DIR__ . '/includes/email_service.php';
require_once __DIR__ . '/includes/email_templates.php';

// Garante que a requisição é do tipo POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Se não for POST, redireciona para o painel para evitar acesso direto ao script
    header("Location: painel.php");
    exit();
}

// Validação de sessão de usuário
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    die("Acesso não autorizado.");
}

// Coleta e validação dos dados do formulário
$id_chamado = filter_input(INPUT_POST, 'id_chamado', FILTER_VALIDATE_INT);
$comentario_texto = trim($_POST['comentario']);
$id_usuario_comentou = $_SESSION['usuario_id'];
$nome_usuario_comentou = $_SESSION['usuario_nome'];
$tipo_usuario = $_SESSION['usuario_tipo'];
$eh_interno = ($tipo_usuario == 'ti' && isset($_POST['comentario_interno'])) ? 1 : 0;

// Garante que o comentário ou anexo não estão vazios
if (!$id_chamado || (empty($comentario_texto) && empty($_FILES['anexos']['name'][0]))) {
    $_SESSION['mensagem_erro'] = "Você precisa escrever um comentário ou anexar um ficheiro.";
    header("Location: detalhes_chamado.php?id=" . $id_chamado);
    exit();
}

// Bloco Try-Catch para controlar toda a operação
try {
    // Busca informações e permissões do ticket
    $sql_perm = "SELECT id_solicitante, id_agente_atribuido, motivo_chamado FROM tickets WHERE id = ? LIMIT 1";
    $stmt_perm = $conexao->prepare($sql_perm);
    $stmt_perm->bind_param("i", $id_chamado);
    $stmt_perm->execute();
    $ticket_info = $stmt_perm->get_result()->fetch_assoc();
    $stmt_perm->close();

    if (!$ticket_info) {
        throw new Exception("Chamado não encontrado.");
    }
    if ($tipo_usuario != 'ti' && $ticket_info['id_solicitante'] != $id_usuario_comentou) {
        throw new Exception("Sem permissão para comentar neste chamado.");
    }

    // Inicia a transação com o banco de dados
    $conexao->begin_transaction();

    // 1. INSERE O COMENTÁRIO
    $sql_insert = "INSERT INTO comentarios_tickets (id_ticket, id_usuario, comentario, interno) VALUES (?, ?, ?, ?)";
    $stmt_insert = $conexao->prepare($sql_insert);
    $stmt_insert->bind_param("iisi", $id_chamado, $id_usuario_comentou, $comentario_texto, $eh_interno);
    $stmt_insert->execute();
    $id_novo_comentario = $stmt_insert->insert_id;
    $stmt_insert->close();

    // 2. PROCESSA OS ANEXOS (se houver)
    if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
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
                    $caminho_relativo = 'uploads/' . $nome_unico; // Corrigido para salvar caminho relativo
                    $stmt_anexo->bind_param("iissi", $id_chamado, $id_novo_comentario, $caminho_relativo, $nome_original, $tamanho_bytes);
                    $stmt_anexo->execute();
                }
            }
        }
        $stmt_anexo->close();
    }

    // 3. ATUALIZA O TICKET E PREPARA AS NOTIFICAÇÕES
    $sql_update = "UPDATE tickets SET data_ultima_atualizacao = NOW() WHERE id = ?";
    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->bind_param("i", $id_chamado);
    $stmt_update->execute();
    $stmt_update->close();

    $destinatarios_ids = [];
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

    // SALVA TUDO NO BANCO DE DADOS
    $conexao->commit();

    // 4. ENVIA AS NOTIFICAÇÕES (E-MAIL E WEBSOCKET) - Apenas após o sucesso do commit
    if ($eh_interno == 0 && !empty($destinatarios_ids)) {
        // Envio de E-mail
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

        // Envio de WebSocket (Mantido)
        $payload_comentario = ['type' => 'new_comment_added', 'payload' => ['nome_usuario' => $nome_usuario_comentou, 'comentario' => $comentario_texto, 'interno' => $eh_interno, 'data_comentario' => date('Y-m-d H:i:s')]];
        enviar_para_topico("chamado-{$id_chamado}", $payload_comentario);
        foreach($destinatarios_ids as $id_dest) {
            enviar_para_usuario($id_dest, ['type' => 'refresh_dashboard']);
        }
    }

} catch (Exception $e) {
    // Se qualquer coisa dentro do 'try' falhar, desfaz a transação
    $conexao->rollback();
    // Registra o erro para o desenvolvedor
    error_log("Erro ao processar comentário: " . $e->getMessage());
    // Prepara uma mensagem de erro para o usuário
    $_SESSION['mensagem_erro'] = "Ocorreu um erro ao processar sua solicitação.";
}

// O redirecionamento acontece aqui no final, após o try-catch ter sido concluído com sucesso ou falha.
header("Location: detalhes_chamado.php?id=" . $id_chamado);
exit();