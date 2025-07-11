<?php
// processa_excluir_chamado.php

session_start();
require_once 'config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

// Verificações de Segurança
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: painel.php");
    exit();
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 'ti') {
    $_SESSION['mensagem_erro'] = "Você não tem permissão para executar esta ação.";
    header("Location: painel.php");
    exit();
}

$id_chamado = filter_input(INPUT_POST, 'id_chamado', FILTER_VALIDATE_INT);
if (!$id_chamado) {
    $_SESSION['mensagem_erro'] = "ID do chamado inválido.";
    header("Location: gerenciar_chamados.php");
    exit();
}

$conexao->begin_transaction();

try {
    // 1. Encontrar e apagar os arquivos físicos dos anexos
    $sql_select_anexos = "SELECT caminho_arquivo FROM anexos_tickets WHERE id_ticket = ?";
    $stmt_select = $conexao->prepare($sql_select_anexos);
    $stmt_select->bind_param("i", $id_chamado);
    $stmt_select->execute();
    $resultado_anexos = $stmt_select->get_result();
    
    while ($anexo = $resultado_anexos->fetch_assoc()) {
        if (!empty($anexo['caminho_arquivo'])) {
            $caminho_fisico = PROJECT_ROOT_PATH . '/' . $anexo['caminho_arquivo'];
            if (file_exists($caminho_fisico)) {
                unlink($caminho_fisico); // Apaga o arquivo do disco
            }
        }
    }
    $stmt_select->close();

    // 2. Apagar os registros do banco de dados em cascata
    // (A ordem é importante para respeitar as chaves estrangeiras)
    
    // Apaga os anexos
    $stmt_delete_anexos = $conexao->prepare("DELETE FROM anexos_tickets WHERE id_ticket = ?");
    $stmt_delete_anexos->bind_param("i", $id_chamado);
    $stmt_delete_anexos->execute();
    $stmt_delete_anexos->close();

    // Apaga os comentários
    $stmt_delete_comentarios = $conexao->prepare("DELETE FROM comentarios_tickets WHERE id_ticket = ?");
    $stmt_delete_comentarios->bind_param("i", $id_chamado);
    $stmt_delete_comentarios->execute();
    $stmt_delete_comentarios->close();
    
    // Apaga as notificações
    $stmt_delete_notificacoes = $conexao->prepare("DELETE FROM notificacoes WHERE id_ticket = ?");
    $stmt_delete_notificacoes->bind_param("i", $id_chamado);
    $stmt_delete_notificacoes->execute();
    $stmt_delete_notificacoes->close();

    // Finalmente, apaga o ticket principal
    $stmt_delete_ticket = $conexao->prepare("DELETE FROM tickets WHERE id = ?");
    $stmt_delete_ticket->bind_param("i", $id_chamado);
    $stmt_delete_ticket->execute();
    
    if ($stmt_delete_ticket->affected_rows > 0) {
        // 3. Se tudo deu certo, confirma a transação
        $conexao->commit();
        $_SESSION['mensagem_sucesso'] = "Chamado #" . $id_chamado . " foi excluído permanentemente.";
    } else {
        // Se o chamado não foi encontrado para deletar, desfaz a transação
        throw new Exception("O ticket a ser excluído não foi encontrado.");
    }
    $stmt_delete_ticket->close();

} catch (Exception $e) {
    // 4. Se algo deu errado, desfaz tudo
    $conexao->rollback();
    error_log("Erro ao excluir chamado #" . $id_chamado . ": " . $e->getMessage());
    $_SESSION['mensagem_erro'] = "Ocorreu um erro no servidor ao tentar excluir o chamado.";
}

// 5. Redireciona para a lista de chamados
header("Location: gerenciar_chamados.php");
exit();