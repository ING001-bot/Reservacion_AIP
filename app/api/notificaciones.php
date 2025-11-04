<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../controllers/PrestamoController.php';

$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
if (!$id_usuario) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'No autorizado']); exit; }

$action = $_GET['action'] ?? ($_POST['action'] ?? 'listar');
$pc = new PrestamoController($conexion);

try {
  switch ($action) {
    case 'pulse': {
      // Endpoint de salud para autodescubrimiento de ruta desde notifications.js
      echo json_encode(['ok'=>true]);
      break;
    }
    case 'listar': {
      $solo = isset($_GET['soloNoLeidas']) ? (bool)$_GET['soloNoLeidas'] : true;
      $limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 10;
      $items = $pc->listarNotificacionesUsuario($id_usuario, $solo, $limit);
      $noLeidas = array_values(array_filter($items, fn($n)=> (int)$n['leida']===0));
      echo json_encode(['ok'=>true,'items'=>$items,'noLeidas'=>count($noLeidas)]);
      break;
    }
    case 'marcar': {
      $id = (int)($_POST['id'] ?? 0);
      if (!$id) { throw new Exception('Falta id'); }
      $ok = $pc->marcarNotificacionLeida($id, $id_usuario);
      echo json_encode(['ok'=>$ok]);
      break;
    }
    case 'marcar_todas': {
      $ok = $pc->marcarTodasNotificacionesLeidas($id_usuario);
      echo json_encode(['ok'=>$ok]);
      break;
    }
    default:
      echo json_encode(['ok'=>false,'error'=>'Acción no soportada']);
  }
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
