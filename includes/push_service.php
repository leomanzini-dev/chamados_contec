<?php
// includes/push_service.php

// Requer a biblioteca de Notificações Push
require_once __DIR__ . '/../vendor/autoload.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Função centralizada para enviar notificações push.
 *
 * @param mysqli $conexao A conexão com a base de dados (passada como parâmetro).
 * @param array $destinatarios_ids Array com os IDs dos utilizadores a notificar.
 * @param int $id_chamado O ID do chamado relacionado.
 * @param string $titulo O título da notificação.
 * @param string $corpo A mensagem principal da notificação.
 * @param string $tag Uma tag para agrupar notificações (opcional).
 */
function enviar_notificacao_push(mysqli $conexao, array $destinatarios_ids, int $id_chamado, string $titulo, string $corpo, string $tag = 'geral')
{
    // Garante que não há IDs duplicados e que a lista não está vazia
    $destinatarios_ids = array_unique(array_filter($destinatarios_ids));
    if (empty($destinatarios_ids) || !defined('VAPID_PUBLIC_KEY') || !defined('VAPID_PRIVATE_KEY')) {
        return;
    }

    $ids_para_query = implode(',', array_map('intval', $destinatarios_ids));
    
    $sql_subs = "SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE id_usuario IN ($ids_para_query)";
    $subscriptions_result = $conexao->query($sql_subs);

    if ($subscriptions_result && $subscriptions_result->num_rows > 0) {
        $auth = [
            'VAPID' => [
                'subject' => 'mailto:seu-email@seudominio.com', // Substitua pelo seu e-mail
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ],
        ];

        $webPush = new WebPush($auth);
        
        $url_base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        $caminho_projeto = '/chamados_contec'; // Ajuste se o nome da sua pasta for diferente

        $payload = json_encode([
            'title' => $titulo,
            'body' => $corpo,
            'icon' => $url_base . $caminho_projeto . '/img/logo_contec.png',
            'data' => ['url' => $url_base . $caminho_projeto . '/detalhes_chamado.php?id=' . $id_chamado],
            'tag' => $tag . '-' . $id_chamado
        ]);

        while ($sub = $subscriptions_result->fetch_assoc()) {
            $subscription = Subscription::create($sub + ["contentEncoding" => "aesgcm"]);
            $webPush->queueNotification($subscription, $payload);
        }

        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess() && $report->isSubscriptionExpired()) {
                $endpoint = $report->getRequest()->getUri()->__toString();
                $stmt_delete = $conexao->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
                $stmt_delete->bind_param('s', $endpoint);
                $stmt_delete->execute();
            }
        }
    }
}
