<?php
// excluir_chamado.php
session_start();
require_once 'config.php';
require_once 'conexao.php';

// 1. Verifica se o usuário tem permissão (somente TI pode excluir)
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'ti') {
    $_SESSION['mensagem_erro'] = "Acesso negado. Você não tem permissão para realizar esta ação.";
    header("Location: gerenciar_chamados.php");
    exit();
}

// 2. Recebe e valida o ID do chamado via POST
$id_chamado = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id_chamado) {
    $_SESSION['mensagem_erro'] = "ID do chamado inválido.";
    header("Location: gerenciar_chamados.php");
    exit();
}

try {
    // Começa transação para garantir atomicidade
    $conexao->begin_transaction();

    // 3. Deleta anexos associados (apagar arquivos do servidor também)
    $sqlAnexos = "SELECT caminho_arquivo FROM anexos_tickets WHERE id_ticket = ?";
    $stmtAnexos = $conexao->prepare($sqlAnexos);
    $stmtAnexos->bind_param("i", $id_chamado);
    $stmtAnexos->execute();
    $resultAnexos = $stmtAnexos->get_result();
    while ($anexo = $resultAnexos->fetch_assoc()) {
        $caminhoArquivo = __DIR__ . '/' . $anexo['caminho_arquivo'];
        if (file_exists($caminhoArquivo)) {
            unlink($caminhoArquivo); // apaga o arquivo físico
        }
    }
    $stmtAnexos->close();

    $sqlDeleteAnexos = "DELETE FROM anexos_tickets WHERE id_ticket = ?";
    $stmtDeleteAnexos = $conexao->prepare($sqlDeleteAnexos);
    $stmtDeleteAnexos->bind_param("i", $id_chamado);
    $stmtDeleteAnexos->execute();
    $stmtDeleteAnexos->close();

    // 4. Deleta comentários associados
    $sqlDeleteComentarios = "DELETE FROM comentarios_tickets WHERE id_ticket = ?";
    $stmtDeleteComentarios = $conexao->prepare($sqlDeleteComentarios);
    $stmtDeleteComentarios->bind_param("i", $id_chamado);
    $stmtDeleteComentarios->execute();
    $stmtDeleteComentarios->close();

    // 5. Deleta o chamado
    $sqlDeleteChamado = "DELETE FROM tickets WHERE id = ?";
    $stmtDeleteChamado = $conexao->prepare($sqlDeleteChamado);
    $stmtDeleteChamado->bind_param("i", $id_chamado);
    $stmtDeleteChamado->execute();

    if ($stmtDeleteChamado->affected_rows === 0) {
        throw new Exception("Chamado não encontrado ou já excluído.");
    }

    $stmtDeleteChamado->close();

    // 6. Commit da transação
    $conexao->commit();

    $_SESSION['mensagem_sucesso'] = "Chamado excluído com sucesso!";
} catch (Exception $e) {
    $conexao->rollback();
    error_log("Erro ao excluir chamado ID {$id_chamado}: " . $e->getMessage());
    $_SESSION['mensagem_erro'] = "Erro ao excluir chamado: " . $e->getMessage();
}

$conexao->close();

header("Location: gerenciar_chamados.php");
exit();
