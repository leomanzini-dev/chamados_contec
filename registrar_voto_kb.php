<?php
// registrar_voto_kb.php
session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

header('Content-Type: application/json');

// Apenas utilizadores logados podem votar
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit();
}

// Obtém os dados enviados pelo JavaScript
$data = json_decode(file_get_contents('php://input'), true);
$id_artigo = filter_var($data['id_artigo'] ?? null, FILTER_VALIDATE_INT);

if (!$id_artigo) {
    echo json_encode(['success' => false, 'message' => 'ID de artigo inválido.']);
    exit();
}

// Atualiza a contagem de votos no banco de dados
// Usamos + 1 para evitar condições de corrida (race conditions)
$sql = "UPDATE kb_artigos SET votos_uteis = votos_uteis + 1 WHERE id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id_artigo);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao registar o voto.']);
}

$stmt->close();
$conexao->close();
