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
  <title>Historial General</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../Public/css/brand.css">
  <link rel="stylesheet" href="../../Public/css/admin_mobile.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../../Public/css/historial_global.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../../Public/css/historial.css?v=<?php echo time(); ?>">
</head>
<body>
  <main class="container my-3" id="historial-global" data-role="<?php echo htmlspecialchars($rol, ENT_QUOTES, 'UTF-8'); ?>">
<?php endif; ?>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h1 class="m-0 text-brand">Historial General</h1>
        <div class="text-muted small">Vista de calendarios de reservas y cancelaciones de las aulas AIP</div>
      </div>
      <div class="d-flex gap-2">
        <?php if ($rol === 'Administrador'): ?>
          <a class="btn btn-outline-brand" href="Admin.php?view=reportes">📊 Reportes / Filtros</a>
        <?php endif; ?>
        <!-- Botón Volver se gestiona desde el navbar -->
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
          <form id="form-pdf-global" action="../view/exportar_pdf.php" method="POST" target="_blank" class="m-0 d-none d-sm-inline-block">
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
    <!-- Barra inferior móvil para PDF (dentro del sistema) -->
    <div class="mobile-bottom-bar d-sm-none">
      <form action="../view/exportar_pdf.php" method="POST" target="_blank" class="w-100 m-0">
        <input type="hidden" name="start_week" id="pdf-start-week-bottom" value="<?php echo date('Y-m-d'); ?>">
        <input type="hidden" name="turno" id="pdf-turno-bottom" value="manana">
        <button type="submit" class="btn btn-brand w-100">
          <i class="fas fa-file-arrow-down me-1"></i> Descargar PDF
        </button>
      </form>
    </div>
    <script>
    // Sincroniza los campos del PDF de la barra inferior con los superiores
    document.addEventListener('DOMContentLoaded', function(){
      function sync(){
        var sTop = document.getElementById('pdf-start-week');
        var tTop = document.getElementById('pdf-turno');
        var pTop = document.getElementById('pdf-prof');
        var sBot = document.getElementById('pdf-start-week-bottom');
        var tBot = document.getElementById('pdf-turno-bottom');
        var pBot = document.getElementById('pdf-prof-bottom');
        if (sTop && sBot) sBot.value = sTop.value;
        if (tTop && tBot) tBot.value = tTop.value;
        if (pTop && pBot) pBot.value = pTop.value;
      }
      sync();
      ['pdf-start-week','pdf-turno','pdf-prof'].forEach(function(id){
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', sync);
      });
    });
    </script>
  <script src="../../Public/js/HistorialGlobalCalendario.js?v=<?php echo time(); ?>"></script>
<?php if (!defined('EMBEDDED_VIEW')): ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>
 
