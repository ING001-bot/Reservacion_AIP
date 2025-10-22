<?php
// Ruta al autoload de Composer o al SDK de Twilio
require_once __DIR__ . '/vendor/autoload.php';

// Si no usas Composer, descomenta esta línea y asegúrate de que la ruta sea correcta
// require_once __DIR__ . '/app/lib/Twilio/autoload.php';

use Twilio\Rest\Client;

// Reemplaza estos valores con tus credenciales de Twilio
$account_sid = 'TU_ACCOUNT_SID';
$auth_token = 'TU_AUTH_TOKEN';
$twilio_number = 'TU_NUMERO_TWILIO'; // Debe incluir el código de país, ejemplo: "+1234567890"
$to_number = 'TU_NUMERO_DE_TELEFONO'; // Número de teléfono de destino con código de país

try {
    $client = new Client($account_sid, $auth_token);
    
    $message = $client->messages->create(
        $to_number,
        [
            'from' => $twilio_number,
            'body' => '¡Hola desde Twilio! Este es un mensaje de prueba.'
        ]
    );
    
    echo "Mensaje enviado correctamente. SID: " . $message->sid;
} catch (Exception $e) {
    echo "Error al enviar el mensaje: " . $e->getMessage();
}
?>
