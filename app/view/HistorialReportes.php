<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['tipo'])) {
  header('Location: ../../Public/index.php');
  exit;
}
$rol = $_SESSION['tipo']; // 'Administrador' | 'Encargado' | ...
?>
<?php if (!defined('EMBEDDED_VIEW')): ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reportes y Filtros</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../Public/css/brand.css">
  <link rel="stylesheet" href="../../Public/css/admin_mobile.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../../Public/css/historial_global.css?v=<?php echo time(); ?>">
</head>
<body>
  <main class="container my-3" id="historial-reportes" data-role="<?php echo htmlspecialchars($rol, ENT_QUOTES, 'UTF-8'); ?>">
<?php else: ?>
  <div id="historial-reportes" data-role="<?php echo htmlspecialchars($rol, ENT_QUOTES, 'UTF-8'); ?>">
<?php endif; ?>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h1 class="m-0 text-brand">Reportes y Filtros</h1>
        <div class="text-muted small">Genere listados por fecha, profesor, tipo y estado</div>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="Admin.php?view=historial_global">Volver al Calendario</a>
      </div>
    </div>

    <section class="card shadow-sm mb-3 p-3">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
        <div class="fw-semibold text-brand">Filtros</div>
        <div class="btn-group" role="group" aria-label="Rango rápido">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-range="hoy">Hoy</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" data-range="7">Últimos 7 días</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" data-range="mes">Este mes</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" data-range="todo">Todo</button>
        </div>
      </div>
      <form id="form-filtros" class="row gy-2 gx-2 align-items-end">
        <div class="col-6 col-md-3">
          <label class="form-label">Desde</label>
          <input type="date" class="form-control" name="desde" placeholder="AAAA-MM-DD">
          <div class="form-text">Fecha inicial del rango</div>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label">Hasta</label>
          <input type="date" class="form-control" name="hasta" placeholder="AAAA-MM-DD">
          <div class="form-text">Fecha final del rango</div>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">Profesor</label>
          <input type="text" class="form-control" name="profesor" placeholder="Ej: Juan Pérez">
          <div class="form-text">Coincidencia por nombre</div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">Tipo</label>
          <select name="tipo" class="form-select">
            <option value="">Todos</option>
            <option value="reserva">Reserva</option>
            <option value="prestamo">Préstamo</option>
          </select>
          <div class="form-text">Clase de movimiento</div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">Estado</label>
          <select name="estado" class="form-select">
            <option value="">Todos</option>
            <option value="Activa">Programada</option>
            <option value="Cancelada">Cancelada</option>
            <option value="Prestado">Prestado</option>
            <option value="Devuelto">Devuelto</option>
          </select>
          <div class="form-text">Situación del registro</div>
        </div>
        <div class="col-12 col-md-12 d-flex gap-2 justify-content-end">
          <button type="submit" class="btn btn-brand">Aplicar filtros</button>
          <button type="button" id="btn-reset" class="btn btn-outline-secondary">Limpiar</button>
        </div>
      </form>
    </section>

    <section class="mb-3">
      <div class="row g-3">
        <div class="col-6 col-lg-3">
          <div class="card shadow-sm p-3 h-100">
            <div class="text-muted">Reservas</div>
            <div class="fs-3 fw-bold" id="stat-reservas">0</div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="card shadow-sm p-3 h-100">
            <div class="text-muted">Cancelaciones</div>
            <div class="fs-3 fw-bold" id="stat-cancelaciones">0</div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="card shadow-sm p-3 h-100">
            <div class="text-muted">Préstamos</div>
            <div class="fs-3 fw-bold" id="stat-prestamos">0</div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="card shadow-sm p-3 h-100">
            <div class="text-muted">Horas Reservadas</div>
            <div class="fs-3 fw-bold" id="stat-horas">0</div>
          </div>
        </div>
      </div>
    </section>

    <section class="row g-3 mb-3">
      <div class="col-lg-6">
        <div class="card shadow-sm p-3 h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-semibold text-brand">Top Profesores por Reservas</div>
            <small class="text-muted">Top 10</small>
          </div>
          <ol id="list-top-prof-reservas" class="mb-0"></ol>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card shadow-sm p-3 h-100">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-semibold text-brand">Top Profesores por Préstamos</div>
            <small class="text-muted">Top 10</small>
          </div>
          <ol id="list-top-prof-prestamos" class="mb-0"></ol>
        </div>
      </div>
    </section>

    <section class="row g-3 mb-3">
      <div class="col-lg-7">
        <div class="card shadow-sm p-3 h-100">
          <div class="fw-semibold text-brand mb-2">Reservas por Día</div>
          <canvas id="chart-reservas-dia" height="140"></canvas>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="card shadow-sm p-3 h-100">
          <div class="fw-semibold text-brand mb-2">Aulas con más Reservas</div>
          <ol id="list-top-aulas" class="mb-0"></ol>
        </div>
      </div>
    </section>

    <section class="card shadow-sm p-2">
      <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center p-2">
        <div class="fw-semibold">Resultados <span class="badge rounded-pill text-bg-secondary" id="count-badge">0</span></div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-brand" id="btn-export-pdf">Exportar PDF</button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-striped align-middle" id="tabla-historial">
          <thead class="table-brand">
            <tr>
              <th>Fecha</th>
              <th>Inicio</th>
              <th>Fin</th>
              <th>Profesor</th>
              <th>Aula/Equipo</th>
              <th>Tipo</th>
              <th>Estado</th>
              <th>Observación</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="../../Public/js/HistorialReportes.js?v=<?php echo time(); ?>"></script>
  <script src="../../Public/js/HistorialEstadisticas.js?v=<?php echo time(); ?>"></script>
<?php if (!defined('EMBEDDED_VIEW')): ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php else: ?>
  </div>
<?php endif; ?>
