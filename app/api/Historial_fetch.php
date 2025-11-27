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
$profesor_nombre = $_SESSION['usuario'] ?? '';

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
    
    // Obtener reservas para esta aula
    $reservas = $controller->obtenerReservasSemanaPorAula($id_aula, $monday, $id_usuario);
    
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

// Procesar cancelaciones del usuario en la semana
$cancelaciones = $controller->obtenerCanceladasSemana($monday, $id_usuario);
$weekDates = $controller->getWeekDates($monday);

foreach ($cancelaciones as $c) {
    $fecha = $c['fecha'] ?? null;
    $id_aula_cancel = intval($c['id_aula'] ?? 0);
    
    if (!$fecha || !in_array($fecha, $weekDates, true)) continue;
    
    $item = [
        'hora_inicio' => substr($c['hora_inicio'] ?? '', 0, 8),
        'hora_fin' => substr($c['hora_fin'] ?? '', 0, 8),
        'motivo' => $c['motivo'] ?? '',
        'profesor' => $profesor_nombre
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

header('Content-Type: application/json');
echo json_encode([
    'monday' => $monday,
    'aulas' => $aulas_data,
    'profesor' => $profesor_nombre
]);
