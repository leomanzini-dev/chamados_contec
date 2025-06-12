<?php
// buscar_artigos.php
session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

// Define o cabeçalho para retornar JSON
header('Content-Type: application/json');

// 1. Valida se o utilizador está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([]); // Retorna um array vazio se não estiver logado
    exit();
}

// 2. Valida e sanitiza o termo de busca vindo da URL
$termo_busca = '';
if (isset($_GET['termo'])) {
    // Remove tags HTML e espaços em branco das pontas
    $termo_busca = trim(strip_tags($_GET['termo']));
}

// 3. Condição para executar a busca (termo precisa de ter pelo menos 3 caracteres)
if (mb_strlen($termo_busca, 'UTF-8') < 3) {
    echo json_encode([]); // Retorna vazio se o termo for muito curto
    exit();
}

// 4. Prepara a consulta SQL para buscar APENAS no título do artigo
$sql = "SELECT id, titulo 
        FROM kb_artigos 
        WHERE titulo LIKE ? AND visivel_para = 'todos'
        LIMIT 5";

$stmt = $conexao->prepare($sql);

// Se a preparação da consulta falhar por algum motivo
if ($stmt === false) {
    error_log("Erro ao preparar a consulta de busca de artigos: " . $conexao->error);
    echo json_encode([]);
    exit();
}

// Adiciona os wildcards (%) para a busca LIKE
$termo_like = "%" . $termo_busca . "%";
// Agora só precisamos de um parâmetro, pois a busca é apenas no título
$stmt->bind_param("s", $termo_like);

$stmt->execute();
$resultado = $stmt->get_result();

$artigos = $resultado->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conexao->close();

// 5. Retorna os resultados encontrados (ou um array vazio se nada for encontrado)
echo json_encode($artigos);
