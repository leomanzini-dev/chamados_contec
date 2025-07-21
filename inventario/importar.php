<?php
// inventario/importar.php (VERSÃO COM NOME DO ARQUIVO CORRIGIDO)

// Aumenta o tempo máximo de execução para evitar que o script pare no meio da importação
set_time_limit(300); 

// Ajusta os caminhos para voltar um diretório (..) e encontrar os arquivos de configuração
require_once __DIR__ . '/../config.php';
require_once PROJECT_ROOT_PATH . '/conexao.php';

echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Importação de Inventário</title>";
echo "<style>body { font-family: sans-serif; line-height: 1.6; padding: 20px; background-color: #f4f4f9; color: #333; } .log { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px; background-color: #fff; } .success { color: #28a745; font-weight: bold; } .error { color: #dc3545; font-weight: bold; } .summary { font-weight: bold; font-size: 1.2rem; margin-top: 20px; padding: 15px; background-color: #e9ecef; border-radius: 5px;} h1 { color: #284B63; }</style>";
echo "</head><body><h1>Iniciando Importação do Inventário de TI</h1>";

// O nome correto do arquivo que o nosso teste encontrou
$nome_arquivo_correto = 'Inventario de TI.xlsx';
$caminho_csv = __DIR__ . '/' . $nome_arquivo_correto;

if (!file_exists($caminho_csv)) {
    die("<div class='log error'>ERRO: Arquivo CSV não encontrado. Verifique se o arquivo <strong>" . htmlspecialchars($nome_arquivo_correto) . "</strong> está dentro da pasta <strong>inventario</strong>.</div></body></html>");
}

$arquivo = fopen($caminho_csv, 'r');
if (!$arquivo) {
    die("<div class='log error'>ERRO: Não foi possível abrir o arquivo CSV.</div></body></html>");
}

// Pula a primeira linha (cabeçalho) do arquivo CSV
fgetcsv($arquivo); 

$total_linhas = 0;
$total_inseridos = 0;
$total_erros = 0;

$conexao->begin_transaction();

try {
    // Prepara a consulta de inserção uma única vez para melhor performance
    $sql_insert = "INSERT INTO inventario 
        (nome_ativo, tipo_dispositivo, fabricante, modelo, data_aquisicao, localizacao, id_usuario_responsavel, status, processador, memoria_ram_gb, armazenamento_gb, sistema_operacional) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conexao->prepare($sql_insert);

    // Loop para ler cada linha do CSV
    while (($linha = fgetcsv($arquivo)) !== FALSE) {
        $total_linhas++;
        
        // Verifica se a linha não está completamente vazia
        if (empty(implode('', $linha))) {
            continue;
        }

        echo "<div class='log'>Processando linha #{$total_linhas}: " . htmlspecialchars($linha[0] ?? 'Vazio') . "... ";

        // Limpa e formata os dados da planilha
        $nome_ativo = trim($linha[0] ?? '');
        if (empty($nome_ativo)) {
            echo "<span class='error'>Nome do ativo vazio. Pulando.</span></div>";
            $total_erros++;
            continue;
        }

        $tipo_dispositivo = trim($linha[1] ?? '');
        $fabricante = trim($linha[2] ?? '');
        $modelo = trim($linha[3] ?? '');
        $data_aquisicao_str = trim($linha[5] ?? '');
        $data_aquisicao = !empty($data_aquisicao_str) ? date('Y-m-d', strtotime(str_replace('/', '-', $data_aquisicao_str))) : null;
        $localizacao = trim($linha[10] ?? '');
        $nome_responsavel = trim($linha[23] ?? '');
        $status = trim($linha[24] ?? 'Disponível');
        if (empty($status)) { $status = 'Disponível'; }
        $processador = trim($linha[11] ?? '');
        $memoria_ram = !empty(trim($linha[12] ?? '')) ? intval($linha[12]) : null;
        $armazenamento = !empty(trim($linha[13] ?? '')) ? intval($linha[13]) : null;
        $so = trim($linha[17] ?? '');

        // Busca o ID do usuário responsável pelo nome
        $id_usuario = null;
        if (!empty($nome_responsavel)) {
            $stmt_user = $conexao->prepare("SELECT id FROM usuarios WHERE nome_completo = ? LIMIT 1");
            $stmt_user->bind_param("s", $nome_responsavel);
            $stmt_user->execute();
            $resultado_user = $stmt_user->get_result();
            if ($resultado_user->num_rows > 0) {
                $id_usuario = $resultado_user->fetch_assoc()['id'];
            }
            $stmt_user->close();
        }

        // Insere os dados no banco
        $stmt_insert->bind_param("ssssssisssis", 
            $nome_ativo, $tipo_dispositivo, $fabricante, $modelo, $data_aquisicao, $localizacao, $id_usuario, $status, $processador, $memoria_ram, $armazenamento, $so
        );
        
        if ($stmt_insert->execute()) {
            echo "<span class='success'>Inserido com sucesso!</span></div>";
            $total_inseridos++;
        } else {
            echo "<span class='error'>Falha ao inserir: " . htmlspecialchars($stmt_insert->error) . "</span></div>";
            $total_erros++;
        }
    }
    
    $conexao->commit();
    echo "<div class='summary success'>Importação concluída!</div>";

} catch (Exception $e) {
    $conexao->rollback();
    echo "<div class='log error'>ERRO CRÍTICO DURANTE A TRANSAÇÃO: " . $e->getMessage() . ". Nenhuma alteração foi salva.</div>";
}

fclose($arquivo);
$stmt_insert->close();
$conexao->close();

echo "<div class='summary'><strong>Resumo:</strong> {$total_linhas} linhas processadas. {$total_inseridos} itens inseridos. {$total_erros} erros.</div>";
echo "</body></html>";

?>