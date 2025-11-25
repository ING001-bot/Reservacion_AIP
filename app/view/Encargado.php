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
<script src="../../Public/js/auth-guard.js"></script>
<script src="../../Public/js/theme.js"></script>
</body>
</html>
