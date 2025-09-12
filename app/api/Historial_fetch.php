<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config/conexion.php';
require '../controllers/HistorialController.php';

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$start = $_GET['start'] ?? date('Y-m-d');
$turno = $_GET['turno'] ?? 'manana';
$id_usuario = $_SESSION['id_usuario'];

$controller = new HistorialController($conexion);
$monday = $controller->getMondayOfWeek($start);

// Traer aulas tipo AIP dinámicamente
$aulas = $controller->obtenerAulasPorTipo('AIP');

// Tomamos las dos primeras aulas AIP (AIP1 = izquierda, AIP2 = derecha)
$aip_ids = [];
$aip_names = [];
foreach ($aulas as $a) {
    $aip_ids[] = $a['id_aula'];
    $aip_names[] = $a['nombre_aula'];
    if (count($aip_ids) >= 2) break;
}
if (count($aip_ids) < 2) {
    while (count($aip_ids) < 2) {
        $aip_ids[] = null;
        $aip_names[] = '';
    }
}

// Filtrar solo las reservas del profesor logueado
$aip1 = $aip_ids[0] ? $controller->obtenerReservasSemanaPorAula($aip_ids[0], $monday, $id_usuario) : array_fill_keys($controller->getWeekDates($monday), []);
$aip2 = $aip_ids[1] ? $controller->obtenerReservasSemanaPorAula($aip_ids[1], $monday, $id_usuario) : array_fill_keys($controller->getWeekDates($monday), []);

header('Content-Type: application/json');
echo json_encode([
    'monday' => $monday,
    'aip1' => $aip1,
    'aip2' => $aip2,
    'aip1_nombre' => $aip_names[0],
    'aip2_nombre' => $aip_names[1]
]);
