<?php
// email_config.php (VERSÃO CORRIGIDA)

// --- Configurações do Servidor de Envio (SMTP) ---

// Host do seu servidor de e-mail.
define('SMTP_HOST', 'mail.contec1996.com.br');

// Usuário de autenticação no servidor SMTP (geralmente o e-mail completo).
define('SMTP_USERNAME', 'informatica@contec1996.com.br'); 

// SENHA do e-mail acima. POR FAVOR, TROQUE SUA SENHA E PREENCHA A NOVA AQUI.
define('SMTP_PASSWORD', 'ContecMatao2024**');

// Porta do servidor SMTP. 587 para TLS (recomendado), 465 para SSL.
define('SMTP_PORT', 587);

// Tipo de criptografia. 'tls' para porta 587, 'ssl' para porta 465.
define('SMTP_SECURE', 'tls');


// --- Informações do Remetente (Quem envia o e-mail) ---

// O e-mail que aparecerá no campo "De:" para o destinatário.
define('EMAIL_FROM', 'naoresponda_chamadoscontec@contec1996.com.br');

// O nome que aparecerá no campo "De:" para o destinatário.
define('EMAIL_FROM_NAME', 'Sistema de Chamados Contec');

// A TAG DE FECHAMENTO ?>