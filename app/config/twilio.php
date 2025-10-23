<?php
// Configuración Twilio desde variables de entorno.
// Define TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN y TWILIO_FROM_NUMBER en tu entorno o archivo .env (no versionado)
return [
    'account_sid' => getenv('TWILIO_ACCOUNT_SID') ?: '',
    'auth_token' => getenv('TWILIO_AUTH_TOKEN') ?: '',
    'from_number' => getenv('TWILIO_FROM_NUMBER') ?: '', // Con código de país, ej: +1234567890
    'test_mode' => getenv('TWILIO_TEST_MODE') !== false ? filter_var(getenv('TWILIO_TEST_MODE'), FILTER_VALIDATE_BOOLEAN) : true,
    'test_number' => getenv('TWILIO_TEST_NUMBER') ?: '' // Número para pruebas
];
