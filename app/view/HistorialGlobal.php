<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['tipo'])) {
  header('Location: ../../Public/index.php');
  exit;
}
$rol = $_SESSION['tipo']; // 'Administrador' | 'Encargado' | ...
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Historial General</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../Public/css/brand.css">
  <link rel="stylesheet" href="../../Public/css/historial_global.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../../Public/css/historial.css?v=<?php echo time(); ?>">
</head>
<body>
  <main class="container my-3" id="historial-global" data-role="<?php echo htmlspecialchars($rol, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h1 class="m-0 text-brand">Historial General</h1>
        <div class="text-muted small">Vista de calendarios de reservas y cancelaciones de las aulas AIP</div>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-brand" href="Admin.php?view=reportes">📊 Reportes / Filtros</a>
        <a class="btn btn-outline-secondary" href="Admin.php?view=inicio">Volver</a>
      </div>
    </div>

    <!-- Calendario Global (parte superior) -->
    <section class="card shadow-sm mb-3 p-3">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="fw-semibold text-brand">Calendario de Reservas (Global)</div>
        <div class="d-flex gap-2 align-items-center">
          <button id="prev-week" class="btn btn-outline-brand btn-sm">« Semana anterior</button>
          <button id="next-week" class="btn btn-outline-brand btn-sm">Semana siguiente »</button>
          <input type="hidden" id="start-of-week" value="<?php echo date('Y-m-d'); ?>">
          <div class="btn-group" role="group">
            <button id="btn-manana" class="btn btn-brand btn-sm active">Mañana</button>
            <button id="btn-tarde" class="btn btn-outline-brand btn-sm">Tarde</button>
          </div>
          <form id="form-pdf-global" action="../view/exportar_pdf_global.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="start_week" id="pdf-start-week" value="<?php echo date('Y-m-d'); ?>">
            <input type="hidden" name="turno" id="pdf-turno" value="manana">
            <input type="hidden" name="profesor" id="pdf-prof" value="">
            <button type="submit" class="btn btn-outline-brand btn-sm">Descargar PDF</button>
          </form>
        </div>
      </div>
      <input type="hidden" id="calendar-prof-filter" value="">
      <div id="calendarios" class="calendarios-grid mt-3"></div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../Public/js/HistorialGlobalCalendario.js?v=<?php echo time(); ?>"></script>
</body>
</html>
