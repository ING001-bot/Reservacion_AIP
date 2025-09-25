<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../controllers/HistorialController.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['tipo'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$start = $_GET['start'] ?? date('Y-m-d');
$turno = $_GET['turno'] ?? 'manana';
$profesorLike = isset($_GET['profesor']) && $_GET['profesor'] !== '' ? $_GET['profesor'] : null; // filtro opcional por nombre

$controller = new HistorialController($conexion);
$monday = $controller->getMondayOfWeek($start);

// Traer aulas tipo AIP dinámicamente
$aulas = $controller->obtenerAulasPorTipo('AIP');
$aip_ids = [];
$aip_names = [];
foreach ($aulas as $a) {
    $aip_ids[] = $a['id_aula'];
    $aip_names[] = $a['nombre_aula'];
    if (count($aip_ids) >= 2) break;
}
while (count($aip_ids) < 2) { $aip_ids[] = null; $aip_names[] = ''; }

// Reservas de TODOS los profesores
$aip1 = $aip_ids[0] ? $controller->obtenerReservasSemanaPorAulaTodos($aip_ids[0], $monday, $profesorLike) : array_fill_keys($controller->getWeekDates($monday), []);
$aip2 = $aip_ids[1] ? $controller->obtenerReservasSemanaPorAulaTodos($aip_ids[1], $monday, $profesorLike) : array_fill_keys($controller->getWeekDates($monday), []);

// Cancelaciones de TODOS
$cancelaciones = $controller->obtenerCanceladasSemanaTodos($monday, null, $profesorLike);
$weekDates = $controller->getWeekDates($monday);
$cancel1 = array_fill_keys($weekDates, []);
$cancel2 = array_fill_keys($weekDates, []);
foreach ($cancelaciones as $c) {
    $fecha = $c['fecha'] ?? null;
    if (!$fecha || !in_array($fecha, $weekDates, true)) continue;
    $item = [
        'hora_inicio' => substr($c['hora_inicio'] ?? '', 0, 8),
        'hora_fin' => substr($c['hora_fin'] ?? '', 0, 8),
        'motivo' => $c['motivo'] ?? '',
        'profesor' => $c['profesor'] ?? ''
    ];
    if ($aip_ids[0] && intval($c['id_aula'] ?? 0) === intval($aip_ids[0])) {
        $cancel1[$fecha][] = $item;
    } elseif ($aip_ids[1] && intval($c['id_aula'] ?? 0) === intval($aip_ids[1])) {
        $cancel2[$fecha][] = $item;
    }
}

echo json_encode([
    'monday' => $monday,
    'aip1' => $aip1,
    'aip2' => $aip2,
    'cancel1' => $cancel1,
    'cancel2' => $cancel2,
    'aip1_nombre' => $aip_names[0],
    'aip2_nombre' => $aip_names[1]
]);
