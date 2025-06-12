<?php
// exportar_relatorio.php
session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

// Apenas usuários 'ti' podem exportar dados
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 'ti') {
    die("Acesso negado.");
}

// Futuramente, podemos adicionar mais tipos de relatórios aqui
$tipo_relatorio = $_GET['relatorio'] ?? 'chamados_geral';

if ($tipo_relatorio == 'chamados_geral') {
    
    // Nome do arquivo que será baixado
    $nome_arquivo = "relatorio_chamados_" . date('Y-m-d') . ".csv";

    // Estes cabeçalhos (headers) dizem ao navegador que ele deve baixar um arquivo
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $nome_arquivo);

    // Abre um fluxo de saída para escrever diretamente na resposta do navegador
    $output = fopen('php://output', 'w');

    // ---- CORREÇÃO AQUI: Adiciona o BOM para o Excel entender o UTF-8 ----
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Escreve a linha de cabeçalho da tabela (os títulos das colunas)
    // Usando ';' como separador, que é mais compatível com o Excel em português
    fputcsv($output, [
        'ID Chamado', 'Assunto', 'Descricao', 'Solicitante', 'Email Solicitante', 
        'Departamento', 'Agente Atribuido', 'Categoria', 'Prioridade', 'Status', 
        'Data Criacao', 'Hora Criacao', 
        'Data Ultima Atualizacao', 'Hora Ultima Atualizacao',
        'Data Resolucao', 'Hora Resolucao'
    ], ';');

    // Consulta SQL para buscar todos os dados detalhados dos chamados
    $sql = "SELECT 
                t.id, 
                t.motivo_chamado, 
                t.descricao_detalhada,
                solicitante.nome_completo AS nome_solicitante, 
                solicitante.email AS email_solicitante, 
                solicitante.departamento,
                agente.nome_completo AS nome_agente,
                c.nome AS nome_categoria,
                p.nome AS nome_prioridade,
                s.nome AS nome_status,
                DATE(t.data_criacao) AS data_criacao_data,
                TIME(t.data_criacao) AS data_criacao_hora,
                DATE(t.data_ultima_atualizacao) AS data_ultima_atualizacao_data,
                TIME(t.data_ultima_atualizacao) AS data_ultima_atualizacao_hora,
                DATE(t.data_resolucao_efetiva) AS data_resolucao_data,
                TIME(t.data_resolucao_efetiva) AS data_resolucao_hora
            FROM tickets AS t
            JOIN usuarios AS solicitante ON t.id_solicitante = solicitante.id
            LEFT JOIN usuarios AS agente ON t.id_agente_atribuido = agente.id
            JOIN categorias AS c ON t.id_categoria = c.id
            JOIN prioridades AS p ON t.id_prioridade = p.id
            JOIN status_tickets AS s ON t.id_status = s.id
            ORDER BY t.id ASC";
    
    $resultado = $conexao->query($sql);

    if ($resultado && $resultado->num_rows > 0) {
        // Percorre cada linha do resultado e a escreve no arquivo CSV
        while ($linha = $resultado->fetch_assoc()) {
            fputcsv($output, $linha, ';');
        }
    }

    fclose($output);
    $conexao->close();
    exit();
}
?>
