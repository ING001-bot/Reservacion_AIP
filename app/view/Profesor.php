<?php
// app/view/dashboard_profesor.php
session_start();

// Prevenir caché del navegador
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (!isset($_SESSION['usuario']) || !isset($_SESSION['tipo'])) {
    header('Location: ../../Public/index.php'); exit;
}
if ($_SESSION['tipo'] !== 'Profesor') {
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
  <title>Profesor - <?= $usuario ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../Public/css/brand.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../../Public/css/admin_mobile.css?v=<?= time() ?>">
</head>
<body class="bg-light">
<?php require __DIR__ . '/partials/navbar.php'; ?>

<!-- Contenido dinámico -->
<main class="container py-4">
  <?php
  // Definir que las vistas incluidas son embebidas (sin headers duplicados)
  if (!defined('EMBEDDED_VIEW')) { define('EMBEDDED_VIEW', true); }
  
  switch ($vista) {
      case 'configuracion':
          include 'Configuracion_Profesor.php';
          break;
      case 'reserva':
          include 'reserva.php';
          break;
      case 'prestamo':
          include 'prestamo.php';
          break;
      case 'historial':
          include 'historial.php';
          break;
      case 'password':
          include 'Cambiar_Contraseña.php';
          break;
      case 'tommibot':
          include 'Tommibot.php';
          break;
      default: ?>
          <div class="text-center mb-4">
              <h1 class="fw-bold text-brand">👨‍🏫 Panel del Profesor</h1>
              <p class="text-muted">Realice rápidamente sus reservas y prestamos.</p>
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
                          <h5 class="card-title mb-2">📅 Reservar Aula</h5>
                          <p class="card-text text-muted mb-4">Consulte disponibilidad y registre su reserva.</p>
                          <a href="?view=reserva" class="btn btn-brand mt-auto">Ir a Reservas</a>
                      </div>
                  </div>
              </div>

              <div class="col-sm-6 col-md-4">
                  <div class="card card-brand shadow-sm h-100">
                      <div class="card-body d-flex flex-column text-center">
                          <h5 class="card-title mb-2">💻 Préstamo de Equipos</h5>
                          <p class="card-text text-muted mb-4">Solicite equipos del aula de innovación.</p>
                          <a href="?view=prestamo" class="btn btn-brand mt-auto">Ir a Préstamos</a>
                      </div>
                  </div>
              </div>

              <div class="col-sm-6 col-md-4">
                  <div class="card card-brand shadow-sm h-100">
                      <div class="card-body d-flex flex-column text-center">
                          <h5 class="card-title mb-2">📄 Mis Reservas/Préstamos</h5>
                          <p class="card-text text-muted mb-4">Revise su historial y estados.</p>
                          <a href="?view=historial" class="btn btn-outline-brand mt-auto">Ver Historial</a>
                      </div>
                  </div>
              </div>

              <div class="col-sm-6 col-md-4">
                  <div class="card card-brand shadow-sm h-100">
                      <div class="card-body d-flex flex-column text-center">
                          <h5 class="card-title mb-2">🤖 TommiBot IA</h5>
                          <p class="card-text text-muted mb-4">Asistente virtual inteligente.</p>
                          <a href="?view=tommibot" class="btn btn-outline-brand mt-auto">Abrir Chat</a>
                      </div>
                  </div>
              </div>

              <div class="col-sm-6 col-md-4">
                  <div class="card card-brand shadow-sm h-100">
                      <div class="card-body d-flex flex-column text-center">
                          <h5 class="card-title mb-2">🔑 Cambiar Contraseña</h5>
                          <p class="card-text text-muted mb-4">Actualice su contraseña de acceso.</p>
                          <a href="?view=password" class="btn btn-outline-brand mt-auto">Abrir</a>
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
<!-- Flujo OTP global eliminado para evitar prompts duplicados; cada vista maneja su modal -->

</body>
</html>