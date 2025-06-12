<?php
// processa_editar_artigo.php
session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

// Apenas utilizadores 'ti' podem executar esta ação
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 'ti') {
    $_SESSION['form_message_type'] = 'error';
    $_SESSION['form_message'] = "Acesso negado.";
    header("Location: admin_kb.php");
    exit();
}

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Coletar e validar os dados do formulário
    $id_artigo = filter_input(INPUT_POST, 'id_artigo', FILTER_VALIDATE_INT);
    $titulo = trim($_POST['titulo']);
    $conteudo = trim($_POST['conteudo']);
    $id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
    $id_editor = $_SESSION['usuario_id']; // Guarda quem fez a última edição

    // Validação básica
    if (!$id_artigo || empty($titulo) || empty($conteudo) || !$id_categoria) {
        $_SESSION['form_message_type'] = 'error';
        $_SESSION['form_message'] = "Erro: Todos os campos são obrigatórios.";
        header("Location: editar_artigo.php?id=" . $id_artigo);
        exit();
    }

    // 2. Atualizar o artigo no banco de dados
    // A coluna data_ultima_atualizacao será atualizada automaticamente pelo MySQL
    $sql_update = "UPDATE kb_artigos SET titulo = ?, conteudo = ?, id_categoria = ?, id_autor = ? WHERE id = ?";
    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->bind_param("ssiii", $titulo, $conteudo, $id_categoria, $id_editor, $id_artigo);
    
    if ($stmt_update->execute()) {
        // Sucesso!
        $_SESSION['form_message_type'] = 'success';
        $_SESSION['form_message'] = "Artigo '" . htmlspecialchars($titulo) . "' atualizado com sucesso!";
        header("Location: admin_kb.php");
        exit();
    } else {
        // Falha na atualização
        $_SESSION['form_message_type'] = 'error';
        $_SESSION['form_message'] = "Erro ao salvar as alterações.";
        header("Location: editar_artigo.php?id=" . $id_artigo);
        exit();
    }

    $stmt_update->close();
    $conexao->close();

} else {
    header("Location: admin_kb.php");
    exit();
}
?>
