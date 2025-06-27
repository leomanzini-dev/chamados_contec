<?php
// obter_dados_relatorio.php

session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 'ti') {
    echo json_encode(['error' => 'Acesso negado']);
    exit();
}

// Monta a base da cláusula WHERE dinamicamente
$where_clauses = [];
$params = [];
$types = '';

if (!empty($_GET['status'])) {
    $where_clauses[] = "t.id_status = ?";
    $params[] = $_GET['status'];
    $types .= 'i';
}
if (!empty($_GET['categoria'])) {
    $where_clauses[] = "t.id_categoria = ?";
    $params[] = $_GET['categoria'];
    $types .= 'i';
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(' AND ', $where_clauses);
}

// Prepara o array de resposta
$response_data = [];

// 1. Dados para o gráfico de Status
$sql_status = "SELECT s.nome, COUNT(t.id) AS total 
               FROM tickets AS t 
               JOIN status_tickets AS s ON t.id_status = s.id 
               {$where_sql}
               GROUP BY s.nome ORDER BY total DESC";
$stmt_status = $conexao->prepare($sql_status);
if (!empty($params)) {
    $stmt_status->bind_param($types, ...$params);
}
$stmt_status->execute();
$dados_status = $stmt_status->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_status->close();
$response_data['grafico_status'] = [
    'labels' => array_column($dados_status, 'nome'),
    'valores' => array_column($dados_status, 'total')
];


// 2. Dados para o gráfico de Categoria
// A cláusula WHERE já foi montada e pode ser reutilizada
$sql_categoria = "SELECT c.nome, COUNT(t.id) AS total 
                  FROM tickets AS t 
                  JOIN categorias AS c ON t.id_categoria = c.id 
                  {$where_sql}
                  GROUP BY c.nome ORDER BY total DESC LIMIT 10";
$stmt_categoria = $conexao->prepare($sql_categoria);
if (!empty($params)) {
    $stmt_categoria->bind_param($types, ...$params);
}
$stmt_categoria->execute();
$dados_categoria = $stmt_categoria->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_categoria->close();
$response_data['grafico_categoria'] = [
    'labels' => array_column($dados_categoria, 'nome'),
    'valores' => array_column($dados_categoria, 'total')
];

// Retorna todos os dados em um único JSON
echo json_encode($response_data);
$conexao->close();
?>