<?php
// websocket/ChatServer.php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface {
    protected $clients;

    // Armazena as inscrições em tópicos. Formato: $subscriptions['nome-do-topico'][resourceId] = $conexao
    private $subscriptions;

    // NOVO: Armazena as conexões por ID de usuário. Formato: $userConnections[userId][resourceId] = $conexao
    private $userConnections;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
        $this->userConnections = [];
        echo "Servidor de Chat aprimorado iniciado.\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nova conexão: ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        if (!$data || !isset($data['action'])) {
            return; // Ignora mensagens malformadas
        }

        switch ($data['action']) {
            // AÇÃO NOVA: Cliente se identifica com seu ID de usuário
            case 'register':
                $userId = $data['userId'] ?? null;
                if ($userId) {
                    $this->userConnections[$userId][$from->resourceId] = $from;
                    echo "Cliente {$from->resourceId} registrado como usuário {$userId}\n";
                }
                break;

            // Ação de se inscrever em um tópico (ex: um chamado específico)
            case 'subscribe':
                $topic = $data['topic'] ?? null;
                if ($topic) {
                    $this->subscriptions[$topic][$from->resourceId] = $from;
                    echo "Cliente {$from->resourceId} subscreveu ao tópico {$topic}\n";
                }
                break;

            // AÇÃO MODIFICADA: Renomeada para ser mais clara
            case 'broadcast_to_topic':
                $topic = $data['topic'] ?? null;
                if ($topic && !empty($this->subscriptions[$topic])) {
                    $payload = json_encode($data['payload']);
                    foreach ($this->subscriptions[$topic] as $client) {
                        $client->send($payload);
                    }
                    echo "Mensagem transmitida para o tópico {$topic}\n";
                }
                break;
            
            // AÇÃO NOVA: Enviar mensagem para um usuário específico
            case 'broadcast_to_user':
                $userId = $data['userId'] ?? null;
                if ($userId && !empty($this->userConnections[$userId])) {
                    $payload = json_encode($data['payload']);
                    foreach ($this->userConnections[$userId] as $client) {
                        $client->send($payload);
                    }
                    echo "Mensagem transmitida para o usuário {$userId}\n";
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);

        // Remove o cliente de todas as subscrições de tópicos
        foreach ($this->subscriptions as $topic => &$clients) {
            unset($clients[$conn->resourceId]);
        }
        
        // Remove o cliente de todas as conexões de usuários
        foreach ($this->userConnections as $userId => &$connections) {
            unset($connections[$conn->resourceId]);
        }

        echo "Conexão {$conn->resourceId} foi desconectada.\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Ocorreu um erro: {$e->getMessage()}\n";
        $conn->close();
    }
}