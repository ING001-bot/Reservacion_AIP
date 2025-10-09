<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Seguridad básica (ajusta según tu app)
if (!isset($_SESSION['id_usuario'])) {
    echo 'Acceso denegado'; exit;
}

require '../controllers/HistorialController.php';
require '../config/conexion.php';

$controller = new HistorialController($conexion);
$startOfWeek = date('Y-m-d'); // referencia inicial (lunes calculado en JS/Controller)
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Historial AIP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../Public/css/brand.css">
  <link rel="stylesheet" href="../../Public/css/historial.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
  <?php require __DIR__ . '/partials/navbar.php'; ?>
  <main class="container py-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h1 class="text-brand h3 mb-0">📜 Historial de Aulas</h1>
        <small class="text-muted">Semana de lunes a sábado</small>
      </div>
      <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="btn-group" role="group">
          <button id="btn-manana" class="btn btn-brand btn-sm active">☀️ Mañana</button>
          <button id="btn-tarde" class="btn btn-outline-brand btn-sm">🌙 Tarde</button>
        </div>
        <form action="../view/exportar_pdf.php" method="POST" target="_blank" class="m-0">
          <input type="hidden" name="start_week" id="pdf-start-week" value="<?php echo $startOfWeek; ?>">
          <input type="hidden" name="turno" id="pdf-turno" value="manana">
          <button type="submit" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-pdf"></i> Descargar PDF
          </button>
        </form>
      </div>
    </div>

    <div class="d-flex gap-2 align-items-center justify-content-center mb-3">
      <button id="prev-week" class="btn btn-outline-brand btn-sm">
        <i class="bi bi-chevron-left"></i> Semana anterior
      </button>
      <input type="hidden" id="start-of-week" value="<?php echo $startOfWeek; ?>">
      <span id="week-range-display" class="badge bg-primary-subtle text-primary-emphasis px-3 py-2"></span>
      <button id="next-week" class="btn btn-outline-brand btn-sm">
        Semana siguiente <i class="bi bi-chevron-right"></i>
      </button>
    </div>

    <section id="calendarios" class="calendarios-grid">
      <!-- Calendarios (AIP1 y AIP2) se inyectarán aquí -->
    </section>

    <section class="prestamos">
      <h2>Préstamos realizados</h2>
      <div id="tabla-prestamos"></div>
    </section>

    <!-- Barra inferior móvil para PDF (no se sale del sistema) -->
    <div class="mobile-bottom-bar">
      <form action="../view/exportar_pdf.php" method="POST" target="_blank" class="w-100 m-0">
        <input type="hidden" name="start_week" id="pdf-start-week-bottom" value="<?php echo $startOfWeek; ?>">
        <input type="hidden" name="turno" id="pdf-turno-bottom" value="manana">
        <button type="submit" class="btn btn-brand w-100">
          <i class="fas fa-file-arrow-down me-1"></i> Descargar PDF
        </button>
      </form>
    </div>
  </main>

  <?php $v = time(); ?>
  <script src="../../Public/js/Historial.js?v=<?= $v ?>" defer></script>
  <script>
  // Mantener sincronizados los campos del PDF de la barra inferior
  document.addEventListener('DOMContentLoaded', function(){
    const topStart = document.getElementById('pdf-start-week');
    const topTurno = document.getElementById('pdf-turno');
    const botStart = document.getElementById('pdf-start-week-bottom');
    const botTurno = document.getElementById('pdf-turno-bottom');
    function sync(){
      if (topStart && botStart) botStart.value = topStart.value;
      if (topTurno && botTurno) botTurno.value = topTurno.value;
    }
    sync();
    [topStart, topTurno].forEach(el => el && el.addEventListener('change', sync));
  });
  </script>
</body>
</html>
