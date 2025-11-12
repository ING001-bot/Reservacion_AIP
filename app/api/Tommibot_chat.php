<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../controllers/TommibotController.php';

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$mode = in_array(($input['mode'] ?? 'text'), ['text','voice'], true) ? $input['mode'] : 'text';
$userId = (int)($_SESSION['id_usuario'] ?? 0);

$bot = new TommibotController($conexion);
$payload = $message ? $bot->replyPayload($userId, $message, $mode) : ['reply'=>'¿En qué puedo ayudarte?','actions'=>[]];

echo json_encode(['ok'=>true,'reply'=>$payload['reply'] ?? '','actions'=>$payload['actions'] ?? []]);
