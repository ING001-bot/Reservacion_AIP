<?php
// Evitar doble inclusión del navbar
if (defined('NAVBAR_INCLUDED')) { return; }
define('NAVBAR_INCLUDED', true);

if (session_status() === PHP_SESSION_NONE) session_start();
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$nombre = htmlspecialchars($_SESSION['usuario'] ?? 'Usuario');
$tipo = htmlspecialchars($_SESSION['tipo'] ?? '');
$es_encargado = ($tipo === 'Encargado');
$es_admin = ($tipo === 'Administrador');
// Determinar si se debe mostrar botón Atrás (solo en vistas internas)
$view = strtolower($_GET['view'] ?? '');
$uri  = $_SERVER['REQUEST_URI'] ?? '';
$show_back = in_array($view, ['devolucion','historial'])
  || preg_match('/Devolucion\.php|Historial(Global|Reportes)?\.php|Historial\.php/i', $uri);
// URL de regreso por rol
$backUrl = '../view/Dashboard.php';
if ($tipo === 'Encargado') { $backUrl = '../view/Encargado.php'; }
elseif ($tipo === 'Administrador') { $backUrl = '../view/Admin.php'; }
elseif ($tipo === 'Profesor') { $backUrl = '../view/Profesor.php'; }
require_once __DIR__ . '/../../controllers/PrestamoController.php';
require_once __DIR__ . '/../../config/conexion.php';
$pc = new PrestamoController($conexion);
$notis = $id_usuario ? $pc->listarNotificacionesUsuario($id_usuario, true, 10) : [];
$no_leidas = array_values(array_filter($notis, function($n){ return (int)$n['leida'] === 0; })) ;
$badge = count($no_leidas);
?>
<!-- Dependencias para iconos y offcanvas (sin integrity para evitar bloqueos) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

<!-- Menú móvil -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
  <div class="offcanvas-header bg-white border-bottom">
    <div class="d-flex align-items-center gap-3">
      <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
        <i class="fas fa-user text-brand" style="font-size:1.4rem;"></i>
      </div>
      <div>
        <div class="fw-semibold" style="line-height:1.1;"><?= $nombre ?></div>
        <small class="text-muted"><?= $tipo ?></small>
      </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
  </div>
  <div class="offcanvas-body p-0">
    <div class="d-flex flex-column h-100">
      <nav class="nav flex-column flex-grow-1 p-3">
        <a href="../view/Dashboard.php" class="nav-link d-flex align-items-center gap-3 py-3">
          <i class="fas fa-home" style="width: 24px; text-align: center;"></i>
          <span>🏠 Inicio</span>
        </a>
        <?php if ($tipo === 'Profesor'): ?>
        <a href="../view/Profesor.php?view=reserva" class="nav-link d-flex align-items-center gap-3 py-3">
          <i class="fas fa-calendar-check" style="width: 24px; text-align: center;"></i>
          <span>🗓 Reservas</span>
        </a>
        <a href="../view/Profesor.php?view=prestamo" class="nav-link d-flex align-items-center gap-3 py-3">
          <i class="fas fa-laptop" style="width: 24px; text-align: center;"></i>
          <span>💻 Préstamos</span>
        </a>
        <a href="../view/Profesor.php?view=historial" class="nav-link d-flex align-items-center gap-3 py-3">
          <i class="fas fa-history" style="width: 24px; text-align: center;"></i>
          <span>📜 Historial</span>
        </a>
        <a href="../view/Profesor.php?view=password" class="nav-link d-flex align-items-center gap-3 py-3">
          <i class="fas fa-key" style="width: 24px; text-align: center;"></i>
          <span>🔐 Cambiar contraseña</span>
        </a>
        <?php endif; ?>
        
        <?php if ($es_admin): ?>
        <a href="../view/Admin.php" class="nav-link d-flex align-items-center gap-3 py-3">
          <i class="fas fa-gauge" style="width: 24px; text-align: center;"></i>
          <span>🏠 Inicio Admin</span>
        </a>
        <a href="../view/HistorialReportes.php" class="nav-link d-flex align-items-center gap-3 py-3">
          <i class="fas fa-chart-line" style="width: 24px; text-align: center;"></i>
          <span>📊 Reportes y Filtros</span>
        </a>
        <a href="../view/HistorialGlobal.php" class="nav-link d-flex align-items-center gap-3 py-3">
          <i class="fas fa-calendar" style="width: 24px; text-align: center;"></i>
          <span>🗓 Historial Global</span>
        </a>
        <?php endif; ?>
        
        <?php if ($es_encargado): ?>
        <a href="../view/Historial.php" class="nav-link d-flex align-items-center gap-3 py-3">
          <i class="fas fa-history" style="width: 24px; text-align: center;"></i>
          <span>📜 Historial</span>
        </a>
        
        <a href="../view/Devolucion.php" class="nav-link d-flex align-items-center gap-3 py-3">
          <i class="fas fa-undo" style="width: 24px; text-align: center;"></i>
          <span>🔄 Devolución</span>
        </a>
        <?php endif; ?>
        
        <div class="mt-auto pt-3 border-top">
          <a href="../controllers/LogoutController.php" class="nav-link d-flex align-items-center gap-3 py-3 text-danger d-md-none">
            <i class="fas fa-sign-out-alt" style="width: 24px; text-align: center;"></i>
            <span>🚪 Cerrar sesión</span>
          </a>
        </div>
      </nav>
    </div>
  </div>
