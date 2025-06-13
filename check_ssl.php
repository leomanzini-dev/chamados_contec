<?php
// check_ssl.php
// Este script verifica se a extensão OpenSSL está realmente ativa no seu ambiente PHP.

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Diagnóstico OpenSSL</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .success { border: 2px solid green; background-color: #e9ffe9; padding: 15px; border-radius: 5px; }
        .error { border: 2px solid red; background-color: #ffeeee; padding: 15px; border-radius: 5px; }
        code { background-color: #eee; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Diagnóstico da Extensão OpenSSL</h1>
    <?php
    if (extension_loaded('openssl')) {
        echo '<div class="success">';
        echo '<h2>SUCESSO! A extensão OpenSSL está ATIVA.</h2>';
        echo '<p>Isto significa que o seu <code>php.ini</code> está correto. Se o erro "Unable to create the key" persistir, o problema é provavelmente de permissões. Tente o seguinte:</p>';
        echo '<ul>';
        echo '<li>Feche o Painel de Controlo do XAMPP.</li>';
        echo '<li>Clique com o botão direito no ícone do Painel de Controlo do XAMPP e selecione <strong>"Executar como administrador"</strong>.</li>';
        echo '<li>Inicie o Apache e tente correr o ficheiro <code>generate_keys.php</code> novamente.</li>';
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<div class="error">';
        echo '<h2>FALHA! A extensão OpenSSL NÃO está ativa.</h2>';
        echo '<p>Isto confirma que o seu servidor Apache não está a carregar a alteração que fez. Por favor, verifique novamente com atenção os passos do <strong>Passo 1.5</strong> no guia. As causas mais comuns são:</p>';
        echo '<ul>';
        echo '<li>O ponto e vírgula (;) da linha <code>extension=openssl</code> não foi removido corretamente.</li>';
        echo '<li>O serviço Apache não foi <strong>parado (Stop)</strong> e <strong>iniciado (Start)</strong> novamente depois de guardar o <code>php.ini</code>.</li>';
        echo '<li>Você pode ter editado um ficheiro <code>php.ini</code> errado. O caminho correto está geralmente em <code>C:\xampp\php\php.ini</code>.</li>';
        echo '</ul>';
        echo '</div>';
    }
    ?>
</body>
</html>
