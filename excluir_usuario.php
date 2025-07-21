<?php
// excluir_usuario.php (VERSÃO ATUALIZADA COM EXCLUSÃO DE CHAMADOS)

session_start();
require_once 'includes/header.php'; // seu arquivo de conexão e sessão

// 1. VERIFICAR PERMISSÃO (só TI pode excluir usuário)
if ($tipo_usuario != 'ti') {
    $_SESSION['mensagem_erro'] = "Acesso negado. Você não tem permissão para realizar esta ação.";
    header("Location: admin_usuarios.php");
    exit();
}

// 2. VALIDAR O ID (POST)
$id_usuario_excluir = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id_usuario_excluir) {
    $_SESSION['mensagem_erro'] = "ID de usuário inválido.";
    header("Location: admin_usuarios.php");
    exit();
}

// 3. IMPEDIR AUTO-EXCLUSÃO
if ($id_usuario_excluir == $_SESSION['usuario_id']) {
    $_SESSION['mensagem_erro'] = "Ação inválida. Você não pode excluir sua própria conta.";
    header("Location: admin_usuarios.php");
    exit();
}

// 4. EXCLUSÃO COM TRANSAÇÃO
try {
    $conexao->begin_transaction();

    // 4.1 Excluir chamados do usuário
    $sql_excluir_chamados = "DELETE FROM tickets WHERE id_solicitante = ?";
    $stmt_chamados = $conexao->prepare($sql_excluir_chamados);
    $stmt_chamados->bind_param("i", $id_usuario_excluir);
    $stmt_chamados->execute();
    $stmt_chamados->close();

    // 4.2 Excluir usuário
    $sql_excluir_usuario = "DELETE FROM usuarios WHERE id = ?";
    $stmt_usuario = $conexao->prepare($sql_excluir_usuario);
    $stmt_usuario->bind_param("i", $id_usuario_excluir);
    $stmt_usuario->execute();

    if ($stmt_usuario->affected_rows > 0) {
        $_SESSION['mensagem_sucesso'] = "Usuário e seus chamados excluídos com sucesso!";
    } else {
        $_SESSION['mensagem_erro'] = "Usuário não encontrado ou já foi excluído.";
    }

    $stmt_usuario->close();

    $conexao->commit();

} catch (Exception $e) {
    $conexao->rollback();
    $_SESSION['mensagem_erro'] = "Erro ao excluir o usuário: " . $e->getMessage();
}

$conexao->close();

// 5. REDIRECIONAR
header("Location: admin_usuarios.php");
exit();
