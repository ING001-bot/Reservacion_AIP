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
    <h1 class="text-brand h3 mb-3">📜 Historial de Aulas</h1>

    <div class="controls">
      <div class="row g-2 align-items-stretch">
        <div class="col-12 col-md-6">
          <div class="btn-group w-100" role="group" aria-label="Seleccionar turno">
            <button id="btn-manana" class="btn btn-brand btn-control w-50 active">Mañana</button>
            <button id="btn-tarde" class="btn btn-outline-brand btn-control w-50">Tarde</button>
          </div>
          <div class="small text-muted mt-1">Mañana (06:00–12:45) · Tarde (13:00–19:00)</div>
        </div>
        <div class="col-12 col-md-6">
          <div class="d-flex gap-2">
            <button id="prev-week" class="btn btn-outline-brand btn-control flex-fill">« Semana anterior</button>
            <button id="next-week" class="btn btn-outline-brand btn-control flex-fill">Semana siguiente »</button>
          </div>
          <input type="hidden" id="start-of-week" value="<?php echo $startOfWeek; ?>">
        </div>
        <div class="col-12">
          <form action="../view/exportar_pdf.php" method="POST" target="_blank" class="w-100">
            <input type="hidden" name="start_week" id="pdf-start-week" value="<?php echo $startOfWeek; ?>">
            <input type="hidden" name="turno" id="pdf-turno" value="manana">
            <button type="submit" class="btn btn-outline-brand btn-control w-100">Descargar PDF</button>
          </form>
        </div>
      </div>
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
