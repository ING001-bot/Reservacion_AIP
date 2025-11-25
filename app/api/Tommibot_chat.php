<?php
header('Content-Type: application/json');
session_start();

try {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../controllers/TommibotController.php';

    // Verificar sesión
    if (!isset($_SESSION['id_usuario'])) {
        echo json_encode(['ok'=>false,'error'=>'No has iniciado sesión']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');
    $mode = in_array(($input['mode'] ?? 'text'), ['text','voice'], true) ? $input['mode'] : 'text';
    $userId = (int)($_SESSION['id_usuario'] ?? 0);

    // Verificar conexión a base de datos
    if (!isset($conexion) || !$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $bot = new TommibotController($conexion);
    $payload = $message ? $bot->replyPayload($userId, $message, $mode) : ['reply'=>'¿En qué puedo ayudarte?','actions'=>[]];

    echo json_encode(['ok'=>true,'reply'=>$payload['reply'] ?? '','actions'=>$payload['actions'] ?? []]);

} catch (Exception $e) {
    error_log('Error en Tommibot_chat.php: ' . $e->getMessage());
    echo json_encode(['ok'=>false,'error'=>'Lo siento, ocurrió un error al procesar tu mensaje. Por favor, intenta nuevamente.','details'=>$e->getMessage()]);
}
