<?php
// processa_editar_usuario.php
session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

// Apenas usuários 'ti' podem executar esta ação
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 'ti') {
    $_SESSION['form_message_type'] = 'error';
    $_SESSION['form_message'] = "Acesso negado.";
    header("Location: admin_usuarios.php");
    exit();
}

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Coletar e validar os dados do formulário
    $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
    $nome_completo = trim($_POST['nome_completo']);
    $email = trim($_POST['email']);
    $departamento = trim($_POST['departamento']);
    $nova_senha = $_POST['senha']; // Senha vem sem trim
    $tipo_usuario_form = $_POST['tipo_usuario'];
    $ativo = filter_input(INPUT_POST, 'ativo', FILTER_VALIDATE_INT);

    // Validação básica
    if (!$id_usuario || empty($nome_completo) || empty($email) || empty($tipo_usuario_form)) {
        $_SESSION['form_message_type'] = 'error';
        $_SESSION['form_message'] = "Erro: Nome, e-mail e tipo de usuário são obrigatórios.";
        header("Location: editar_usuario.php?id=" . $id_usuario);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['form_message_type'] = 'error';
        $_SESSION['form_message'] = "Erro: Formato de e-mail inválido.";
        header("Location: editar_usuario.php?id=" . $id_usuario);
        exit();
    }

    // 2. Verificar se o e-mail já existe em OUTRO usuário
    $sql_check_email = "SELECT id FROM usuarios WHERE email = ? AND id != ? LIMIT 1";
    $stmt_check = $conexao->prepare($sql_check_email);
    $stmt_check->bind_param("si", $email, $id_usuario);
    $stmt_check->execute();
    $resultado_check = $stmt_check->get_result();
    
    if ($resultado_check->num_rows > 0) {
        $_SESSION['form_message_type'] = 'error';
        $_SESSION['form_message'] = "Erro: Este e-mail já está em uso por outro usuário.";
        $stmt_check->close();
        header("Location: editar_usuario.php?id=" . $id_usuario);
        exit();
    }
    $stmt_check->close();

    // 3. Preparar a consulta SQL de forma dinâmica
    $sql_update = "UPDATE usuarios SET nome_completo = ?, email = ?, departamento = ?, tipo_usuario = ?, ativo = ?";
    $params = [$nome_completo, $email, $departamento, $tipo_usuario_form, $ativo];
    $types = "ssssi";

    // Se uma nova senha foi digitada, adiciona a atualização de senha na consulta
    if (!empty($nova_senha)) {
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $sql_update .= ", senha = ?";
        $params[] = $senha_hash;
        $types .= "s";
    }

    $sql_update .= " WHERE id = ?";
    $params[] = $id_usuario;
    $types .= "i";

    // 4. Executar a atualização no banco de dados
    $stmt_update = $conexao->prepare($sql_update);
    // Usando o operador "splat" (...) para passar o array de parâmetros
    $stmt_update->bind_param($types, ...$params);

    if ($stmt_update->execute()) {
        // Sucesso!
        $_SESSION['form_message_type'] = 'success';
        $_SESSION['form_message'] = "Usuário '" . htmlspecialchars($nome_completo) . "' atualizado com sucesso!";
        header("Location: admin_usuarios.php");
        exit();
    } else {
        // Falha na atualização
        $_SESSION['form_message_type'] = 'error';
        $_SESSION['form_message'] = "Erro ao atualizar o usuário.";
        header("Location: editar_usuario.php?id=" . $id_usuario);
        exit();
    }

    $stmt_update->close();
    $conexao->close();

} else {
    // Se não for POST, redireciona
    header("Location: admin_usuarios.php");
    exit();
}
?>