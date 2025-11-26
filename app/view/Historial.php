<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Seguridad básica (ajusta según tu app)
if (!isset($_SESSION['id_usuario'])) {
    echo 'Acceso denegado'; exit;
}

// Prevenir caché del navegador (solo si no es vista embebida)
if (!defined('EMBEDDED_VIEW')) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
}

require '../controllers/HistorialController.php';
require '../config/conexion.php';

// Marcar notificación como leída si viene desde una notificación
if (isset($_GET['notif_read']) && is_numeric($_GET['notif_read'])) {
    try {
        $stmt = $conexion->prepare("UPDATE notificaciones SET leida = 1 WHERE id_notificacion = ? AND id_usuario = ?");
        $stmt->execute([(int)$_GET['notif_read'], (int)$_SESSION['id_usuario']]);
    } catch (\Throwable $e) {
        // Ignorar errores silenciosamente
    }
}

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
        <h1 class="text-brand h3 mb-0">📜 Historial</h1>
        <small class="text-muted">Semana de lunes a sábado</small>
      </div>
      <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="btn-group" role="group" aria-label="Tabs historial">
          <button id="tab-reservas" class="btn btn-brand btn-sm">Historial/Reserva</button>
          <button id="tab-equipos" class="btn btn-outline-brand btn-sm">Historial/Equipos</button>
        </div>
      </div>
    </div>

    <!-- Reservas (Aulas) -->
    <section id="section-reservas">
      <div class="d-flex gap-2 align-items-center flex-wrap mb-2">
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
    </section>

    <!-- Equipos (Préstamos como calendario) -->
    <section id="section-equipos" style="display:none">
      <div class="d-flex gap-2 align-items-center flex-wrap mb-2">
        <div class="btn-group" role="group">
          <button id="eq-btn-manana" class="btn btn-brand btn-sm active">☀️ Mañana</button>
          <button id="eq-btn-tarde" class="btn btn-outline-brand btn-sm">🌙 Tarde</button>
        </div>
      </div>
      <div class="d-flex gap-2 align-items-center justify-content-center mb-3">
        <button id="eq-prev-week" class="btn btn-outline-brand btn-sm">
          <i class="bi bi-chevron-left"></i> Semana anterior
        </button>
        <input type="hidden" id="eq-start-of-week" value="<?php echo $startOfWeek; ?>">
        <span id="eq-week-range-display" class="badge bg-primary-subtle text-primary-emphasis px-3 py-2"></span>
        <button id="eq-next-week" class="btn btn-outline-brand btn-sm">
          Semana siguiente <i class="bi bi-chevron-right"></i>
        </button>
      </div>
      <section id="calendarios-equipos" class="calendarios-grid">
        <!-- Calendarios por tipo (LAPTOP / PROYECTOR) se inyectarán aquí -->
      </section>
      <section class="card mt-3 p-2">
        <div class="fw-semibold mb-2">Préstamos de la semana</div>
        <div id="eq-table-container"></div>
      </section>
    </section>

    <!-- Placeholder oculto para compatibilidad con Public/js/Historial.js -->
    <div id="tabla-prestamos" style="display:none"></div>

    
  </main>

  <?php $v = time(); ?>
  <script src="../../Public/js/Historial.js?v=<?= $v ?>" defer></script>
  <script src="../../Public/js/HistorialEquiposCalendario.js?v=<?= $v ?>" defer></script>
  <script>
  document.addEventListener('DOMContentLoaded', function(){
    // Toggle tabs
    const tabRes = document.getElementById('tab-reservas');
    const tabEq  = document.getElementById('tab-equipos');
    const secRes = document.getElementById('section-reservas');
    const secEq  = document.getElementById('section-equipos');
    function showRes(){
      secRes.style.display='';
      secEq.style.display='none';
      tabRes.classList.remove('btn-outline-brand');
      tabRes.classList.add('btn-brand');
      tabEq.classList.remove('btn-brand');
      tabEq.classList.add('btn-outline-brand');
    }
    function showEq(){
      secRes.style.display='none';
      secEq.style.display='';
      tabEq.classList.remove('btn-outline-brand');
      tabEq.classList.add('btn-brand');
      tabRes.classList.remove('btn-brand');
      tabRes.classList.add('btn-outline-brand');
    }
    tabRes.addEventListener('click', showRes);
    tabEq.addEventListener('click', showEq);
    
    // Detectar hash en URL para abrir pestaña específica
    const hash = window.location.hash;
    if (hash === '#equipos') {
      showEq();
    } else {
      // default: mostrar reservas
      showRes();
    }

    // Sincronización ya no necesaria: solo hay un botón PDF (arriba)
  });
  </script>
  <script src="../../Public/js/theme.js"></script>
</body>
</html>
