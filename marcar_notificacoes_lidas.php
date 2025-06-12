<?php
// marcar_notificacoes_lidas.php
session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

$id_usuario = $_SESSION['usuario_id'];

$sql = "UPDATE notificacoes SET lida = TRUE WHERE id_usuario_destino = ? AND lida = FALSE";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id_usuario);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Falha ao atualizar o banco de dados']);
}

$stmt->close();
$conexao->close();
?>
