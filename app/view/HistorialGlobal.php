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
        <div class="text-muted small">Semana de lunes a sábado</div>
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

<<<<<<< HEAD
    <div class="mb-3 d-flex gap-2 flex-wrap">
      <button id="tab-reserva" class="btn btn-brand btn-sm">Historial / Reserva</button>
      <button id="tab-equipos" class="btn btn-outline-brand btn-sm">Historial / Equipos</button>
    </div>

    <!-- Vista: Reserva (se mantiene igual) -->
    <section id="vista-reserva" class="card shadow-sm mb-3 p-3">
=======
    <!-- Calendario Global (Reservas) -->
    <section id="section-reservas" class="card shadow-sm mb-3 p-3">
>>>>>>> 37d623eb911e485d34ce66af60d357b7fdb58415
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
          <?php if (in_array($rol, ['Administrador','Encargado'])): ?>
          <form id="form-pdf-global" action="../view/exportar_pdf.php" method="POST" target="_blank" class="m-0 w-100 w-md-auto">
            <input type="hidden" name="start_week" id="pdf-start-week" value="<?php echo date('Y-m-d'); ?>">
            <input type="hidden" name="turno" id="pdf-turno" value="manana">
            <input type="hidden" name="profesor" id="pdf-prof" value="">
            <button type="submit" class="btn btn-success btn-sm w-100">
              <i class="bi bi-file-earmark-pdf"></i> Descargar PDF
            </button>
          </form>
          <?php endif; ?>
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
<<<<<<< HEAD

    <!-- Vista: Equipos -->
    <section id="vista-equipos" class="card shadow-sm mb-3 p-3" style="display:none;">
      <div class="d-flex flex-column gap-2">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
          <div class="d-flex gap-2 align-items-center flex-wrap">
            <div class="btn-group" role="group">
              <button id="eq-btn-manana" class="btn btn-brand btn-sm active">☀️ Mañana</button>
              <button id="eq-btn-tarde" class="btn btn-outline-brand btn-sm">🌙 Tarde</button>
            </div>
            <?php if (in_array($rol, ['Administrador','Encargado'])): ?>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input id="eq-search" type="text" class="form-control" placeholder="Buscar por profesor, equipo, aula o fecha">
            </div>
            <?php endif; ?>
          </div>
          <?php if (in_array($rol, ['Administrador','Encargado'])): ?>
          <form id="form-pdf-equipos" action="../view/exportar_pdf_equipos.php" method="POST" target="_blank" class="m-0 w-100 w-md-auto">
            <input type="hidden" name="start_week" id="eq-pdf-start-week" value="<?php echo date('Y-m-d'); ?>">
            <input type="hidden" name="turno" id="eq-pdf-turno" value="manana">
            <input type="hidden" name="q" id="eq-pdf-q" value="">
            <button type="submit" class="btn btn-success btn-sm w-100">
              <i class="bi bi-file-earmark-pdf"></i> Descargar PDF
            </button>
          </form>
          <?php endif; ?>
        </div>

        <div class="d-flex gap-2 align-items-center justify-content-center mb-2">
          <button id="eq-prev-week" class="btn btn-outline-brand btn-sm">
            <i class="bi bi-chevron-left"></i> Semana anterior
          </button>
          <input type="hidden" id="eq-start-of-week" value="<?php echo date('Y-m-d'); ?>">
          <span id="eq-week-range-display" class="badge bg-primary-subtle text-primary-emphasis px-3 py-2"></span>
          <button id="eq-next-week" class="btn btn-outline-brand btn-sm">
            Semana siguiente <i class="bi bi-chevron-right"></i>
          </button>
        </div>

        <div id="calendarios-equipos" class="calendarios-grid mt-2"></div>

        <div class="mt-3">
          <h5 class="text-brand">Préstamos de equipos</h5>
          <div id="tabla-equipos" class="table-responsive"></div>
        </div>
      </div>
    </section>
  <script src="../../Public/js/HistorialGlobalCalendario.js?v=<?php echo time(); ?>"></script>
  <script src="../../Public/js/HistorialEquipos.js?v=<?php echo time(); ?>"></script>
  <script>
  // Toggle de vistas
  (function(){
    const tabReserva = document.getElementById('tab-reserva');
    const tabEquipos = document.getElementById('tab-equipos');
    const vistaReserva = document.getElementById('vista-reserva');
    const vistaEquipos = document.getElementById('vista-equipos');
    if (!tabReserva || !tabEquipos || !vistaReserva || !vistaEquipos) return;
    tabReserva.addEventListener('click', function(){
      tabReserva.classList.add('btn-brand'); tabReserva.classList.remove('btn-outline-brand');
      tabEquipos.classList.remove('btn-brand'); tabEquipos.classList.add('btn-outline-brand');
      vistaReserva.style.display='block';
      vistaEquipos.style.display='none';
    });
    tabEquipos.addEventListener('click', function(){
      tabEquipos.classList.add('btn-brand'); tabEquipos.classList.remove('btn-outline-brand');
      tabReserva.classList.remove('btn-brand'); tabReserva.classList.add('btn-outline-brand');
      vistaReserva.style.display='none';
      vistaEquipos.style.display='block';
      // Disparar carga inicial si no se ha hecho
      if (window.HistorialEquipos && typeof window.HistorialEquipos.init === 'function') {
        window.HistorialEquipos.init();
      }
    });
  })();
=======
    
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
>>>>>>> 37d623eb911e485d34ce66af60d357b7fdb58415
  </script>
<?php if (!defined('EMBEDDED_VIEW')): ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>
 
