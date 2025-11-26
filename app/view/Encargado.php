<?php
// app/view/dashboard_encargado.php
session_start();

// Prevenir caché del navegador
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (!isset($_SESSION['usuario']) || !isset($_SESSION['tipo'])) {
    header('Location: ../../Public/index.php'); exit;
}
if ($_SESSION['tipo'] !== 'Encargado') {
    header('Location: Dashboard.php'); exit;
}
$usuario = htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8');

// Verificar préstamos vencidos (solo si estamos en el dashboard principal)
$prestamosVencidos = [];
$totalVencidos = 0;
if (!isset($_GET['view']) || $_GET['view'] === 'inicio') {
    require_once __DIR__ . '/../config/conexion.php';
    require_once __DIR__ . '/../lib/AlertService.php';
    try {
        $alertService = new \App\Lib\AlertService($conexion);
        $vencidosData = $alertService->obtenerPrestamosVencidosParaDashboard();
        $prestamosVencidos = array_merge($vencidosData['prestamos'], $vencidosData['packs']);
        $totalVencidos = $vencidosData['total'];
    } catch (\Exception $e) {
        error_log("Error al verificar préstamos vencidos: " . $e->getMessage());
    }
}

// Determinar qué vista cargar
$vista = $_GET['view'] ?? 'inicio';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Encargado - <?= $usuario ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../../Public/css/brand.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../../Public/css/admin_mobile.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../../Public/css/historial_global.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../../Public/css/historial.css?v=<?= time() ?>">
</head>
<body class="bg-light">
<?php require __DIR__ . '/partials/navbar.php'; ?>

<!-- Contenido dinámico -->
<main class="container py-5">
  <?php
  // Definir que las vistas incluidas son embebidas (sin headers duplicados)
  if (!defined('EMBEDDED_VIEW')) { define('EMBEDDED_VIEW', true); }
  
  switch ($vista) {
      case 'configuracion':
          include 'Configuracion_Encargado.php';
          break;
      case 'historial':
          // Mostrar el Historial Global también para Encargado
          include 'HistorialGlobal.php';
          break;
      case 'devolucion':
          include 'devolucion.php';
          break;
      case 'password':
          include 'Cambiar_Contraseña.php';
          break;
      default: ?>
          <div class="text-center mb-4">
              <h1 class="fw-bold text-brand">🧰 Panel del Encargado</h1>
              <p class="text-muted">Gestione devoluciones y consulte historiales.</p>
          </div>
          <div class="row g-4 justify-content-center">
              <div class="col-sm-6 col-md-4">
                  <div class="card card-brand shadow-sm h-100">
                      <div class="card-body d-flex flex-column text-center">
                          <h5 class="card-title mb-2">👤 Mi Perfil</h5>
                          <p class="card-text text-muted mb-4">Gestiona tu información personal.</p>
                          <a href="?view=configuracion" class="btn btn-outline-brand mt-auto">Abrir</a>
                      </div>
                  </div>
              </div>

              <div class="col-sm-6 col-md-4">
                  <div class="card card-brand shadow-sm h-100">
                      <div class="card-body d-flex flex-column text-center">
                          <h5 class="card-title mb-2">📄 Historial</h5>
                          <p class="card-text text-muted mb-4">Reservas y préstamos del sistema.</p>
                          <a href="?view=historial" class="btn btn-outline-brand mt-auto">Abrir</a>
                      </div>
                  </div>
              </div>

              <div class="col-sm-6 col-md-4">
                  <div class="card card-brand shadow-sm h-100">
                      <div class="card-body d-flex flex-column text-center">
                          <h5 class="card-title mb-2">🔄 Devoluciones</h5>
                          <p class="card-text text-muted mb-4">Registrar devoluciones de equipos.</p>
                          <a href="?view=devolucion" class="btn btn-outline-brand mt-auto">Abrir</a>
                      </div>
                  </div>
              </div>
          </div>
  <?php
  }
  ?>
</main>

<!-- 🔔 Modal de alerta de préstamos vencidos -->
<?php if ($totalVencidos > 0): ?>
<div class="modal fade" id="modalPrestamosVencidos" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">
          <i class="fas fa-exclamation-triangle me-2"></i>
          ⚠️ Préstamos sin devolver
        </h5>
      </div>
      <div class="modal-body">
        <p class="mb-3">
          <strong>Hay <?= $totalVencidos ?> préstamo(s) que ya pasaron su hora de fin y aún no han sido devueltos:</strong>
        </p>
        <ul class="list-group">
          <?php foreach ($prestamosVencidos as $pv): ?>
            <li class="list-group-item d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-bold">
                  <?= isset($pv['id_pack']) ? 'Pack #'.$pv['id_pack'] : 'Préstamo #'.$pv['id_prestamo'] ?>
                </div>
                <div class="small text-muted">
                  Solicitante: <?= htmlspecialchars($pv['solicitante']) ?><br>
                  Hora fin: <?= $pv['hora_fin'] ?>
                </div>
              </div>
              <span class="badge bg-danger rounded-pill"><?= $pv['minutos_retraso'] ?> min</span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <a href="?view=devolucion" class="btn btn-danger">Ir a Devoluciones</a>
      </div>
    </div>
  </div>
