<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../controllers/HistorialController.php';
require_once __DIR__ . '/../models/PrestamoModel.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['tipo'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$start = $_GET['start'] ?? date('Y-m-d');
$turno = $_GET['turno'] ?? 'manana';
$q = isset($_GET['q']) && $_GET['q'] !== '' ? trim($_GET['q']) : null;
$controller = new HistorialController($conexion);
$monday = $controller->getMondayOfWeek($start);
$rol = $_SESSION['tipo'];
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);

// Fechas de la semana (Lun-Sáb)
$weekDates = $controller->getWeekDates($monday);

// Helper para normalizar HH:MM:SS
$normTime = function($t){ return substr((string)$t, 0, 8); };

// Construir estructura de agenda semanal (fecha => eventos) y tabla de préstamos
$agenda = array_fill_keys($weekDates, []);
$prestamosTabla = [];

// Obtener conexión a través del modelo de préstamos
$prestamoModel = new PrestamoModel($conexion);
$db = $prestamoModel->getDb();

// Recolectar préstamos individuales de la semana
$sqlInd = "SELECT p.id_prestamo, p.id_usuario, u.nombre AS profesor, e.nombre_equipo, e.tipo_equipo, a.nombre_aula,
                  p.fecha_prestamo AS fecha, p.hora_inicio, p.hora_fin, p.estado
           FROM prestamos p
           JOIN usuarios u ON p.id_usuario = u.id_usuario
           JOIN equipos e ON p.id_equipo = e.id_equipo
           JOIN aulas a ON p.id_aula = a.id_aula
           WHERE p.fecha_prestamo BETWEEN :desde AND :hasta";
$paramsInd = [':desde' => $weekDates[0], ':hasta' => end($weekDates)];
if (!in_array($rol, ['Administrador','Encargado'])) {
    $sqlInd .= " AND p.id_usuario = :u";
    $paramsInd[':u'] = $id_usuario;
}
$sqlInd .= $q ? " AND (u.nombre LIKE :q OR e.nombre_equipo LIKE :q OR a.nombre_aula LIKE :q)" : "";
$paramsInd[':q'] = $q ? "%$q%" : null;
$sqlInd .= " ORDER BY p.fecha_prestamo ASC, p.hora_inicio ASC";
$stmtInd = $db->prepare($sqlInd);
foreach ($paramsInd as $k=>$v) { if ($v!==null) $stmtInd->bindValue($k, $v); }
$stmtInd->execute();
$rowsInd = $stmtInd->fetchAll(PDO::FETCH_ASSOC);

// Agrupar individuales por (usuario, aula, fecha, hora_inicio, hora_fin, estado)
$groupedInd = [];
foreach ($rowsInd as $r) {
    $fecha = $r['fecha'];
    $hi = $normTime($r['hora_inicio'] ?? '');
    $hf = $normTime($r['hora_fin'] ?? '');
    $key = implode('|', [
        (int)$r['id_usuario'],
        $r['nombre_aula'] ?? '',
        $fecha,
        $hi,
        $hf,
        $r['estado'] ?? ''
    ]);
    if (!isset($groupedInd[$key])) {
        $groupedInd[$key] = [
            'id_usuario' => (int)$r['id_usuario'],
            'profesor' => $r['profesor'] ?? '',
            'aula' => $r['nombre_aula'] ?? '',
            'fecha' => $fecha,
            'hora_inicio' => $hi,
            'hora_fin' => $hf,
            'estado' => $r['estado'] ?? '',
            'equipos' => []
        ];
    }
    $eqName = trim((string)($r['nombre_equipo'] ?? ''));
    if ($eqName !== '') $groupedInd[$key]['equipos'][] = $eqName;
}


// Función para agregar item a agenda
$addAgenda = function(array &$bucket, string $fecha, string $hora_inicio, string $hora_fin, string $profesor){
    $bucket[$fecha][] = [
        'hora_inicio' => $hora_inicio,
        'hora_fin' => $hora_fin,
        'profesor' => $profesor
    ];
};

// Mapear individuales según tipo principal
foreach ($groupedInd as $g) {
    $fecha = $g['fecha'];
    if (!in_array($fecha, $weekDates, true)) continue;
    $addAgenda($agenda, $fecha, $g['hora_inicio'], $g['hora_fin'], $g['profesor']);
    $prestamosTabla[] = [
        'id' => 0, // agrupado
        'tipo' => 'Individual',
        'equipo' => implode(', ', array_unique($g['equipos'])),
        'profesor' => $g['profesor'],
        'aula' => $g['aula'],
        'fecha' => $g['fecha'],
        'hora_inicio' => $g['hora_inicio'],
        'hora_fin' => $g['hora_fin'],
        'estado' => $g['estado']
    ];
}


// Ordenar por hora dentro de cada día
foreach ($agenda as $fecha => $arr) {
    usort($arr, function($a,$b){ return strcmp($a['hora_inicio'],$b['hora_inicio']); });
    $agenda[$fecha] = $arr;
}

// Ordenar tabla por fecha/hora
usort($prestamosTabla, function($a,$b){
    $c = strcmp($a['fecha'], $b['fecha']);
    if ($c!==0) return $c;
    return strcmp($a['hora_inicio'], $b['hora_inicio']);
});

echo json_encode([
    'monday' => $monday,
    'agenda' => $agenda,
    'prestamos' => $prestamosTabla
]);
