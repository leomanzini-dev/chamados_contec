<?php
// processa_editar_usuario.php (VERSÃO FINAL E CORRIGIDA)
session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 'ti') {
    $_SESSION['mensagem_erro'] = "Acesso negado.";
    header("Location: admin_usuarios.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
    $nome_completo = trim($_POST['nome_completo']);
    $email = trim($_POST['email']);
    $departamento = trim($_POST['departamento']);
    $nova_senha = $_POST['senha'];
    $tipo_usuario = $_POST['tipo_usuario'];
    $ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 0;

    if (!$id_usuario || empty($nome_completo) || empty($email) || empty($tipo_usuario)) {
        $_SESSION['mensagem_erro'] = "Erro: Nome, e-mail e tipo de usuário são obrigatórios.";
        header("Location: editar_usuario.php?id=" . $id_usuario);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensagem_erro'] = "Erro: Formato de e-mail inválido.";
        header("Location: editar_usuario.php?id=" . $id_usuario);
        exit();
    }

    $sql_check_email = "SELECT id FROM usuarios WHERE email = ? AND id != ? LIMIT 1";
    $stmt_check = $conexao->prepare($sql_check_email);
    $stmt_check->bind_param("si", $email, $id_usuario);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['mensagem_erro'] = "Erro: Este e-mail já está em uso por outro usuário.";
        $stmt_check->close();
        header("Location: editar_usuario.php?id=" . $id_usuario);
        exit();
    }
    $stmt_check->close();

    $sql_update = "UPDATE usuarios SET nome_completo = ?, email = ?, departamento = ?, tipo_usuario = ?, ativo = ?";
    $params = [$nome_completo, $email, $departamento, $tipo_usuario, $ativo];
    $types = "ssssi";

    if (!empty($nova_senha)) {
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $sql_update .= ", senha = ?";
        $params[] = $senha_hash;
        $types .= "s";
    }

    $sql_update .= " WHERE id = ?";
    $params[] = $id_usuario;
    $types .= "i";

    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->bind_param($types, ...$params);

    if ($stmt_update->execute()) {
        $_SESSION['mensagem_sucesso'] = "Usuário '" . htmlspecialchars($nome_completo) . "' atualizado com sucesso!";
    } else {
        $_SESSION['mensagem_erro'] = "Erro ao atualizar o usuário.";
        error_log("Erro em processa_editar_usuario: " . $stmt_update->error);
    }
    
    $stmt_update->close();
    $conexao->close();

} else {
    $_SESSION['mensagem_erro'] = "Requisição inválida.";
}

header("Location: admin_usuarios.php");
exit();
?>