<?php
// processa_adicionar_usuario.php
session_start();
// Usando o config para ter um caminho seguro para a conexão
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

// Apenas usuários 'ti' podem executar esta ação
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 'ti') {
    // Define uma mensagem de erro na sessão
    $_SESSION['form_message_type'] = 'error';
    $_SESSION['form_message'] = "Acesso negado.";
    // Redireciona para o painel de administração de usuários
    header("Location: admin_usuarios.php");
    exit();
}

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Coletar e validar os dados do formulário
    $nome_completo = trim($_POST['nome_completo']);
    $email = trim($_POST['email']);
    $departamento = trim($_POST['departamento']);
    $senha = $_POST['senha']; // A senha não precisa de trim
    $tipo_usuario = $_POST['tipo_usuario'];
    $ativo = filter_input(INPUT_POST, 'ativo', FILTER_VALIDATE_INT);

    // Validação básica
    if (empty($nome_completo) || empty($email) || empty($senha) || empty($tipo_usuario)) {
        $_SESSION['form_message_type'] = 'error';
        $_SESSION['form_message'] = "Erro: Nome, e-mail, senha e tipo de usuário são obrigatórios.";
        header("Location: adicionar_usuario.php"); // Volta para o formulário
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['form_message_type'] = 'error';
        $_SESSION['form_message'] = "Erro: Formato de e-mail inválido.";
        header("Location: adicionar_usuario.php");
        exit();
    }

    // 2. Verificar se o e-mail já existe no banco de dados
    $sql_check_email = "SELECT id FROM usuarios WHERE email = ? LIMIT 1";
    $stmt_check = $conexao->prepare($sql_check_email);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $resultado_check = $stmt_check->get_result();
    
    if ($resultado_check->num_rows > 0) {
        $_SESSION['form_message_type'] = 'error';
        $_SESSION['form_message'] = "Erro: Este e-mail já está cadastrado.";
        $stmt_check->close();
        header("Location: adicionar_usuario.php");
        exit();
    }
    $stmt_check->close();

    // 3. Criptografar a senha (HASH) - Passo de segurança crucial!
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // 4. Inserir o novo usuário no banco de dados
    $sql_insert = "INSERT INTO usuarios (nome_completo, email, departamento, senha, tipo_usuario, ativo) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conexao->prepare($sql_insert);
    // s = string, i = integer
    $stmt_insert->bind_param("sssssi", $nome_completo, $email, $departamento, $senha_hash, $tipo_usuario, $ativo);
    
    if ($stmt_insert->execute()) {
        // Sucesso!
        $_SESSION['form_message_type'] = 'success';
        $_SESSION['form_message'] = "Usuário '" . htmlspecialchars($nome_completo) . "' adicionado com sucesso!";
        header("Location: admin_usuarios.php"); // Redireciona para a lista de usuários
        exit();
    } else {
        // Falha na inserção
        $_SESSION['form_message_type'] = 'error';
        $_SESSION['form_message'] = "Erro ao salvar o usuário no banco de dados.";
        // Em produção, logar o erro: error_log($stmt_insert->error);
        header("Location: adicionar_usuario.php");
        exit();
    }

    $stmt_insert->close();
    $conexao->close();

} else {
    // Se não for POST, redireciona para a página principal do admin
    header("Location: admin_usuarios.php");
    exit();
}
?>