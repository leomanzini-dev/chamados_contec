<?php
// config.php

// Define uma constante com o caminho absoluto para a pasta raiz do projeto.
define('PROJECT_ROOT_PATH', __DIR__);

// ===== ADIÇÃO IMPORTANTE =====
// Defina aqui o endereço PÚBLICO completo do seu sistema.
// Se você está usando ngrok, este é o endereço que ele te fornece.
// Lembre-se de incluir a pasta do projeto no final, se houver.
// IMPORTANTE: Mude este valor para o seu endereço ngrok atual!
define('APP_URL', 'https://4e18-189-50-251-153.ngrok-free.app/chamados_contec');


// --- Suas chaves VAPID ---
define('VAPID_PUBLIC_KEY', 'BLeQCw4oHwJVMvm0ko7jptDFztp95wmWypZOk1IPjMJ6xRzoNUxG6Kgt8zpNjzeqYh_iFImB31K9y3Qj0WSXxnk');
define('VAPID_PRIVATE_KEY', 'iqk_-HlBKbbGP6SOKbVguctnb_u1zfjHOoSQ-56-IxQ');
?>