<?php
// Configuración sencilla de Twilio.
// Rellena estos valores con tus credenciales reales.
// No se recomienda versionar este archivo con credenciales reales.

return [
    // Ejemplo: ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
    'account_sid' => getenv('TWILIO_ACCOUNT_SID') ?: 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    // Ejemplo: your_auth_token
    'auth_token'  => getenv('TWILIO_AUTH_TOKEN') ?: 'your_auth_token_here',
    // Ejemplo: +16205518379 (formato E.164)
    'from_number' => getenv('TWILIO_FROM_NUMBER') ?: '+1234567890',
    'whatsapp_from' => getenv('TWILIO_WHATSAPP_FROM') ?: '+14155238886'
    , 'allow_dev_mode' => false
];
