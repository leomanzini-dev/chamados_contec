<?php
// includes/websocket_service.php
require_once __DIR__ . '/../vendor/autoload.php';
use WebSocket\Client;

/**
 * Função interna para criar e retornar um cliente WebSocket.
 * Evita duplicação de código e centraliza a gestão de erros.
 */
function get_ws_client() {
    try {
        // A porta 8080 é a que definimos no seu websocket/server.php
        return new Client("ws://127.0.0.1:8080", ['timeout' => 5]);
    } catch (Exception $e) {
        // Se o servidor WebSocket não estiver rodando, apenas registra o erro no log do PHP
        // sem quebrar a aplicação principal.
        error_log("Erro ao conectar ao WebSocket: " . $e->getMessage());
        return null;
    }
}

/**
 * Envia uma mensagem para todos os clientes inscritos em um TÓPICO específico.
 * Útil para: atualizar uma página específica que vários usuários podem estar vendo.
 * Exemplo: enviar uma atualização para 'chamado-123'.
 *
 * @param string $topic O tópico para o qual a mensagem deve ser enviada.
 * @param array $payload Os dados a serem enviados (o conteúdo da mensagem).
 */
function enviar_para_topico(string $topic, array $payload) {
    $client = get_ws_client();
    if (!$client) {
        return; // Não faz nada se não conseguir conectar
    }

    $message = [
        'action'  => 'broadcast_to_topic',
        'topic'   => $topic,
        'payload' => $payload
    ];

    try {
        $client->send(json_encode($message));
        $client->close();
    } catch (Exception $e) {
        error_log("Erro ao enviar mensagem para o tópico {$topic}: " . $e->getMessage());
    }
}

/**
 * Envia uma mensagem para todas as conexões de um USUÁRIO específico.
 * Útil para: notificações pessoais, como o sino de notificações.
 * Exemplo: enviar um aviso para o usuário de ID 42.
 *
 * @param int $userId O ID do usuário a notificar.
 * @param array $payload Os dados a serem enviados (o conteúdo da mensagem).
 */
function enviar_para_usuario(int $userId, array $payload) {
    $client = get_ws_client();
    if (!$client) {
        return; // Não faz nada se não conseguir conectar
    }

    $message = [
        'action'  => 'broadcast_to_user',
        'userId'  => $userId,
        'payload' => $payload
    ];
    
    try {
        $client->send(json_encode($message));
        $client->close();
    } catch (Exception $e) {
        error_log("Erro ao enviar mensagem para o usuário {$userId}: " . $e->getMessage());
    }
}