</div>

<!-- Barra de navegación superior -->
<nav class="navbar navbar-expand-lg navbar-dark bg-brand shadow-sm mb-0 sticky-top">
  <div class="container-fluid">
    <!-- Hamburguesa: solo móvil -->
    <?php
      $scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
      $hamburgerTarget = ($es_admin && strtolower($scriptName) === 'admin.php') ? '#sidebarAdmin' : '#mobileMenu';
      $hamburgerControls = ltrim($hamburgerTarget, '#');
    ?>
    <button class="hamburger-btn d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="<?= $hamburgerTarget ?>" aria-controls="<?= $hamburgerControls ?>" title="Menú">
      <i class="fas fa-bars"></i>
    </button>
    <!-- Botón Atrás: visible en PC y móvil solo en Devolución/Historial -->
    <?php if ($show_back): ?>
    <a class="btn-back me-2" href="<?= $backUrl ?>" title="Volver al inicio">
      <i class="fas fa-arrow-left me-1"></i> Atrás
    </a>
    <?php endif; ?>
    
    <a class="navbar-brand fw-bold d-flex align-items-center brand-navbar" href="../view/Dashboard.php" title="Juan Tomis Stack">
      <img src="../../Public/img/logo_colegio.png" alt="Logo" class="me-2 brand-logo">
      <span class="brand-title">Juan Tomis Stack</span>
    </a>
    
    <div class="d-flex align-items-center ms-auto">
      <!-- Notificaciones (Admin, Encargado y Profesor) -->
      <?php if ($id_usuario > 0): ?>
      <div class="dropdown me-3">
        <button type="button" class="btn btn-link nav-link position-relative text-white p-2 border-0" id="notifDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-display="static" aria-expanded="false">
          <i class="fas fa-bell fa-lg"></i>
          <?php if ($badge > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
              <?= $badge ?>
            </span>
          <?php endif; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end p-0 shadow" aria-labelledby="notifDropdown" style="min-width: 320px; max-width: 400px;">
          <div class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
            <strong>Notificaciones</strong>
            <button class="btn btn-sm btn-outline-secondary rounded-pill" id="notif-markall">Marcar todas</button>
          </div>
          <div class="list-group list-group-flush" id="notif-list" style="max-height: 400px; overflow-y: auto;">
            <?php if (empty($notis)): ?>
              <div class="p-3 text-muted small text-center">No hay notificaciones nuevas</div>
            <?php else: ?>
              <?php foreach ($notis as $n): ?>
                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-start" href="<?= htmlspecialchars($n['url'] ?? '#') ?>">
                  <div class="me-3">
                    <div class="fw-semibold small"><?= htmlspecialchars($n['titulo']) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars(mb_strimwidth($n['mensaje'], 0, 80, '…')) ?></div>
                  </div>
                  <?php if (!(int)$n['leida']): ?>
                    <span class="badge bg-primary rounded-pill">nuevo</span>
                  <?php endif; ?>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </ul>
      </div>
      <?php endif; ?>
      
      <!-- Perfil de usuario (informativo para evitar duplicar "Cerrar sesión") -->
      <div class="dropdown">
        <button type="button" class="btn btn-link nav-link text-white dropdown-toggle d-flex align-items-center border-0" id="userDropdown" data-bs-toggle="dropdown" data-bs-auto-close="true" data-bs-display="static" aria-expanded="false">
          <i class="fas fa-user-circle me-2"></i>
          <span><?= $nombre ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
          <li><span class="dropdown-item-text small text-muted"><?= $tipo ?></span></li>
          <li><hr class="dropdown-divider d-none d-md-block"></li>
          <li class="d-none d-md-block"><a class="dropdown-item" href="../controllers/LogoutController.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<!-- Estilos para el menú móvil -->
<style>
.offcanvas {
  width: 280px;
  max-width: 85vw;
  background-color: #ffffff; /* Fondo sólido para no mezclar con la barra azul */
}

/* Asegurar que el offcanvas y su backdrop queden por encima de la navbar */
/* Usar z-index por defecto de Bootstrap para evitar superposiciones indeseadas */
/* offcanvas y backdrop se quedan con sus z-index nativos */
/* Elevar un poco para garantizar que NO lo tape la cabecera */
.offcanvas, .offcanvas-backdrop { z-index: 1065; }
/* Backdrop no bloquea clics y no oscurece en exceso */
.offcanvas-backdrop {
  background-color: rgba(0,0,0,0.08) !important;
  pointer-events: none;
}

.offcanvas {
  transition: transform 0.3s ease-in-out;
}

.offcanvas-start {
  transform: translateX(-100%);
}

.offcanvas.offcanvas-start.show {
  transform: translateX(0);
}

.nav-link {
  border-radius: 8px;
  transition: all 0.2s;
}

.nav-link:hover, .nav-link.active {
  background-color: var(--brand-light);
  color: var(--brand-color) !important;
}

/* Ajustes para el botón de menú en móviles */
.navbar-toggler {
  border: none;
  padding: 0.5rem;
}

.navbar-toggler:focus {
  box-shadow: none;
  outline: none;
}

/* Mejoras para las notificaciones */
#notif-list {
  scrollbar-width: thin;
}

