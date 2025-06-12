<?php
// verificar_updates.php - VERSÃO FINAL UNIFICADA

require_once __DIR__ . '/config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Sessão inválida.']);
    exit();
}

$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario = $_SESSION['usuario_tipo'];
$resposta = ['tipo_usuario' => $tipo_usuario];

// --- DADOS COMUNS A TODOS OS UTILIZADORES ---
// 1. Contar notificações não lidas
$sql_notificacoes_count = "SELECT COUNT(id) AS total_nao_lidas FROM notificacoes WHERE id_usuario_destino = ? AND lida = FALSE";
$stmt_notificacoes_count = $conexao->prepare($sql_notificacoes_count);
if ($stmt_notificacoes_count) {
    $stmt_notificacoes_count->bind_param("i", $id_usuario_logado);
    $stmt_notificacoes_count->execute();
    $resposta['notificacoes_nao_lidas'] = (int) $stmt_notificacoes_count->get_result()->fetch_assoc()['total_nao_lidas'];
    $stmt_notificacoes_count->close();
}

// 2. Buscar as 5 últimas notificações para o dropdown
$sql_notificacoes_list = "SELECT id, id_ticket, mensagem, data_criacao FROM notificacoes WHERE id_usuario_destino = ? ORDER BY data_criacao DESC LIMIT 5";
$stmt_notificacoes_list = $conexao->prepare($sql_notificacoes_list);
if ($stmt_notificacoes_list) {
    $stmt_notificacoes_list->bind_param("i", $id_usuario_logado);
    $stmt_notificacoes_list->execute();
    $resposta['lista_notificacoes'] = $stmt_notificacoes_list->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_notificacoes_list->close();
}


// --- ROTEAMENTO DE CONTEXTO ---
if (isset($_GET['id_chamado']) && is_numeric($_GET['id_chamado'])) {
    // << NOVO >> CONTEXTO: PÁGINA DE DETALHES DO CHAMADO
    $id_chamado_atual = intval($_GET['id_chamado']);

    // 1. Busca os detalhes principais do chamado para atualizar o status, agente, etc.
    $sql_detalhes = "SELECT t.id_status, t.data_ultima_atualizacao, s.nome AS nome_status, a.nome_completo AS nome_agente FROM tickets t JOIN status_tickets s ON t.id_status = s.id LEFT JOIN usuarios a ON t.id_agente_atribuido = a.id WHERE t.id = ?";
    $stmt_detalhes = $conexao->prepare($sql_detalhes);
    if($stmt_detalhes){
        $stmt_detalhes->bind_param("i", $id_chamado_atual);
        $stmt_detalhes->execute();
        $resposta['detalhes_chamado'] = $stmt_detalhes->get_result()->fetch_assoc();
        $stmt_detalhes->close();
    }

    // 2. Busca os comentários do chamado
    $sql_comentarios = "SELECT c.*, u.nome_completo AS nome_usuario FROM comentarios_tickets AS c JOIN usuarios AS u ON c.id_usuario = u.id WHERE c.id_ticket = ? ORDER BY c.data_comentario ASC";
    $stmt_comentarios = $conexao->prepare($sql_comentarios);
    if($stmt_comentarios) {
        $stmt_comentarios->bind_param("i", $id_chamado_atual);
        $stmt_comentarios->execute();
        $resposta['comentarios'] = $stmt_comentarios->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_comentarios->close();
    }

} else {
    // CONTEXTO: PÁGINAS DE PAINEL (painel.php, meus_chamados.php)
    if ($tipo_usuario == 'ti') {
        // --- LÓGICA PARA O PAINEL DE TI ---
        $ultimo_id_conhecido = isset($_GET['ultimo_id']) ? intval($_GET['ultimo_id']) : 0;
        $sql_novos_chamados = "SELECT t.id, t.motivo_chamado, u.nome_completo as nome_solicitante FROM tickets t JOIN usuarios u ON t.id_solicitante = u.id WHERE t.id_agente_atribuido IS NULL AND t.id_status = 1 AND t.id > ? ORDER BY t.id ASC";
        $stmt_novos = $conexao->prepare($sql_novos_chamados);
        $resposta['novos_chamados'] = [];
        if ($stmt_novos) {
            $stmt_novos->bind_param("i", $ultimo_id_conhecido);
            $stmt_novos->execute();
            $resposta['novos_chamados'] = $stmt_novos->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_novos->close();
        }
        
        $sql_stats = "SELECT (SELECT COUNT(id) FROM tickets WHERE id_status = 1) AS abertos, (SELECT COUNT(id) FROM tickets WHERE id_status = 2) AS andamento, (SELECT COUNT(id) FROM tickets WHERE id_status = 5) AS resolvidos_total FROM DUAL";
        $resposta['estatisticas'] = $conexao->query($sql_stats)->fetch_assoc();

        $sql_meus_ativos = "SELECT t.id, t.motivo_chamado, s.nome as nome_status FROM tickets t JOIN status_tickets s ON t.id_status = s.id WHERE t.id_agente_atribuido = ? AND s.nome NOT IN (5, 6) ORDER BY t.data_ultima_atualizacao DESC LIMIT 5";
        $stmt_meus = $conexao->prepare($sql_meus_ativos);
        $resposta['meus_chamados_ativos'] = [];
        if ($stmt_meus) {
            $stmt_meus->bind_param("i", $id_usuario_logado);
            $stmt_meus->execute();
            $resposta['meus_chamados_ativos'] = $stmt_meus->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_meus->close();
        }

    } else {
        // --- LÓGICA PARA O PAINEL DO COLABORADOR ---
        $sql_stats_colab = "SELECT (SELECT COUNT(id) FROM tickets WHERE id_solicitante = ? AND id_status NOT IN (5, 6)) AS meus_abertos, (SELECT COUNT(id) FROM tickets WHERE id_solicitante = ? AND id_status = 5) AS meus_resolvidos FROM DUAL";
        $stmt_stats = $conexao->prepare($sql_stats_colab);
        $resposta['estatisticas'] = [];
        if ($stmt_stats) {
            $stmt_stats->bind_param("ii", $id_usuario_logado, $id_usuario_logado);
            $stmt_stats->execute();
            $resposta['estatisticas'] = $stmt_stats->get_result()->fetch_assoc();
            $stmt_stats->close();
        }

        $sql_ultimos_colab = "SELECT t.id, t.motivo_chamado, s.nome as nome_status FROM tickets t JOIN status_tickets s ON t.id_status = s.id WHERE t.id_solicitante = ? ORDER BY t.data_ultima_atualizacao DESC LIMIT 5";
        $stmt_chamados = $conexao->prepare($sql_ultimos_colab);
        $resposta['ultimos_chamados'] = [];
        if ($stmt_chamados) {
            $stmt_chamados->bind_param("i", $id_usuario_logado);
            $stmt_chamados->execute();
            $resposta['ultimos_chamados'] = $stmt_chamados->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_chamados->close();
        }
    }
}


$conexao->close();
header('Content-Type: application/json');
echo json_encode($resposta);
?>
