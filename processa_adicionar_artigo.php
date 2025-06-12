<?php
// processa_adicionar_artigo.php
session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

// Apenas usuários 'ti' podem executar esta ação
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 'ti') {
    $_SESSION['form_message_type'] = 'error';
    $_SESSION['form_message'] = "Acesso negado.";
    header("Location: admin_kb.php");
    exit();
}

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Coletar e validar os dados do formulário
    $titulo = trim($_POST['titulo']);
    $conteudo = trim($_POST['conteudo']); // O HTML será permitido, mas sanitizado na exibição
    $id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
    $id_autor = $_SESSION['usuario_id']; // O autor é o usuário de TI logado

    // Validação básica
    if (empty($titulo) || empty($conteudo) || !$id_categoria) {
        $_SESSION['form_message_type'] = 'error';
        $_SESSION['form_message'] = "Erro: Título, conteúdo e categoria são obrigatórios.";
        header("Location: adicionar_artigo.php");
        exit();
    }

    // 2. Inserir o novo artigo no banco de dados
    $sql_insert = "INSERT INTO kb_artigos (titulo, conteudo, id_categoria, id_autor) VALUES (?, ?, ?, ?)";
    $stmt_insert = $conexao->prepare($sql_insert);
    $stmt_insert->bind_param("ssii", $titulo, $conteudo, $id_categoria, $id_autor);
    
    if ($stmt_insert->execute()) {
        // Sucesso!
        $_SESSION['form_message_type'] = 'success';
        $_SESSION['form_message'] = "Artigo '" . htmlspecialchars($titulo) . "' adicionado com sucesso!";
        header("Location: admin_kb.php"); // Redireciona para a lista de artigos
        exit();
    } else {
        // Falha na inserção
        $_SESSION['form_message_type'] = 'error';
        $_SESSION['form_message'] = "Erro ao salvar o artigo no banco de dados.";
        header("Location: adicionar_artigo.php");
        exit();
    }

    $stmt_insert->close();
    $conexao->close();

} else {
    // Se não for POST, redireciona para a página principal do admin da KB
    header("Location: admin_kb.php");
    exit();
}
?>