#notif-list::-webkit-scrollbar {
  width: 6px;
}

#notif-list::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

#notif-list::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}

#notif-list::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Mejoras para el tema oscuro */
body.dark .offcanvas {
  background-color: var(--panel);
  color: var(--text-dark);
}

body.dark .nav-link:hover,
body.dark .nav-link.active {
  background-color: rgba(30, 107, 214, 0.1);
}

body.dark #notif-list::-webkit-scrollbar-track {
  background: #2d3748;
}

body.dark #notif-list::-webkit-scrollbar-thumb {
  background: #4a5568;
}

body.dark #notif-list::-webkit-scrollbar-thumb:hover {
  background: #718096;
}

/* Estilos para dropdowns en navbar - Mejorados */
.navbar .btn-link {
  text-decoration: none !important;
  color: rgba(255, 255, 255, 0.95) !important;
  transition: all 0.2s ease;
  padding: 0.5rem 0.75rem;
  background: transparent !important;
  border-radius: 8px;
}

.navbar .btn-link:hover {
  color: #fff !important;
  background: rgba(255, 255, 255, 0.1) !important;
  transform: translateY(-1px);
}

.navbar .btn-link:focus,
.navbar .btn-link:active {
  color: #fff !important;
  box-shadow: none !important;
  outline: none !important;
  background: rgba(255, 255, 255, 0.15) !important;
}

