<?php
// processa_login.php
session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

header('Content-Type: application/json');

function enviar_resposta_json($success, $message = '') {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $senha_digitada = trim($_POST['senha']);

    if (empty($email) || empty($senha_digitada)) {
        enviar_resposta_json(false, "Por favor, preencha o e-mail e a senha.");
    }

    $sql = "SELECT id, nome_completo, email, senha, tipo_usuario, ativo FROM usuarios WHERE email = ?";
    if ($stmt = $conexao->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows == 1) {
            $usuario = $resultado->fetch_assoc();

            if ($usuario['ativo'] == 0) {
                enviar_resposta_json(false, "Esta conta de usuário está desativada.");
            }

            if (password_verify($senha_digitada, $usuario['senha'])) {
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome_completo'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_tipo'] = $usuario['tipo_usuario'];
                enviar_resposta_json(true);
            } else {
                enviar_resposta_json(false, "E-mail ou senha inválidos.");
            }
        } else {
            enviar_resposta_json(false, "E-mail ou senha inválidos.");
        }
        $stmt->close();
    } else {
        enviar_resposta_json(false, "Erro no sistema. Tente novamente.");
    }
    $conexao->close();
} else {
    enviar_resposta_json(false, "Método inválido.");
}
?>
