<?php
// app/view/historial_profesor.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../controllers/HistorialController.php';

// seguridad sesión
if (!isset($_SESSION['usuario']) || ($_SESSION['tipo'] ?? '') !== 'Profesor') {
    echo "Acceso denegado"; exit;
}

$id_usuario = $_SESSION['id_usuario'];
$controller = new HistorialController($conexion);

// Obtener AIP1 y AIP2
$aulas = $controller->obtenerAulas();
if (count($aulas) < 2) {
    die("No hay aulas AIP suficientes registradas. (Se esperan 2)");
}
$aip1 = $aulas[0];
$aip2 = $aulas[1];

// Semana inicial
$inicio_semana = new DateTime();
$inicio_semana->modify('monday this week');
$fin_semana = clone $inicio_semana;
$fin_semana->modify('+5 days'); // termina sábado
$fecha_inicio = $inicio_semana->format('Y-m-d');
$fecha_fin = $fin_semana->format('Y-m-d');

// Reservas iniciales (render server-side para carga inicial)
$reservas_aip1 = $controller->obtenerReservasSemana($aip1['id_aula'], $fecha_inicio, $fecha_fin);
$reservas_aip2 = $controller->obtenerReservasSemana($aip2['id_aula'], $fecha_inicio, $fecha_fin);

// Prestamos para la tabla inferior
$prestamos = $controller->obtenerPrestamos($id_usuario);

// Datos embebidos para JS (estado inicial)
$initialData = [
    'fecha_inicio' => $fecha_inicio,
    'fecha_fin' => $fecha_fin,
    'aulas' => [
        [
            'id_aula' => $aip1['id_aula'],
            'nombre_aula' => $aip1['nombre_aula'],
            'reservas' => $reservas_aip1
        ],
        [
            'id_aula' => $aip2['id_aula'],
            'nombre_aula' => $aip2['nombre_aula'],
            'reservas' => $reservas_aip2
        ]
    ]
];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Historial Profesor - Calendarios AIP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../Public/css/historial.css">
</head>
<body>
<div class="container py-4">
    <h1 class="mb-3 text-center">📅 Historial y Calendarios AIP</h1>

    <!-- controles -->
    <div class="d-flex justify-content-between align-items-center mb-3 controls">
        <div>
            <button id="btnPrev" class="btn btn-outline-primary">⬅ Semana anterior</button>
            <button id="btnNext" class="btn btn-outline-primary">Semana siguiente ➡</button>
            <span class="ms-2 small-note" id="weekRangeServer">Semana: <?= htmlspecialchars($fecha_inicio) ?> → <?= htmlspecialchars($fecha_fin) ?></span>
        </div>
        <div>
            <button id="btnManana" class="btn btn-success">Turno Mañana (06:00–12:45)</button>
            <button id="btnTarde" class="btn btn-outline-secondary">Turno Tarde (13:00–19:00)</button>
        </div>
    </div>

    <!-- calendarios (dos aulas) -->
    <div class="row g-3" id="calendarsContainer">
        <div class="col-md-6">
            <div class="calendar-box" id="cal-aip1">
                <div class="calendar-title"><?= htmlspecialchars($aip1['nombre_aula']) ?></div>
                <!-- tabla inyectada por JS -->
            </div>
        </div>
        <div class="col-md-6">
            <div class="calendar-box" id="cal-aip2">
                <div class="calendar-title"><?= htmlspecialchars($aip2['nombre_aula']) ?></div>
                <!-- tabla inyectada por JS -->
            </div>
        </div>
    </div>

    <!-- Tabla de préstamos -->
    <h2 class="mt-4">📦 Historial de Préstamos de Equipos</h2>
    <div class="table-responsive mt-2">
        <?php if (!empty($prestamos)): ?>
            <table class="table table-striped table-bordered">
                <thead class="table-primary text-center">
                    <tr>
                        <th>Tipo</th>
                        <th>Equipo</th>
                        <th>Aula</th>
                        <th>Fecha Préstamo</th>
                        <th>Hora Inicio</th>
                        <th>Hora Fin</th>
                        <th>Fecha Devolución</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($prestamos as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['tipo_equipo'] ?? '') ?></td>
                        <td><?= htmlspecialchars($p['nombre_equipo'] ?? '') ?></td>
                        <td><?= htmlspecialchars($p['nombre_aula'] ?? '') ?></td>
                        <td><?= htmlspecialchars($p['fecha_prestamo'] ?? '') ?></td>
                        <td><?= htmlspecialchars($p['hora_inicio'] ?? '') ?></td>
                        <td><?= htmlspecialchars($p['hora_fin'] ?? '') ?></td>
                        <td><?= htmlspecialchars($p['fecha_devolucion'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">No se encontraron préstamos de equipos para este profesor.</div>
        <?php endif; ?>
    </div>

    <div class="mt-3 text-center">
        <a href="dashboard.php" class="btn btn-outline-secondary">⬅ Volver al Dashboard</a>
    </div>
</div>

<!-- Pasamos datos mínimos al JS (solo esto en línea) -->
<script>
    const initialData = <?= json_encode($initialData, JSON_UNESCAPED_UNICODE) ?>;
    const ajaxUrl = <?= json_encode('../controllers/HistorialController.php') ?>;
</script>

<!-- Script externo (mismo directorio). Guarda historial_calendario.js al lado de este archivo -->
<script src="../../Public/js/Historial.js"></script>
</body>
</html>
