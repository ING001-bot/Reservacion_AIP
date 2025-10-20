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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../../Public/css/brand.css">
  <link rel="stylesheet" href="../../Public/css/admin_mobile.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../../Public/css/historial_global.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../../Public/css/historial.css?v=<?php echo time(); ?>">
</head>
<body>
  <?php require __DIR__ . '/partials/navbar.php'; ?>
  <main class="container my-3" id="historial-global" data-role="<?php echo htmlspecialchars($rol, ENT_QUOTES, 'UTF-8'); ?>">
<?php endif; ?>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h1 class="m-0 text-brand">Historial General</h1>
        <div class="text-muted small">Vista de calendarios de reservas y cancelaciones de las aulas AIP</div>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <div class="btn-group" role="group" aria-label="Tabs historial">
          <button id="tab-reservas" class="btn btn-brand btn-sm">Historial/Reserva</button>
          <button id="tab-equipos" class="btn btn-outline-brand btn-sm">Historial/Equipos</button>
        </div>
        <?php if ($rol === 'Administrador'): ?>
          <a class="btn btn-outline-brand btn-sm" href="Admin.php?view=reportes">📊 Reportes / Filtros</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Calendario Global (Reservas) -->
    <section id="section-reservas" class="card shadow-sm mb-3 p-3">
      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <div class="fw-semibold text-brand fs-5">Calendario de Reservas (Global)</div>
            <small class="text-muted">Semana de lunes a sábado</small>
          </div>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap justify-content-center justify-content-md-start">
          <div class="btn-group" role="group">
            <button id="btn-manana" class="btn btn-brand btn-sm active">☀️ Mañana</button>
            <button id="btn-tarde" class="btn btn-outline-brand btn-sm">🌙 Tarde</button>
          </div>
          <form id="form-pdf-global" action="../view/exportar_pdf.php" method="POST" target="_blank" class="m-0 w-100 w-md-auto">
            <input type="hidden" name="start_week" id="pdf-start-week" value="<?php echo date('Y-m-d'); ?>">
            <input type="hidden" name="turno" id="pdf-turno" value="manana">
            <input type="hidden" name="profesor" id="pdf-prof" value="">
            <button type="submit" class="btn btn-success btn-sm w-100">
              <i class="bi bi-file-earmark-pdf"></i> Descargar PDF
            </button>
          </form>
        </div>
      </div>
      <div class="d-flex gap-2 align-items-center justify-content-center mb-3">
        <button id="prev-week" class="btn btn-outline-brand btn-sm">
          <i class="bi bi-chevron-left"></i> Semana anterior
        </button>
        <input type="hidden" id="start-of-week" value="<?php echo date('Y-m-d'); ?>">
        <span id="week-range-display" class="badge bg-primary-subtle text-primary-emphasis px-3 py-2"></span>
        <button id="next-week" class="btn btn-outline-brand btn-sm">
          Semana siguiente <i class="bi bi-chevron-right"></i>
        </button>
      </div>
      <input type="hidden" id="calendar-prof-filter" value="">
      <div id="calendarios" class="calendarios-grid mt-3"></div>
    </section>
    
    <!-- Calendario de Equipos (Global por tipo) -->
    <section id="section-equipos" class="card shadow-sm mb-3 p-3" style="display:none">
      <div class="mb-3">
        <div class="fw-semibold text-brand fs-5">Calendario de Préstamos de Equipos (Global)</div>
        <small class="text-muted">Semana de lunes a sábado</small>
      </div>
      <div class="d-flex gap-2 align-items-center justify-content-center mb-3">
        <button id="eq-prev-week" class="btn btn-outline-brand btn-sm">
          <i class="bi bi-chevron-left"></i> Semana anterior
        </button>
        <input type="hidden" id="eq-start-of-week" value="<?php echo date('Y-m-d'); ?>">
        <span id="eq-week-range-display" class="badge bg-primary-subtle text-primary-emphasis px-3 py-2"></span>
        <button id="eq-next-week" class="btn btn-outline-brand btn-sm">
          Semana siguiente <i class="bi bi-chevron-right"></i>
        </button>
      </div>
      <div class="d-flex align-items-center gap-2 flex-wrap justify-content-between mb-2">
        <div class="btn-group" role="group">
          <button id="eq-btn-manana" class="btn btn-brand btn-sm active">☀️ Mañana</button>
          <button id="eq-btn-tarde" class="btn btn-outline-brand btn-sm">🌙 Tarde</button>
        </div>
        <div class="ms-auto" style="min-width:260px">
          <input id="eq-search" type="search" class="form-control form-control-sm" placeholder="Buscar por profesor, equipo o aula...">
        </div>
      </div>
      <div id="calendarios-equipos" class="calendarios-grid mt-3"></div>
      <section class="card mt-3 p-2">
        <div class="fw-semibold mb-2">Préstamos de la semana</div>
        <div id="eq-table-container"></div>
      </section>
    </section>

  <script src="../../Public/js/HistorialGlobalCalendario.js?v=<?php echo time(); ?>"></script>
  <script src="../../Public/js/HistorialEquiposCalendario.js?v=<?php echo time(); ?>"></script>
  <script>
    (function(){
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
      if (tabRes && tabEq){
        tabRes.addEventListener('click', showRes);
        tabEq.addEventListener('click', showEq);
        showRes();
      }
    })();
  </script>
<?php if (!defined('EMBEDDED_VIEW')): ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>
 
