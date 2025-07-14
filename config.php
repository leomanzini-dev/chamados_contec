<?php
// config.php

// Define uma constante com o caminho absoluto para a pasta raiz do projeto.
define('PROJECT_ROOT_PATH', __DIR__);

// ===== ATUALIZAÇÃO DA URL PÚBLICA =====
// O endereço PÚBLICO completo do seu sistema.
define('APP_URL', 'https://640d6541ba22.ngrok-free.app/chamados_contec');


// --- Suas chaves VAPID (Mantidas, mas não são mais usadas para e-mail) ---
define('VAPID_PUBLIC_KEY', 'BLeQCw4oHwJVMvm0ko7jptDFztp95wmWypZOk1IPjMJ6xRzoNUxG6Kgt8zpNjzeqYh_iFImB31K9y3Qj0WSXxnk');
define('VAPID_PRIVATE_KEY', 'iqk_-HlBKbbGP6SOKbVguctnb_u1zfjHOoSQ-56-IxQ');

// A TAG DE FECHAMENTO ?>