.navbar .dropdown-toggle::after {
  margin-left: 0.5em;
  vertical-align: middle;
  border-top-color: rgba(255, 255, 255, 0.8);
}

/* Asegurar que los iconos sean visibles con sombra sutil */
.navbar .fas {
  color: #fff !important;
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
}

/* Badge de notificaciones mejorado */
.navbar .badge {
  font-size: 0.7rem;
  font-weight: 700;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* Asegurar clic en campana y usuario por encima de contenidos */
.navbar.sticky-top{ z-index: 1020; position: sticky; }
.dropdown-menu{ z-index: 1050; }
.navbar .btn-link, .hamburger-btn, .btn-back{ position: relative; z-index: auto; }

/* Forzar visibilidad del dropdown */
.dropdown-menu.show {
  display: block !important;
  animation: fadeInDown 0.2s ease;
}

@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>

<style>
/* Marca grande y bonita en navbar (desktop/tablet) */
.brand-navbar .brand-logo{ height:28px; width:auto; object-fit:contain; }
@media (min-width: 992px){
  .brand-navbar .brand-logo{ height:32px; }
  .brand-navbar .brand-title{ font-size: 1.25rem; font-weight: 800; letter-spacing:.2px; }
}
@media (min-width: 1400px){
  .brand-navbar .brand-title{ font-size: 1.35rem; }
}

/* Botón Atrás unificado (móvil) */
.btn-back{
  background:#fff;
  color: var(--brand-color);
  border: 1px solid rgba(255,255,255,.0);
  padding: 6px 12px;
  border-radius: 9999px;
  font-weight: 600;
  line-height: 1.2;
  box-shadow: 0 4px 10px rgba(0,0,0,.08);
  text-decoration: none;
}
.btn-back:hover{ color:#fff; background: var(--brand-dark); }

/* Botón hamburguesa bonito (móvil) */
.hamburger-btn{
  background:#fff;
  color: var(--brand-color);
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  border: 1px solid rgba(0,0,0,0.05);
  box-shadow: 0 4px 12px rgba(0,0,0,.10);
}
.hamburger-btn i{ font-size: 18px; }
.hamburger-btn:hover{ background: var(--brand-light); color: var(--brand-dark); }
body.dark .hamburger-btn{ background: var(--panel); color: var(--brand-color); border-color: var(--border-soft); }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../Public/js/notifications.js"></script>
<script>
// Inicialización simple de dropdowns (sin prevenir eventos)
document.addEventListener('DOMContentLoaded', function(){
  if (typeof bootstrap === 'undefined' || !bootstrap.Dropdown) return;
  document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(el){
    try {
      var inst = bootstrap.Dropdown.getInstance(el);
      if (inst) inst.dispose();
      new bootstrap.Dropdown(el, { autoClose: true });
    } catch(e) { /* noop */ }
  });
});
</script>

<script>
// Ocultar enlaces/botones "Volver" o "Atrás" en pantallas pequeñas
(function(){
  function hideBackButtons(){
    try{
      var isSmall = window.matchMedia('(max-width: 600px)').matches;
      var candidates = Array.prototype.slice.call(document.querySelectorAll('a.btn, button.btn, a, button'));
      candidates.forEach(function(el){
        var txt = (el.textContent || '').trim().toLowerCase();
        if (/(^|\s)(volver|atrás|atras|regresar|back)(\s|$)/.test(txt)){
          if (isSmall){ el.style.display = 'none'; }
          else { if (el.dataset._wasHidden !== '1') { el.style.display = ''; } }
        }
      });
    }catch(e){ /* noop */ }
  }
  document.addEventListener('DOMContentLoaded', hideBackButtons);
  window.addEventListener('resize', hideBackButtons);
})();
</script>
