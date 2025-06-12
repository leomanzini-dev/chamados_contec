<?php
$senha_para_colaborador = '123@mudar'; // <-- DEFINA A SENHA QUE VOCÊ QUER AQUI
$hash_da_senha = password_hash($senha_para_colaborador, PASSWORD_DEFAULT);

echo "Para a senha: <strong>" . htmlspecialchars($senha_para_colaborador) . "</strong><br>";
echo "O hash gerado é: <pre>" . htmlspecialchars($hash_da_senha) . "</pre>";
echo "<p>Copie TODO o hash acima (incluindo qualquer $ inicial) e cole no campo 'senha' da tabela 'usuarios' no phpMyAdmin para o email colaborador@contec.com.br.</p>";
?>