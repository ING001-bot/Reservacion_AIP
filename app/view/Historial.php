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
  <main class="container">
    <h1 class="text-brand">Historial de Aulas AIP</h1>

    <div class="controls">
      <div class="turnos">
        <button id="btn-manana" class="btn btn-brand active">Turno Mañana (06:00 - 12:45)</button>
        <button id="btn-tarde" class="btn btn-outline-brand">Turno Tarde (13:00 - 19:00)</button>
      </div>

      <div class="semanas">
        <button id="prev-week" class="btn btn-outline-brand">« Semana anterior</button>
        <button id="next-week" class="btn btn-outline-brand">Semana siguiente »</button>
        <input type="hidden" id="start-of-week" value="<?php echo $startOfWeek; ?>">
      </div>

      <div class="pdf">
        <form action="../view/exportar_pdf.php" method="POST" target="_blank">
          <input type="hidden" name="start_week" id="pdf-start-week" value="<?php echo $startOfWeek; ?>">
          <input type="hidden" name="turno" id="pdf-turno" value="manana">
          <button type="submit" class="btn btn-outline-brand">Descargar PDF</button>
        </form>
      </div>
    </div>

    <section id="calendarios" class="calendarios-grid">
      <!-- Calendarios (AIP1 y AIP2) se inyectarán aquí -->
    </section>

    <section class="prestamos">
      <h2>Préstamos realizados</h2>
      <div id="tabla-prestamos"></div>
    </section>
  </main>
    <a href="Profesor.php" class="btn btn-primary mb-3">
      <i class="bi bi-arrow-left"></i> Volver al inicio
    </a>

  <?php $v = time(); ?>
  <script src="../../Public/js/Historial.js?v=<?= $v ?>" defer></script>
</body>
</html>
