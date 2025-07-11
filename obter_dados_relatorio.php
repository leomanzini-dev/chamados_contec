<?php
// obter_dados_relatorio.php
header('Content-Type: application/json');
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

session_start();
// Apenas usuários 'ti' podem acessar os dados
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 'ti') {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado.']);
    exit();
}

// Mapa de cores para cada status (baseado no seu CSS)
$mapa_cores_status = [
    'aberto' => '#3b82f6',
    'em andamento' => '#f59e0b',
    'aguardando solicitante' => '#6b7280',
    'pausado' => '#6366f1',
    'resolvido' => '#22c55e',
    'cancelado' => '#ef4444'
];

// --- Lógica dos Filtros (vinda do GET) ---
$filtro_status_id = filter_input(INPUT_GET, 'status', FILTER_VALIDATE_INT);
$filtro_categoria_id = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);

// Monta a cláusula WHERE dinamicamente
$where_clauses = [];
$params = [];
$types = '';

if ($filtro_status_id) {
    $where_clauses[] = "t.id_status = ?";
    $params[] = $filtro_status_id;
    $types .= 'i';
}
if ($filtro_categoria_id) {
    $where_clauses[] = "t.id_categoria = ?";
    $params[] = $filtro_categoria_id;
    $types .= 'i';
}
$where_sql = !empty($where_clauses) ? "WHERE " . implode(' AND ', $where_clauses) : "";


// --- Consulta para o Gráfico de Status ---
$sql_status = "
    SELECT s.nome, COUNT(t.id) as total
    FROM status_tickets s
    LEFT JOIN tickets t ON s.id = t.id_status " . (strpos($where_sql, 't.id_categoria') ? "AND t.id_categoria = ?" : "") . "
    " . (strpos($where_sql, 't.id_status') ? "AND t.id_status = ?" : "") . "
    GROUP BY s.id, s.nome
    ORDER BY s.nome
";
// A lógica de filtros para status é um pouco diferente, pois queremos todos os status, mesmo que zerados.
// Para simplificar, vamos filtrar o status principal aqui se ele for selecionado.
if ($filtro_status_id) {
    $sql_status = "
        SELECT s.nome, COUNT(t.id) as total
        FROM status_tickets s
        LEFT JOIN tickets t ON s.id = t.id_status
        WHERE s.id = ? " . (strpos($where_sql, 't.id_categoria') ? "AND t.id_categoria = ?" : "") . "
        GROUP BY s.id, s.nome
    ";
    $params_status = $filtro_categoria_id ? [$filtro_status_id, $filtro_categoria_id] : [$filtro_status_id];
    $types_status = $filtro_categoria_id ? 'ii' : 'i';
} else {
    $params_status = $filtro_categoria_id ? [$filtro_categoria_id] : [];
    $types_status = $filtro_categoria_id ? 'i' : '';
}

$stmt_status = $conexao->prepare($sql_status);
if (!empty($params_status)) {
    $stmt_status->bind_param($types_status, ...$params_status);
}
$stmt_status->execute();
$resultado_status = $stmt_status->get_result()->fetch_all(MYSQLI_ASSOC);

$labels_status = [];
$dados_status = [];
$cores_status = [];
foreach($resultado_status as $row) {
    $labels_status[] = $row['nome'];
    $dados_status[] = $row['total'];
    // Busca a cor no mapa de cores. Se não encontrar, usa uma cor padrão.
    $cores_status[] = $mapa_cores_status[strtolower($row['nome'])] ?? '#cccccc';
}


// --- Consulta para o Gráfico de Categorias ---
$sql_categoria = "
    SELECT c.nome, COUNT(t.id) as total
    FROM categorias c
    LEFT JOIN tickets t ON c.id = t.id_categoria " . $where_sql . "
    GROUP BY c.id, c.nome
    HAVING total > 0
    ORDER BY total DESC
    LIMIT 10
";
$stmt_categoria = $conexao->prepare($sql_categoria);
if (!empty($params)) {
    $stmt_categoria->bind_param($types, ...$params);
}
$stmt_categoria->execute();
$resultado_categoria = $stmt_categoria->get_result()->fetch_all(MYSQLI_ASSOC);

$labels_categoria = [];
$dados_categoria = [];
foreach($resultado_categoria as $row) {
    $labels_categoria[] = $row['nome'];
    $dados_categoria[] = $row['total'];
}


// Monta o JSON de resposta
$resposta = [
    'graficoStatus' => [
        'labels' => $labels_status,
        'dados' => $dados_status,
        'cores' => $cores_status,
    ],
    'graficoCategoria' => [
        'labels' => $labels_categoria,
        'dados' => $dados_categoria,
    ]
];

echo json_encode($resposta);

$conexao->close();