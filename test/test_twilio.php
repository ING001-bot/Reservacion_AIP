<?php
// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar el autoload de Composer
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('Por favor ejecuta "composer install" en la raíz del proyecto');
}
require_once $autoloadPath;

// Incluir manualmente el archivo de configuración
require_once __DIR__ . '/../app/config/twilio.php';

// Incluir manualmente la clase SmsService
require_once __DIR__ . '/../app/lib/SmsService.php';

// Usar el namespace correcto
use App\Lib\SmsService;

// Verificar si se está enviando el formulario
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $_POST['to'] ?? '';
    $message = $_POST['message'] ?? 'Mensaje de prueba desde Twilio';
    
    $smsService = new SmsService();
    $result = $smsService->sendSms($to, $message);
}

// Verificar conexión con Twilio
$smsService = new SmsService();
$connection = $smsService->verifyConnection();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Twilio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h2 class="h5 mb-0">Prueba de Twilio SMS</h2>
                    </div>
                    <div class="card-body">
                        <!-- Estado de la conexión -->
                        <div class="mb-4">
                            <h3 class="h6">Estado de la conexión:</h3>
                            <?php if ($connection['success']): ?>
                                <div class="alert alert-success">
                                    <p class="mb-0">✅ <strong>Conexión exitosa con Twilio</strong></p>
                                    <p class="mb-0">Cuenta: <?= htmlspecialchars($connection['account']['friendly_name']) ?></p>
                                    <p class="mb-0">Estado: <?= htmlspecialchars($connection['account']['status']) ?></p>
                                    <p class="mb-0">Tipo: <?= htmlspecialchars($connection['account']['type']) ?></p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <p class="mb-0">❌ <strong>Error de conexión con Twilio</strong></p>
                                    <p class="mb-0"><?= htmlspecialchars($connection['error']) ?></p>
                                    <p class="mb-0 small">Asegúrate de que las credenciales en <code>app/config/twilio.php</code> sean correctas.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Formulario de prueba -->
                        <?php if ($connection['success']): ?>
                            <div class="mb-4">
                                <h3 class="h6">Enviar mensaje de prueba:</h3>
                                <?php if ($result): ?>
                                    <div class="alert alert-<?= $result['success'] ? 'success' : 'danger' ?> mb-3">
                                        <?php if ($result['success']): ?>
                                            <p class="mb-0">✅ <strong>Mensaje enviado correctamente</strong></p>
                                            <p class="mb-0">SID: <?= htmlspecialchars($result['sid']) ?></p>
                                            <p class="mb-0">Estado: <?= htmlspecialchars($result['status']) ?></p>
                                        <?php else: ?>
                                            <p class="mb-0">❌ <strong>Error al enviar el mensaje</strong></p>
                                            <p class="mb-0"><?= htmlspecialchars($result['error']) ?></p>
                                            <?php if (isset($result['code'])): ?>
                                                <p class="mb-0">Código: <?= htmlspecialchars($result['code']) ?></p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" class="border p-3 bg-light rounded">
                                    <div class="mb-3">
                                        <label for="to" class="form-label">Número de teléfono (con código de país):</label>
                                        <input type="text" class="form-control" id="to" name="to" 
                                               placeholder="Ej: +521234567890" required>
                                        <div class="form-text">En modo prueba, todos los mensajes se enviarán al número configurado en <code>test_number</code>.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="message" class="form-label">Mensaje:</label>
                                        <textarea class="form-control" id="message" name="message" rows="3" required>Este es un mensaje de prueba desde el sistema de reservas AIP</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Enviar mensaje de prueba</button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <!-- Instrucciones de configuración -->
                        <div class="mt-4">
                            <h3 class="h6">Configuración necesaria:</h3>
                            <ol>
                                <li>Abre el archivo <code>app/config/twilio.php</code> y configura tus credenciales:</li>
                                <pre class="bg-dark text-light p-3 rounded">
return [
    'account_sid' => 'TU_ACCOUNT_SID',
    'auth_token' => 'TU_AUTH_TOKEN',
    'from_number' => 'TU_NUMERO_TWILIO', // Con código de país, ej: +1234567890
    'test_mode' => true, // Cambiar a false en producción
    'test_number' => 'TU_NUMERO_DE_PRUEBA' // Número para pruebas
];</pre>
                                <li>Obtén tus credenciales en <a href="https://www.twilio.com/console" target="_blank">la consola de Twilio</a>.</li>
                                <li>Si estás en modo prueba, todos los mensajes se enviarán al número configurado en <code>test_number</code>.</li>
                                <li>Para usar en producción, establece <code>'test_mode' => false</code>.</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
