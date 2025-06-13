<?php
// processa_comentario.php - VERSÃO FINAL

session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';
require_once __DIR__ . '/vendor/autoload.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403); die("Acesso não autorizado.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_chamado = filter_input(INPUT_POST, 'id_chamado', FILTER_VALIDATE_INT);
    $comentario_texto = trim($_POST['comentario']);
    $id_usuario_comentou = $_SESSION['usuario_id'];
    $nome_usuario_comentou = $_SESSION['usuario_nome'];
    $tipo_usuario = $_SESSION['usuario_tipo'];
    $eh_interno = ($tipo_usuario == 'ti' && isset($_POST['comentario_interno'])) ? 1 : 0;

    if (!$id_chamado || (empty($comentario_texto) && empty($_FILES['anexos']['name'][0]))) {
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

    // Inserir o comentário...
    $sql_insert = "INSERT INTO comentarios_tickets (id_ticket, id_usuario, comentario, interno) VALUES (?, ?, ?, ?)";
    $stmt_insert = $conexao->prepare($sql_insert);
    $stmt_insert->bind_param("iisi", $id_chamado, $id_usuario_comentou, $comentario_texto, $eh_interno);
    $stmt_insert->execute();
    $id_novo_comentario = $stmt_insert->insert_id;
    $stmt_insert->close();

    // Lógica de upload de anexos...

    // --- LÓGICA DE NOTIFICAÇÃO (App e Push) ---
    if ($eh_interno == 0) {
        $destinatarios_ids = [];
        // ... (sua lógica para determinar os destinatários) ...

        if (!empty($destinatarios_ids)) {
            // Insere a notificação no sino do site...

            // ENVIA A NOTIFICAÇÃO PUSH
            $ids_para_query = implode(',', array_map('intval', $destinatarios_ids));
            $sql_subs = "SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE id_usuario IN ($ids_para_query)";
            $subscriptions_result = $conexao->query($sql_subs);

            if ($subscriptions_result && $subscriptions_result->num_rows > 0) {
                $auth = ['VAPID' => ['subject' => 'mailto:seu-email@seudominio.com', 'publicKey' => VAPID_PUBLIC_KEY, 'privateKey' => VAPID_PRIVATE_KEY]];
                $webPush = new WebPush($auth);
                $payload = json_encode([/*... seu payload ...*/]);

                while ($sub = $subscriptions_result->fetch_assoc()) {
                    
                    // << CORREÇÃO >> Criamos o objeto de subscrição com todos os dados necessários.
                    $subscription = Subscription::create([
                        "endpoint" => $sub['endpoint'],
                        "publicKey" => $sub['p256dh'],
                        "authToken" => $sub['auth'],
                        "contentEncoding" => "aesgcm" // A informação que estava em falta
                    ]);

                    $webPush->queueNotification($subscription, $payload);
                }

                foreach ($webPush->flush() as $report) {
                    // ... (lógica para tratar os relatórios de envio) ...
                }
            }
        }
    }

    // Atualiza a data do ticket e redireciona...
    header("Location: detalhes_chamado.php?id=" . $id_chamado);
    exit();
}
?>
