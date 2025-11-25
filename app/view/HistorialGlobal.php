<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['tipo'])) {
  header('Location: ../../Public/index.php');
  exit;
}

// Prevenir caché del navegador (solo si no es vista embebida)
if (!defined('EMBEDDED_VIEW')) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
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
        <?php if ($rol === 'Administrador'): ?>
          <a class="btn btn-outline-brand btn-sm" href="Admin.php?view=reportes">📊 Reportes / Filtros</a>
        <?php endif; ?>
        <?php if (in_array($rol, ['Administrador','Encargado','Profesor'])): ?>
        <form id="form-pdf-unificado" action="../view/exportar_pdf.php" method="POST" target="_blank" class="m-0">
          <input type="hidden" name="start_week" id="uni-pdf-start-week" value="<?php echo date('Y-m-d'); ?>">
          <input type="hidden" name="turno" id="uni-pdf-turno" value="manana">
          <input type="hidden" name="q" id="uni-pdf-q" value="">
          <button type="submit" class="btn btn-success btn-sm" data-confirm="¿Descargar PDF de la semana visible?" data-confirm-ok="Sí, descargar" data-confirm-cancel="Volver">
            <i class="bi bi-file-earmark-pdf"></i> Descargar PDF
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="mb-3 d-flex gap-2 flex-wrap">
      <button id="tab-reserva" class="btn btn-brand btn-sm">Historial / Reserva</button>
      <button id="tab-equipos" class="btn btn-outline-brand btn-sm">Historial / Equipos</button>
    </div>

    <!-- Vista: Reserva (se mantiene igual) -->
    <section id="vista-reserva" class="card shadow-sm mb-3 p-3">
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
    const formPdf = document.getElementById('form-pdf-unificado');
    const uniWeek = document.getElementById('uni-pdf-start-week');
    const uniTurno = document.getElementById('uni-pdf-turno');
    const uniQ = document.getElementById('uni-pdf-q');

    if (!tabReserva || !tabEquipos || !vistaReserva || !vistaEquipos) return;

    function syncReservaParams(){
      const start = document.getElementById('start-of-week');
      if (start && uniWeek) uniWeek.value = start.value || '';
      if (formPdf) formPdf.action = '../view/exportar_pdf.php';
      // Reserva no usa filtro q
      if (uniQ) uniQ.value = '';
    }

    function syncEquiposParams(){
      const start = document.getElementById('eq-start-of-week');
      if (start && uniWeek) uniWeek.value = start.value || '';
      if (formPdf) formPdf.action = '../view/exportar_pdf_equipos.php';
      const q = document.getElementById('eq-search');
      if (uniQ) uniQ.value = q ? (q.value || '') : '';
    }

    tabReserva.addEventListener('click', function(){
      tabReserva.classList.add('btn-brand'); tabReserva.classList.remove('btn-outline-brand');
      tabEquipos.classList.remove('btn-brand'); tabEquipos.classList.add('btn-outline-brand');
      vistaReserva.style.display='block';
      vistaEquipos.style.display='none';
      syncReservaParams();
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
      syncEquiposParams();
    });

    // Sincronizar semana al cambiar flechas
    const prevWeek = document.getElementById('prev-week');
    const nextWeek = document.getElementById('next-week');
    const eqPrevWeek = document.getElementById('eq-prev-week');
    const eqNextWeek = document.getElementById('eq-next-week');
    if (prevWeek) prevWeek.addEventListener('click', function(){ setTimeout(syncReservaParams, 0); });
    if (nextWeek) nextWeek.addEventListener('click', function(){ setTimeout(syncReservaParams, 0); });
    if (eqPrevWeek) eqPrevWeek.addEventListener('click', function(){ setTimeout(syncEquiposParams, 0); });
    if (eqNextWeek) eqNextWeek.addEventListener('click', function(){ setTimeout(syncEquiposParams, 0); });

    // Inicial: vista reserva
    syncReservaParams();
  })();
  (function(){
    var bm = document.getElementById('btn-manana');
    var bt = document.getElementById('btn-tarde');
    var ebm = document.getElementById('eq-btn-manana');
    var ebt = document.getElementById('eq-btn-tarde');
    var uniTurno = document.getElementById('uni-pdf-turno');
    function setTurno(val){ if (uniTurno) uniTurno.value = val; }
    if (bm && bt){
      bm.addEventListener('click', function(){
        bm.classList.add('btn-brand'); bm.classList.remove('btn-outline-brand');
        bt.classList.remove('btn-brand'); bt.classList.add('btn-outline-brand');
        setTurno('manana');
      });
      bt.addEventListener('click', function(){
        bt.classList.add('btn-brand'); bt.classList.remove('btn-outline-brand');
        bm.classList.remove('btn-brand'); bm.classList.add('btn-outline-brand');
        setTurno('tarde');
      });
    }
    if (ebm && ebt){
      ebm.addEventListener('click', function(){
        ebm.classList.add('btn-brand'); ebm.classList.remove('btn-outline-brand');
        ebt.classList.remove('btn-brand'); ebt.classList.add('btn-outline-brand');
        setTurno('manana');
      });
      ebt.addEventListener('click', function(){
        ebt.classList.add('btn-brand'); ebt.classList.remove('btn-outline-brand');
        ebm.classList.remove('btn-brand'); ebm.classList.add('btn-outline-brand');
        setTurno('tarde');
      });
    }
  })();
  </script>
<?php if (!defined('EMBEDDED_VIEW')): ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>
 
