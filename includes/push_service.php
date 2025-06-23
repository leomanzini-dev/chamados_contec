<?php
// includes/push_service.php

require_once __DIR__ . '/../vendor/autoload.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

function enviar_notificacao_push(mysqli $conexao, array $destinatarios_ids, int $id_chamado, string $titulo, string $corpo, string $tag = 'geral')
{
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
                'subject' => 'mailto:seu-email@seudominio.com', // Substitua
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ],
        ];

        try { // ===== ADICIONADO PARA DEBUG =====
            $webPush = new WebPush($auth);
            
            $url_base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
            $caminho_projeto = '/chamados_contec'; // Ajuste se necessário

            $payload = json_encode([
                'title' => $titulo,
                'body' => $corpo,
                'icon' => $url_base . $caminho_projeto . '/img/logo_contec.png',
                'data' => ['url' => $url_base . $caminho_projeto . '/detalhes_chamado.php?id=' . $id_chamado],
                'tag' => $tag
            ]);

            while ($sub = $subscriptions_result->fetch_assoc()) {
                $subscription = Subscription::create($sub + ["contentEncoding" => "aesgcm"]);
                $webPush->queueNotification($subscription, $payload);
            }

            // Envia todas as notificações na fila e verifica por erros
            foreach ($webPush->flush() as $report) {
                if (!$report->isSuccess()) {
                    // Se uma notificação falhar, registra o erro no log do PHP
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
            // Se ocorrer um erro geral na biblioteca, registra no log do PHP
            error_log("Erro geral no WebPush: " . $e->getMessage());
        } // ===== FIM DO BLOCO ADICIONADO =====
    }
}