<?php
// save-subscription.php

require_once __DIR__ . '/config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['endpoint'])) {
    http_response_code(400);
    exit();
}

$endpoint = $data['endpoint'];
$p256dh = $data['keys']['p256dh'];
$auth = $data['keys']['auth'];

// Verifica se a subscrição já existe para evitar duplicados
$sql_check = "SELECT id FROM push_subscriptions WHERE endpoint = ?";
$stmt_check = $conexao->prepare($sql_check);
$stmt_check->bind_param("s", $endpoint);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // A subscrição já existe, podemos apenas garantir que está associada ao utilizador correto
    $sql_update = "UPDATE push_subscriptions SET id_usuario = ? WHERE endpoint = ?";
    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->bind_param("is", $id_usuario, $endpoint);
    $stmt_update->execute();
} else {
    // Insere a nova subscrição
    $sql_insert = "INSERT INTO push_subscriptions (id_usuario, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)";
    $stmt_insert = $conexao->prepare($sql_insert);
    $stmt_insert->bind_param("isss", $id_usuario, $endpoint, $p256dh, $auth);
    $stmt_insert->execute();
}

http_response_code(201);
