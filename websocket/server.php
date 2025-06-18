<?php
// websocket/server.php
require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/ChatServer.php'; // Garante que a classe é carregada

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\ChatServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    8080
);

echo "Servidor de WebSocket a correr na porta 8080...\n";
$server->run();