</div>

<!-- Sonido de alerta (sutil) -->
<audio id="alertSound" style="display:none;">
  <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFQxPquXwuWkdCDGN1vLTgjMGHGi56OmyiSMCAA==" type="audio/wav">
</audio>

<script>
  // Mostrar modal y reproducir sonido si hay vencidos
  (function(){
    var modal = document.getElementById('modalPrestamosVencidos');
    if (modal) {
      var bsModal = new bootstrap.Modal(modal);
      bsModal.show();
      
      // Reproducir sonido de alerta (solo si el usuario ha interactuado)
      setTimeout(function(){
        try {
          var sound = document.getElementById('alertSound');
          if (sound) {
            sound.volume = 0.3;
            sound.play().catch(function(err){
              console.log('No se pudo reproducir sonido de alerta (requiere interacción del usuario)');
            });
          }
        } catch(e) { }
      }, 500);
    }
  })();
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../Public/js/sidebar.js"></script>
<script>
// Validación inmediata de sesión al cargar desde caché
(function() {
  // Detectar si venimos de logout
  if (sessionStorage.getItem('logged_out') === 'true') {
    sessionStorage.removeItem('logged_out');
    window.location.replace('../../Public/index.php');
  }
  
  // Validar sesión si la página viene del cache
  window.addEventListener('pageshow', function(e) {
    if (e.persisted || (window.performance && window.performance.navigation.type === 2)) {
      // Validar sesión en servidor
      fetch('/Reservacion_AIP/app/api/check_session.php', {cache: 'no-store'})
        .then(r => r.json())
        .then(d => { if (!d.logged_in) window.location.replace('../../Public/index.php'); })
        .catch(() => window.location.replace('../../Public/index.php'));
    }
  });
})();
</script>

<!-- 🔄 Verificación periódica de préstamos vencidos (solo en dashboard principal) -->
<?php if (!isset($_GET['view']) || $_GET['view'] === 'inicio'): ?>
<script>
(function(){
  var intervalo = 5 * 60 * 1000; // 5 minutos
  var ultimoTotal = <?= $totalVencidos ?>;
  
  function verificarPrestamosVencidos() {
    fetch('../../app/api/verificar_prestamos_vencidos.php', {
      method: 'GET',
      cache: 'no-store'
    })
    .then(function(response){ return response.json(); })
    .then(function(data){
      if (data.ok && data.total > 0) {
        console.log('⚠️ Préstamos vencidos detectados:', data.total);
        
        // Si hay nuevos vencidos desde la última verificación, mostrar notificación
        if (data.total > ultimoTotal) {
          // Mostrar toast o alerta sutil
          if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            var toastHTML = '<div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">' +
              '<div class="d-flex">' +
                '<div class="toast-body">⚠️ Nuevos préstamos vencidos detectados (' + data.total + '). <a href="?view=devolucion" class="text-white fw-bold">Ver devoluciones</a></div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
              '</div>' +
            '</div>';
            
            var toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            toastContainer.innerHTML = toastHTML;
            document.body.appendChild(toastContainer);
            
            var toastEl = toastContainer.querySelector('.toast');
            var toast = new bootstrap.Toast(toastEl, { delay: 5000 });
            toast.show();
            
            // Reproducir sonido
            try {
              var sound = document.getElementById('alertSound');
              if (sound) {
                sound.volume = 0.3;
                sound.play().catch(function(){});
              }
            } catch(e) {}
          }
          
          ultimoTotal = data.total;
        }
      }
    })
    .catch(function(err){
      console.error('Error al verificar préstamos vencidos:', err);
    });
  }
  
  // Verificar cada 5 minutos
  setInterval(verificarPrestamosVencidos, intervalo);
  
  // Primera verificación después de 1 minuto (para no saturar al cargar)
  setTimeout(verificarPrestamosVencidos, 60000);
})();
</script>
<?php endif; ?>

<script src="../../Public/js/auth-guard.js"></script>
<script src="../../Public/js/theme.js"></script>
</body>
</html>
