<?php
// app/view/dashboard_admin.php
session_start();

// Prevenir caché del navegador
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (!isset($_SESSION['usuario']) || !isset($_SESSION['tipo'])) {
    header('Location: ../../Public/index.php'); exit;
}
if ($_SESSION['tipo'] !== 'Administrador') {
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
  <title>Administrador - <?= $usuario ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Estilos de marca -->
  <link rel="stylesheet" href="../../Public/css/brand.css?v=<?php echo time(); ?>">
  <!-- Responsive Admin -->
  <link rel="stylesheet" href="../../Public/css/admin_mobile.css?v=<?php echo time(); ?>">
  <!-- Calendarios/Historial estilos base para vistas embebidas -->
  <link rel="stylesheet" href="../../Public/css/historial_global.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../../Public/css/historial.css?v=<?php echo time(); ?>">
</head>
<body class="bg-light">
<?php require __DIR__ . '/partials/navbar.php'; ?>

<div class="d-flex">
  <!-- Sidebar fija -->
  <div class="sidebar text-white" id="sidebarAdmin">
    <div class="px-3 pt-5 pb-3" style="margin-top: 20px;">
      <h4 class="text-white mb-4 fw-bold" style="font-size: 1.4rem; letter-spacing: 1px; text-transform: uppercase;">Panel Admin</h4>
      <nav class="nav flex-column" style="gap: 0.5rem;">
        <a class="nav-link link-sidebar <?= $vista==='inicio'?'active':'' ?>" href="Admin.php?view=inicio">🏠 Inicio</a>
        <a class="nav-link link-sidebar <?= $vista==='usuarios'?'active':'' ?>" href="Admin.php?view=usuarios">👤 Gestión de Usuarios</a>
        <a class="nav-link link-sidebar <?= $vista==='aulas'?'active':'' ?>" href="Admin.php?view=aulas">🏫 Gestión de Aulas</a>
        <a class="nav-link link-sidebar <?= $vista==='equipos'?'active':'' ?>" href="Admin.php?view=equipos">💻 Gestión de Equipos</a>
        <a class="nav-link link-sidebar <?= $vista==='tipos_equipo'?'active':'' ?>" href="Admin.php?view=tipos_equipo">⚙ Tipos de Equipo</a>
        <a class="nav-link link-sidebar <?= $vista==='historial_global'?'active':'' ?>" href="Admin.php?view=historial_global">🗂️ Historial General</a>
        <a class="nav-link link-sidebar <?= $vista==='reportes'?'active':'' ?>" href="Admin.php?view=reportes">📊 Reportes y Estadísticas</a>
        <a class="nav-link link-sidebar <?= $vista==='notificaciones'?'active':'' ?>" href="Admin.php?view=notificaciones">🔔 Notificaciones</a>
        <a class="nav-link link-sidebar <?= $vista==='password'?'active':'' ?>" href="Admin.php?view=password">🔑 Cambiar Contraseña</a>
        <a class="nav-link link-sidebar <?= $vista==='configuracion'?'active':'' ?>" href="Admin.php?view=configuracion">⚙️ Configuración</a>
      </nav>
      <div class="mt-auto pt-4 border-top border-white border-opacity-25">
        <div class="small text-white-50">Admin: <?= $usuario ?></div>
      </div>
    </div>
  </div>

  <!-- Contenido dinámico -->
  <main class="content-with-sidebar p-4 flex-grow-1 min-vh-100">
    <?php
    // Señal para que las vistas no rendericen su propio <html>/<head>/<body>
    if (!defined('EMBEDDED_VIEW')) { define('EMBEDDED_VIEW', true); }
    switch ($vista) {
      case 'configuracion':
        include 'Configuracion_Admin.php';
        break;
      case 'usuarios':
        include 'Registrar_Usuario.php';
        break;
      case 'aulas':
        include 'Registrar_Aula.php';
        break;
      case 'equipos':
        include 'registrar_equipo.php';
        break;
      case 'tipos_equipo':
        include 'Gestion_Tipos_Equipo.php';
        break;
      case 'historial_global':
        include 'HistorialGlobal.php';
        break;
      case 'reportes':
        include 'HistorialReportes.php';
        break;
      case 'notificaciones':
        include 'Notificaciones.php';
        break;
      case 'password':
        include 'cambiar_contraseña.php';
        break;
      default: ?>
        <?php
          $imgWelcome = '../../Public/img/colegio.png';
          if (!file_exists(__DIR__ . '/../../Public/img/colegio.png') && file_exists(__DIR__ . '/../../Public/img/colegio.jpg')) {
            $imgWelcome = '../../Public/img/colegio.jpg';
          }
        ?>
        <div class="position-relative" style="margin:-1.5rem -2rem -2rem -2rem; width:auto; height: calc(100vh + 3.5rem); overflow:hidden; pointer-events:none; z-index:0;">
          <img src="<?= htmlspecialchars($imgWelcome) ?>" alt="Imagen de inicio" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; object-position:center; display:block; pointer-events:none;">
        </div>
      <?php
    }
    ?>
  </main>
</div>

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
<?php if ($vista === 'usuarios'): ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../../Public/js/usuarios.js?v=<?= time() ?>"></script>
<?php elseif ($vista === 'aulas'): ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../../Public/js/aulas.js?v=<?= time() ?>"></script>
<?php elseif ($vista === 'equipos'): ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../../Public/js/equipo.js?v=<?= time() ?>"></script>
<?php elseif ($vista === 'tipos_equipo'): ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../../Public/js/tipos_equipo.js?v=<?= time() ?>"></script>
<?php elseif ($vista === 'reportes'): ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="../../Public/js/HistorialReportes.js?v=<?= time() ?>"></script>
  <script src="../../Public/js/HistorialEstadisticas.js?v=<?= time() ?>"></script>
<?php elseif ($vista === 'historial_global'): ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="../../Public/js/HistorialGlobal.js?v=<?= time() ?>"></script>
  <script src="../../Public/js/HistorialGlobalCalendario.js?v=<?= time() ?>"></script>
<?php endif; ?>
<script src="../../Public/js/theme.js"></script>
</body>
</html>
