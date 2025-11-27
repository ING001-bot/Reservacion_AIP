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

// Preparar estructura dinámica para TODAS las aulas AIP
$aulas_data = [];
$cancelaciones_data = [];

foreach ($aulas as $aula) {
    $id_aula = $aula['id_aula'];
    $nombre_aula = $aula['nombre_aula'];
    
    // Obtener reservas para esta aula (TODAS, no solo del usuario)
    $reservas = $controller->obtenerReservasSemanaPorAulaTodos($id_aula, $monday, $profesorLike);
    
    // Inicializar cancelaciones para esta aula
    $weekDates = $controller->getWeekDates($monday);
    $cancel_aula = array_fill_keys($weekDates, []);
    
    $aulas_data[] = [
        'id_aula' => $id_aula,
        'nombre_aula' => $nombre_aula,
        'reservas' => $reservas,
        'cancelaciones' => $cancel_aula
    ];
    
    $cancelaciones_data[$id_aula] = $cancel_aula;
}

// Procesar cancelaciones de TODOS en la semana
$cancelaciones = $controller->obtenerCanceladasSemanaTodos($monday, null, $profesorLike);
$weekDates = $controller->getWeekDates($monday);

foreach ($cancelaciones as $c) {
    $fecha = $c['fecha'] ?? null;
    $id_aula_cancel = intval($c['id_aula'] ?? 0);
    
    if (!$fecha || !in_array($fecha, $weekDates, true)) continue;
    
    $item = [
        'hora_inicio' => substr($c['hora_inicio'] ?? '', 0, 8),
        'hora_fin' => substr($c['hora_fin'] ?? '', 0, 8),
        'motivo' => $c['motivo'] ?? '',
        'profesor' => $c['profesor'] ?? ''
    ];
    
    // Asignar a la aula correspondiente
    if (isset($cancelaciones_data[$id_aula_cancel])) {
        $cancelaciones_data[$id_aula_cancel][$fecha][] = $item;
    }
}

// Actualizar cancelaciones en aulas_data
foreach ($aulas_data as &$aula) {
    if (isset($cancelaciones_data[$aula['id_aula']])) {
        $aula['cancelaciones'] = $cancelaciones_data[$aula['id_aula']];
    }
}
unset($aula);

echo json_encode([
    'monday' => $monday,
    'aulas' => $aulas_data
]);
