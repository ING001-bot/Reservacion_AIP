<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../controllers/HistorialGlobalController.php';
require_once __DIR__ . '/../controllers/HistorialController.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['tipo'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$start = $_GET['start'] ?? date('Y-m-d');
$turno = $_GET['turno'] ?? 'manana';
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$rol = $_SESSION['tipo'];
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);

$global = new HistorialGlobalController($conexion);
$helper = new HistorialController($conexion);
$monday = $helper->getMondayOfWeek($start);
$weekDates = $helper->getWeekDates($monday);

// Aulas AIP (dos calendarios)
$aulas = $helper->obtenerAulasPorTipo('AIP');
$aip_ids = [];
$aip_names = [];
foreach ($aulas as $a) {
    $aip_ids[] = (int)$a['id_aula'];
    $aip_names[] = $a['nombre_aula'];
    if (count($aip_ids) >= 2) break;
}
while (count($aip_ids) < 2) { $aip_ids[] = null; $aip_names[] = ''; }

// Obtener préstamos de la semana (según rol y filtro)
$desde = $weekDates[0];
$hasta = end($weekDates);
$opts = ['desde' => $desde, 'hasta' => $hasta, 'tipo' => 'prestamo'];
if ($rol === 'Administrador' || $rol === 'Encargado') {
    if ($q !== '') { $opts['profesor'] = $q; }
    $prestamos = $global->listarHistorial($opts);
} else {
    // Profesor: solo sus préstamos
    $todos = $global->listarHistorial($opts);
    $nombreSesion = $_SESSION['usuario'] ?? '';
    $prestamos = array_values(array_filter($todos, function($p) use ($nombreSesion) {
        return isset($p['profesor']) && ($p['profesor'] === $nombreSesion);
    }));
}

// Estructurar dos calendarios por turno (mañana/tarde) para toda la semana
$calMan = array_fill_keys($weekDates, []);
$calTar = array_fill_keys($weekDates, []);
foreach ($prestamos as $p) {
    $fecha = $p['fecha'] ?? ($p['fecha_prestamo'] ?? null);
    if (!$fecha || !in_array($fecha, $weekDates, true)) continue;
    $hi = substr($p['hora_inicio'] ?? '', 0, 8);
    $hf = substr($p['hora_fin'] ?? '', 0, 8);
    $item = [
        'hora_inicio' => $hi,
        'hora_fin'    => $hf,
        'profesor'    => $p['profesor'] ?? ($p['nombre'] ?? ''),
        'aula'        => $p['aula'] ?? ($p['nombre_aula'] ?? ''),
        'equipo'      => $p['equipo'] ?? ($p['nombre_equipo'] ?? ''),
    ];
    // Clasificación por turno (06:00-12:59 mañana, 13:00-19:00 tarde)
    if ($hi >= '06:00:00' && $hi < '13:00:00') {
        $calMan[$fecha][] = $item;
    } else if ($hi >= '13:00:00' && $hi <= '19:00:00') {
        $calTar[$fecha][] = $item;
    }
}

// Tabla inferior: agrupar equipos por registro (profesor, aula, fecha, horas)
$tablaMap = [];
foreach ($prestamos as $p) {
    $prof = $p['profesor'] ?? ($p['nombre'] ?? '');
    $aula = $p['aula'] ?? ($p['nombre_aula'] ?? '');
    $fecha = $p['fecha'] ?? ($p['fecha_prestamo'] ?? '');
    $hi = substr($p['hora_inicio'] ?? '', 0, 8);
    $hf = substr($p['hora_fin'] ?? '', 0, 8);
    $eq = $p['equipo'] ?? ($p['nombre_equipo'] ?? '');
    $key = implode('|', [$prof, $aula, $fecha, $hi, $hf]);
    if (!isset($tablaMap[$key])) {
        $tablaMap[$key] = [
            'profesor' => $prof,
            'equipo' => [],
            'aula' => $aula,
            'hora_inicio' => $hi,
            'hora_fin' => $hf,
            'fecha' => $fecha,
        ];
    }
    if ($eq !== '') { $tablaMap[$key]['equipo'][] = $eq; }
}
$tabla = array_map(function($row){
    $row['equipo'] = implode(', ', array_unique($row['equipo']));
    return $row;
}, array_values($tablaMap));

echo json_encode([
    'monday' => $monday,
    'manana' => $calMan,
    'tarde' => $calTar,
    'tabla' => $tabla,
]);
