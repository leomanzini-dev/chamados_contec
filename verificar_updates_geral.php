<?php
// verificar_updates_geral.php

require_once __DIR__ . '/config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 'ti') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Acesso negado.']);
    exit();
}

$id_usuario_logado = $_SESSION['usuario_id'];
$resposta = [];

// --- DADOS COMUNS ---
// Notificações
$sql_notificacoes = "SELECT COUNT(id) AS total_nao_lidas FROM notificacoes WHERE id_usuario_destino = ? AND lida = FALSE";
$stmt_notificacoes = $conexao->prepare($sql_notificacoes);
if ($stmt_notificacoes) {
    $stmt_notificacoes->bind_param("i", $id_usuario_logado);
    $stmt_notificacoes->execute();
    $resposta['notificacoes_nao_lidas'] = (int) $stmt_notificacoes->get_result()->fetch_assoc()['total_nao_lidas'];
    $stmt_notificacoes->close();
}

// Lista de Notificações
$sql_lista_notif = "SELECT id, id_ticket, mensagem, data_criacao FROM notificacoes WHERE id_usuario_destino = ? ORDER BY data_criacao DESC LIMIT 5";
$stmt_lista_notif = $conexao->prepare($sql_lista_notif);
if ($stmt_lista_notif) {
    $stmt_lista_notif->bind_param("i", $id_usuario_logado);
    $stmt_lista_notif->execute();
    $resposta['lista_notificacoes'] = $stmt_lista_notif->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_lista_notif->close();
}

// --- LÓGICA DA TABELA "GERENCIAR CHAMADOS" ---
// Reaplica a mesma lógica de filtros da página principal
$filtro_status = filter_input(INPUT_GET, 'status', FILTER_VALIDATE_INT);
$filtro_prioridade = filter_input(INPUT_GET, 'prioridade', FILTER_VALIDATE_INT);
$filtro_agente = filter_input(INPUT_GET, 'agente', FILTER_VALIDATE_INT);
$filtro_busca = trim(filter_input(INPUT_GET, 'busca', FILTER_SANITIZE_STRING));

$sql = "SELECT t.id, t.motivo_chamado, t.data_ultima_atualizacao, c.nome AS nome_categoria, p.nome AS nome_prioridade, s.nome AS nome_status, solicitante.nome_completo AS nome_solicitante, agente.nome_completo AS nome_agente FROM tickets AS t JOIN usuarios AS solicitante ON t.id_solicitante = solicitante.id LEFT JOIN usuarios AS agente ON t.id_agente_atribuido = agente.id JOIN categorias AS c ON t.id_categoria = c.id JOIN prioridades AS p ON t.id_prioridade = p.id JOIN status_tickets AS s ON t.id_status = s.id";

$where_clauses = [];
$params = [];
$types = '';

if ($filtro_status) { $where_clauses[] = "t.id_status = ?"; $params[] = $filtro_status; $types .= 'i'; }
if ($filtro_prioridade) { $where_clauses[] = "t.id_prioridade = ?"; $params[] = $filtro_prioridade; $types .= 'i'; }
if ($filtro_agente) { $where_clauses[] = "t.id_agente_atribuido = ?"; $params[] = $filtro_agente; $types .= 'i'; }
if (!empty($filtro_busca)) { $where_clauses[] = "(t.motivo_chamado LIKE ? OR solicitante.nome_completo LIKE ?)"; $params[] = "%" . $filtro_busca . "%"; $params[] = "%" . $filtro_busca . "%"; $types .= 'ss'; }

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY t.data_ultima_atualizacao DESC";

$stmt = $conexao->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();
$resposta['chamados_gerenciados'] = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conexao->close();
header('Content-Type: application/json');
echo json_encode($resposta);
?>
