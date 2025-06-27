<?php
// includes/push_service.php - VERSÃO FINAL E DEFINITIVA

require_once __DIR__ . '/../vendor/autoload.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

function enviar_notificacao_push(mysqli $conexao, array $destinatarios_ids, int $id_chamado, string $titulo, string $corpo, string $tag = 'geral')
{
    $destinatarios_ids = array_unique(array_filter($destinatarios_ids));
    if (empty($destinatarios_ids) || !defined('VAPID_PUBLIC_KEY') || !defined('VAPID_PRIVATE_KEY') || !defined('APP_URL')) {
        return;
    }

    $ids_para_query = implode(',', array_map('intval', $destinatarios_ids));
    
    $sql_subs = "SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE id_usuario IN ($ids_para_query)";
    $subscriptions_result = $conexao->query($sql_subs);

    if ($subscriptions_result && $subscriptions_result->num_rows > 0) {
        $auth = [
            'VAPID' => [
                'subject' => 'mailto:seu-email@seudominio.com',
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ],
        ];

        try {
            $webPush = new WebPush($auth);
            
            $payload = json_encode([
                'title' => $titulo,
                'body' => $corpo,
                'icon' => APP_URL . '/img/logo_contec.png',
                'data' => ['url' => APP_URL . '/detalhes_chamado.php?id=' . $id_chamado],
                'tag' => $tag
            ]);

            // ===== ADIÇÃO: OPÇÕES DE ENVIO COM URGÊNCIA ALTA =====
            $options = [
                'TTL' => 3600, // Tempo de vida da notificação em segundos (1 hora)
                'urgency' => 'high', // Força a entrega imediata
            ];

            while ($sub = $subscriptions_result->fetch_assoc()) {
                $subscription = Subscription::create($sub + ["contentEncoding" => "aesgcm"]);
                // Adicionamos as opções ao enfileirar a notificação
                $webPush->queueNotification($subscription, $payload, $options);
            }
            
            // Adiciona um log para vermos exatamente o que está sendo enviado
            error_log("WebPush Payload Enviado: " . $payload);

            foreach ($webPush->flush() as $report) {
                if (!$report->isSuccess()) {
                    error_log("Push Report: Falha ao enviar para " . $report->getEndpoint() . ". Motivo: " . $report->getReason());
                    if ($report->isSubscriptionExpired()) {
                        $endpoint_expirado = $report->getRequest()->getUri()->__toString();
                        $stmt_delete = $conexao->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
                        $stmt_delete->bind_param('s', $endpoint_expirado);
                        $stmt_delete->execute();
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Erro geral no WebPush: " . $e->getMessage());
        }
    }
}