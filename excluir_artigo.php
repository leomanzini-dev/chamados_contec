<?php
// excluir_artigo.php (VERSÃO ATUALIZADA PARA RECEBER POST)
require_once 'includes/header.php';

// 1. VERIFICAR PERMISSÃO
if ($tipo_usuario != 'ti') {
    $_SESSION['mensagem_erro'] = "Acesso negado. Você não tem permissão para excluir artigos.";
    header("Location: admin_kb.php");
    exit();
}

// 2. VALIDAR O ID (MUDANÇA DE GET PARA POST)
$id_artigo_excluir = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id_artigo_excluir) {
    $_SESSION['mensagem_erro'] = "ID de artigo inválido.";
    header("Location: admin_kb.php");
    exit();
}

// 3. LÓGICA DE EXCLUSÃO
$sql = "DELETE FROM kb_artigos WHERE id = ?";
$stmt = $conexao->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $id_artigo_excluir);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['mensagem_sucesso'] = "Artigo excluído com sucesso!";
        } else {
            $_SESSION['mensagem_erro'] = "Artigo não encontrado ou já foi excluído.";
        }
    } else {
        $_SESSION['mensagem_erro'] = "Erro ao excluir o artigo.";
    }
    $stmt->close();
} else {
    $_SESSION['mensagem_erro'] = "Ocorreu um erro no sistema. Tente novamente mais tarde.";
}

$conexao->close();

// 4. REDIRECIONAR
header("Location: admin_kb.php");
exit();