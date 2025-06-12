<?php
// email_config.php - CONFIGURADO PARA OUTLOOK/OFFICE 365

// Define as configurações do servidor de envio de e-mail (SMTP)
define('SMTP_HOST', 'mail.contec1996.com.br');      // Servidor SMTP da Microsoft
define('SMTP_USERNAME', 'informatica@contec1996.com.br'); // Coloque aqui o seu e-mail completo do Outlook
define('SMTP_PASSWORD', 'ContecMatao2024**');   // Coloque aqui a senha do seu e-mail
define('SMTP_PORT', 587);                        // Porta padrão para STARTTLS
define('SMTP_SECURE', 'tls');                    // Tipo de criptografia (geralmente 'tls' para a porta 587)

// Define as informações do remetente (quem está enviando)
define('EMAIL_FROM', 'naoresponda_chamadoscontec@contec1996.com.br');  // Repita seu e-mail aqui
define('EMAIL_FROM_NAME', 'Sistema de Chamados Contec'); // Nome que aparecerá para quem receber o e-mail
?>