<?php

return [
    // Dominio institucional permitido para crear cuentas (RN-14). Se configura en el .env
    'dominio' => env('UNIVERSIDAD_DOMINIO', '@unicah.edu'),

    // Códigos de verificación (sección 7 del análisis)
    'codigo_expira_minutos' => 15,
    'codigo_reenvio_segundos' => 60,
    'codigo_max_intentos' => 5,
    'registro_token_minutos' => 30,
];