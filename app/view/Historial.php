<?php
// app/views/historial.php
if (session_status() === PHP_SESSION_NONE) session_start();

// seguridad
if (!isset($_SESSION['usuario']) || ($_SESSION['tipo'] ?? '') !== 'Profesor') {
    die("Acceso denegado");
}

// Asegurar que la conexión esté disponible
// Ajustar la ruta si tu config está en otro lugar
if (file_exists(__DIR__ . '/../config/conexion.php')) {
    require_once __DIR__ . '/../config/conexion.php';
} elseif (file_exists(__DIR__ . '/../../config/conexion.php')) {
    require_once __DIR__ . '/../../config/conexion.php';
} else {
    die("No se encontró config/conexion.php");
}

require_once __DIR__ . '/../controllers/HistorialController.php';

$id_usuario = $_SESSION['id_usuario'];
$ctrl = new HistorialController($conexion ?? $GLOBALS['conexion'] ?? null);

// Datos iniciales (semana actual)
$datos = $ctrl->obtenerReservasSemanaPorAula($id_usuario, 0);
$prestamos = $ctrl->obtenerPrestamosPorProfesor($id_usuario);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Historial - Reservas y Préstamos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../Public/css/brand.css">
  <style>
    /* estilos mínimos para la grilla */
    .calendar { width:100%; border-collapse: collapse; }
    .calendar th, .calendar td { border: 1px solid #e2e8f0; padding:6px; text-align:center; }
    .time-col { width:80px; font-weight:600; background:#f8fafc; }
    .occupied { background:#ffc9c9; }
    .free { background:#e6ffe6; }
    .calendar-title { font-weight:700; margin-bottom:8px; }
    .table-responsive-calendar { overflow:auto; max-height:520px; }
  </style>
</head>
<body class="bg-light">
<main class="container py-4">
  <h1 class="text-center text-brand mb-4">📚 Mis Reservas y Préstamos</h1>

  <div class="card mb-4 shadow-sm p-3">
    <div class="d-flex justify-content-center mb-2">
      <button id="btnPrev" class="btn btn-sm btn-outline-brand me-2">Semana anterior</button>
      <span id="weekRangeDisplay" class="fw-bold align-self-center"></span>
      <button id="btnNext" class="btn btn-sm btn-outline-brand ms-2">Semana siguiente</button>
    </div>
    <div class="d-flex justify-content-center mb-3">
      <button id="btnManana" class="btn btn-sm btn-brand me-2">Mañana (06:00 - 12:45)</button>
      <button id="btnTarde" class="btn btn-sm btn-outline-brand">Tarde (13:00 - 19:00)</button>
      <a id="btnPdf" class="btn btn-sm btn-outline-danger ms-2" target="_blank">Descargar PDF</a>
    </div>

    <div id="cal-aip1" class="calendar-box mb-3"></div>
    <div id="cal-aip2" class="calendar-box mb-3"></div>
  </div>

  <h2 class="text-center text-brand my-4">📖 Historial de Préstamos</h2>
  <div class="card shadow-sm p-3 mb-5">
    <?php if ($prestamos): ?>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-primary">
            <tr>
              <th>Tipo</th><th>Equipo</th><th>Aula</th><th>Fecha</th><th>Inicio</th><th>Fin</th><th>Devolución</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($prestamos as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p['tipo_equipo']) ?></td>
              <td><?= htmlspecialchars($p['nombre_equipo']) ?></td>
              <td><?= htmlspecialchars($p['nombre_aula']) ?></td>
              <td><?= htmlspecialchars($p['fecha_prestamo']) ?></td>
              <td><?= htmlspecialchars($p['hora_inicio']) ?></td>
              <td><?= htmlspecialchars($p['hora_fin']) ?></td>
              <td><?= htmlspecialchars($p['fecha_devolucion']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-info">No se encontraron préstamos de equipos.</div>
    <?php endif; ?>
  </div>
</main>

<script>
  // Datos iniciales suministrados por PHP (estructura compatible con el JS)
  const initialData = <?= json_encode($datos, JSON_UNESCAPED_UNICODE) ?>;
  // AJAX URL relativo desde esta vista a su controlador
  const AJAX_URL = '../controllers/HistorialController.php';
</script>
<script src="../../Public/js/historial.js"></script>
</body>
</html>
