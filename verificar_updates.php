<?php
// verificar_updates.php

session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Não autenticado']);
    exit();
}

$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario = $_SESSION['usuario_tipo'];
$ultimo_id = filter_input(INPUT_GET, 'ultimo_id', FILTER_VALIDATE_INT) ?? 0;

$response_data = [
    'tipo_usuario' => $tipo_usuario
];

// --- BUSCA OS DADOS DE NOTIFICAÇÃO (COMUM A TODOS) ---
$sql_notif_count = "SELECT COUNT(id) AS total FROM notificacoes WHERE id_usuario_destino = ? AND lida = FALSE";
$stmt_count = $conexao->prepare($sql_notif_count);
$stmt_count->bind_param("i", $id_usuario_logado);
$stmt_count->execute();
$response_data['notificacoes_nao_lidas'] = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();

$sql_notif_list = "SELECT id, id_ticket, mensagem, data_criacao FROM notificacoes WHERE id_usuario_destino = ? ORDER BY data_criacao DESC LIMIT 5";
$stmt_list = $conexao->prepare($sql_notif_list);
$stmt_list->bind_param("i", $id_usuario_logado);
$stmt_list->execute();
$response_data['lista_notificacoes'] = $stmt_list->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_list->close();


// --- BUSCA DADOS ESPECÍFICOS POR TIPO DE USUÁRIO ---
if ($tipo_usuario == 'ti') {
    // Busca estatísticas para TI
    $sql_stats_ti = "SELECT (SELECT COUNT(id) FROM tickets WHERE id_status = 1) AS abertos, (SELECT COUNT(id) FROM tickets WHERE id_status = 2) AS andamento, (SELECT COUNT(id) FROM tickets WHERE id_status = 5) AS resolvidos_total FROM DUAL";
    $response_data['estatisticas'] = $conexao->query($sql_stats_ti)->fetch_assoc();

    // Busca apenas os NOVOS chamados que ainda não estão na tela do TI
    $sql_novos = "SELECT t.id, t.motivo_chamado, u.nome_completo as nome_solicitante FROM tickets t JOIN usuarios u ON t.id_solicitante = u.id WHERE t.id_agente_atribuido IS NULL AND t.id_status = 1 AND t.id > ? ORDER BY t.data_criacao DESC";
    $stmt_novos = $conexao->prepare($sql_novos);
    $stmt_novos->bind_param("i", $ultimo_id);
    $stmt_novos->execute();
    $response_data['novos_chamados'] = $stmt_novos->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_novos->close();

    // Busca os chamados ativos do agente de TI
    $sql_meus_ativos = "SELECT t.id, t.motivo_chamado, s.nome as nome_status FROM tickets t JOIN status_tickets s ON t.id_status = s.id WHERE t.id_agente_atribuido = ? AND s.nome NOT IN ('Resolvido', 'Cancelado') ORDER BY t.data_ultima_atualizacao DESC LIMIT 5";
    $stmt_meus = $conexao->prepare($sql_meus_ativos);
    $stmt_meus->bind_param("i", $id_usuario_logado);
    $stmt_meus->execute();
    $response_data['meus_chamados_ativos'] = $stmt_meus->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_meus->close();

} else { // Se for colaborador
    // Busca estatísticas para o colaborador
    $sql_stats_colab = "SELECT (SELECT COUNT(id) FROM tickets WHERE id_solicitante = ? AND id_status NOT IN (5, 6)) AS meus_abertos, (SELECT COUNT(id) FROM tickets WHERE id_solicitante = ? AND id_status = 5) AS meus_resolvidos FROM DUAL";
    $stmt_stats = $conexao->prepare($sql_stats_colab);
    $stmt_stats->bind_param("ii", $id_usuario_logado, $id_usuario_logado);
    $stmt_stats->execute();
    $response_data['estatisticas'] = $stmt_stats->get_result()->fetch_assoc();
    $stmt_stats->close();
    
    // Busca os últimos chamados do colaborador
    $sql_ultimos_colab = "SELECT t.id, t.motivo_chamado, s.nome as nome_status FROM tickets t JOIN status_tickets s ON t.id_status = s.id WHERE t.id_solicitante = ? ORDER BY t.data_ultima_atualizacao DESC LIMIT 5";
    $stmt_chamados = $conexao->prepare($sql_ultimos_colab);
    $stmt_chamados->bind_param("i", $id_usuario_logado);
    $stmt_chamados->execute();
    $response_data['ultimos_chamados'] = $stmt_chamados->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_chamados->close();
}

$conexao->close();
echo json_encode($response_data);
exit();
?>