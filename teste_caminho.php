<?php
// teste_caminho.php

// Ativa a exibição de todos os erros para não termos tela branca
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Carrega o config para termos nossa constante de caminho
require_once 'config.php';

// Monta o caminho completo que estamos tentando usar
$caminho_do_template = PROJECT_ROOT_PATH . '/includes/email_templates.php';

echo "<h1>Teste de Verificação de Caminho</h1>";
echo "<p>A constante <strong>PROJECT_ROOT_PATH</strong> está definida como:</p>";
echo "<pre style='background-color: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>" . PROJECT_ROOT_PATH . "</pre>";

echo "<p>O caminho completo que o PHP está tentando carregar é:</p>";
echo "<pre style='background-color: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>" . $caminho_do_template . "</pre>";

echo "<hr>";

// Verifica se o arquivo realmente existe nesse caminho
if (file_exists($caminho_do_template)) {
    echo "<h2 style='color: green;'>SUCESSO: O arquivo foi encontrado!</h2>";
    echo "<p>Isso confirma que o caminho está correto. Se o erro 'Call to undefined function' ainda ocorre, pode haver um problema de permissão de leitura no arquivo ou um caractere inválido dentro dele.</p>";
} else {
    echo "<h2 style='color: red;'>FALHA: O arquivo NÃO foi encontrado nesse caminho.</h2>";
    echo "<p><strong>Esta é a causa do seu erro.</strong> Por favor, verifique se:</p>";
    echo "<ul>";
    echo "<li>O nome da pasta é exatamente <strong>includes</strong> (no plural e em minúsculas).</li>";
    echo "<li>O nome do arquivo é exatamente <strong>email_templates.php</strong> (no plural e em minúsculas).</li>";
    echo "</ul>";
}
?>