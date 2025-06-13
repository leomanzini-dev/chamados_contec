<?php
// generate_keys.php

// Garante que o autoload do Composer foi executado com sucesso
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<h1>Erro</h1>";
    echo "<p>A pasta 'vendor' não foi encontrada. Por favor, execute 'composer require minishlink/web-push' na pasta do seu projeto.</p>";
    exit();
}

require_once __DIR__ . '/vendor/autoload.php';
use Minishlink\WebPush\VAPID;

try {
    $keys = VAPID::createVapidKeys();

    echo "<h1>Suas Chaves VAPID foram Geradas com Sucesso!</h1>";
    echo "<p>Copie estas chaves e adicione-as ao seu ficheiro <strong>config.php</strong>.</p>";
    echo "<hr>";
    
    echo "<h3>Chave Pública:</h3>";
    echo "<pre style='background-color:#f0f0f0; padding:10px; border-radius:5px; word-wrap:break-word;'>" . htmlspecialchars($keys['publicKey']) . "</pre>";
    
    echo "<h3>Chave Privada:</h3>";
    echo "<pre style='background-color:#f0f0f0; padding:10px; border-radius:5px; word-wrap:break-word;'>" . htmlspecialchars($keys['privateKey']) . "</pre>";

    echo "<hr>";
    echo "<p><strong>IMPORTANTE:</strong> Depois de copiar as chaves, apague este ficheiro (generate_keys.php) do seu servidor.</p>";

} catch (Exception $e) {
    echo "<h1>Ocorreu um Erro</h1>";
    echo "<p>Não foi possível gerar as chaves. Erro: " . $e->getMessage() . "</p>";
}

?>
