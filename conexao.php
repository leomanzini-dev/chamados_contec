<?php

// Definições do Banco de Dados
define('DB_SERVER', 'localhost'); // Geralmente 'localhost' para XAMPP
define('DB_USERNAME', 'root');    // Usuário padrão do XAMPP é 'root'
define('DB_PASSWORD', '');        // Senha padrão do XAMPP é vazia
define('DB_NAME', 'sistema_chamados_contec'); // O nome do seu banco de dados

// Tenta conectar ao banco de dados MySQL
$conexao = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verifica a conexão
if ($conexao->connect_error) {
    // Em um ambiente de produção, você não exibiria o erro detalhado para o usuário.
    // Você poderia logar o erro e mostrar uma mensagem genérica.
    die("Conexão falhou: " . $conexao->connect_error);
}

// Define o charset para UTF-8 (importante para acentuação e caracteres especiais)
if (!$conexao->set_charset("utf8mb4")) {
    //printf("Erro ao definir utf8mb4: %s\n", $conexao->error);
    // Tratar erro se necessário
}

// Opcional: Verificar se o charset foi definido corretamente (para debug)
// printf("Charset atual: %s\n", $conexao->character_set_name());

// A variável $conexao estará disponível para ser usada em outros scripts PHP
// que incluírem este arquivo.
?>