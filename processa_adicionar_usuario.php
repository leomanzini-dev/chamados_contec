<?php
// processa_adicionar_usuario.php (VERSÃO FINAL E CORRIGIDA)

session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

// Apenas usuários 'ti' podem executar esta ação
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 'ti') {
    $_SESSION['mensagem_erro'] = "Acesso negado.";
    header("Location: admin_usuarios.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Coletar e validar os dados do formulário
    $nome_completo = trim($_POST['nome_completo']);
    $email = trim($_POST['email']);
    $departamento = trim($_POST['departamento']);
    $senha = $_POST['senha'];
    $tipo_usuario = $_POST['tipo_usuario'];
    
    // CORREÇÃO: Garante que 'ativo' seja 0 se não for enviado, ou 1 se for.
    $ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 0;

    // Validação básica
    if (empty($nome_completo) || empty($email) || empty($senha) || empty($tipo_usuario)) {
        $_SESSION['mensagem_erro'] = "Erro: Nome, e-mail, senha e tipo de usuário são obrigatórios.";
        header("Location: adicionar_usuario.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensagem_erro'] = "Erro: Formato de e-mail inválido.";
        header("Location: adicionar_usuario.php");
        exit();
    }

    // 2. Verificar se o e-mail já existe
    $sql_check_email = "SELECT id FROM usuarios WHERE email = ? LIMIT 1";
    $stmt_check = $conexao->prepare($sql_check_email);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $resultado_check = $stmt_check->get_result();
    
    if ($resultado_check->num_rows > 0) {
        $_SESSION['mensagem_erro'] = "Erro: Este e-mail já está cadastrado.";
        $stmt_check->close();
        header("Location: adicionar_usuario.php");
        exit();
    }
    $stmt_check->close();

    // 3. Criptografar a senha (HASH)
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // 4. Inserir o novo usuário no banco de dados
    $sql_insert = "INSERT INTO usuarios (nome_completo, email, departamento, senha, tipo_usuario, ativo) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conexao->prepare($sql_insert);
    $stmt_insert->bind_param("sssssi", $nome_completo, $email, $departamento, $senha_hash, $tipo_usuario, $ativo);
    
    if ($stmt_insert->execute()) {
        $_SESSION['mensagem_sucesso'] = "Usuário '" . htmlspecialchars($nome_completo) . "' adicionado com sucesso!";
    } else {
        $_SESSION['mensagem_erro'] = "Erro ao salvar o usuário no banco de dados.";
        error_log("Erro em processa_adicionar_usuario: " . $stmt_insert->error);
    }

    $stmt_insert->close();
    $conexao->close();

    header("Location: admin_usuarios.php");
    exit();

} else {
    header("Location: admin_usuarios.php");
    exit();
}
?>