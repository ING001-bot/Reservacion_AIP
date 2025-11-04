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
$reply = $message ? $bot->reply($userId, $message, $mode) : '¿En qué puedo ayudarte?';

echo json_encode(['ok'=>true,'reply'=>$reply]);
