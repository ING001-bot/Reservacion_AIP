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

// Asegurar que la conexión esté disponible
if (!isset($conexion)) {
    require_once __DIR__ . '/../../config/conexion.php';
}

// Obtener foto de perfil del usuario usando ConfiguracionController
$foto_perfil = null;
if ($id_usuario > 0) {
    try {
        require_once __DIR__ . '/../../controllers/ConfiguracionController.php';
        $configController = new ConfiguracionController();
        $perfilData = $configController->obtenerPerfil($id_usuario);
        if ($perfilData && !empty($perfilData['foto_perfil'])) {
            $foto_perfil = $perfilData['foto_perfil'];
        }
    } catch (Exception $e) {
        // Si hay error, simplemente no mostrar foto
        $foto_perfil = null;
    }
}

// Determinar si se debe mostrar botón Atrás (en todas las vistas internas excepto dashboards principales)
$view = strtolower($_GET['view'] ?? '');
$uri  = $_SERVER['REQUEST_URI'] ?? '';

// Lista de archivos donde NO se muestra el botón Atrás (páginas principales/dashboards)
$main_pages = ['Profesor.php', 'Encargado.php', 'Admin.php', 'Dashboard.php', 'index.php'];
$is_main_page = false;
foreach ($main_pages as $page) {
    if (stripos($uri, $page) !== false && !stripos($uri, 'view=')) {
        $is_main_page = true;
        break;
    }
}

// Mostrar botón Atrás si NO está en página principal
$show_back = !$is_main_page && ($tipo === 'Profesor' || $tipo === 'Encargado' || $tipo === 'Administrador');

// URL de regreso por rol
$backUrl = '../view/Dashboard.php';
if ($tipo === 'Encargado') { $backUrl = '../view/Encargado.php'; }
elseif ($tipo === 'Administrador') { $backUrl = '../view/Admin.php'; }
elseif ($tipo === 'Profesor') { $backUrl = '../view/Profesor.php'; }
require_once __DIR__ . '/../../controllers/PrestamoController.php';
$pc = new PrestamoController($conexion);
$notis = $id_usuario ? $pc->listarNotificacionesUsuario($id_usuario, true, 10) : [];
$no_leidas = array_values(array_filter($notis, function($n){ return (int)$n['leida'] === 0; })) ;
$badge = count($no_leidas);
?>
<!-- Dependencias para iconos y offcanvas (sin integrity para evitar bloqueos) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

<!-- Menú móvil -->
<div class="offcanvas offcanvas-start bg-brand text-white" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
  <div class="offcanvas-header bg-brand text-white border-0">
    <div class="d-flex align-items-center gap-3">
      <?php if ($foto_perfil): ?>
        <img src="../../Public/<?= htmlspecialchars($foto_perfil) ?>" alt="Foto de perfil" class="rounded-circle" style="width:48px;height:48px;object-fit:cover;border:2px solid rgba(255,255,255,0.3);">
      <?php else: ?>
        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px; background: rgba(255,255,255,.15);">
          <i class="fas fa-user text-white" style="font-size:1.4rem;"></i>
        </div>
      <?php endif; ?>
      <div>
        <div class="fw-semibold text-white" style="line-height:1.1;"><?= $nombre ?></div>
        <small class="text-white-50"><?= $tipo ?></small>
      </div>
    </div>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
  </div>
  <div class="offcanvas-body p-0 text-white">
    <div class="d-flex flex-column h-100">
      <nav class="nav flex-column flex-grow-1 p-3">
        <?php if ($tipo === 'Profesor'): ?>
        <a href="../view/Profesor.php" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-home" style="width: 24px; text-align: center;"></i>
          <span>Inicio</span>
        </a>
        <a href="../view/Profesor.php?view=reserva" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-calendar-check" style="width: 24px; text-align: center;"></i>
          <span>Reservas</span>
        </a>
        <a href="../view/Profesor.php?view=prestamo" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-laptop" style="width: 24px; text-align: center;"></i>
          <span>Préstamos</span>
        </a>
        <a href="../view/Profesor.php?view=historial" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-history" style="width: 24px; text-align: center;"></i>
          <span>Historial</span>
        </a>
        <a href="../view/Profesor.php?view=notificaciones" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-bell" style="width: 24px; text-align: center;"></i>
          <span>Notificaciones</span>
        </a>
        <a href="../view/Profesor.php?view=password" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-key" style="width: 24px; text-align: center;"></i>
          <span>Cambiar contraseña</span>
        </a>
        <?php endif; ?>
        
        <?php if ($es_admin): ?>
        <a href="../view/Admin.php" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-gauge" style="width: 24px; text-align: center;"></i>
          <span>Inicio</span>
        </a>
        <a href="../view/Admin.php?view=usuarios" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-users" style="width: 24px; text-align: center;"></i>
          <span>Usuarios</span>
        </a>
        <a href="../view/Admin.php?view=equipos" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-laptop" style="width: 24px; text-align: center;"></i>
          <span>Equipos</span>
        </a>
        <a href="../view/Admin.php?view=aulas" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-door-open" style="width: 24px; text-align: center;"></i>
          <span>Aulas</span>
        </a>
        <a href="../view/Admin.php?view=historial_global" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-calendar" style="width: 24px; text-align: center;"></i>
          <span>Historial Global</span>
        </a>
        <a href="../view/Admin.php?view=reportes" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-chart-line" style="width: 24px; text-align: center;"></i>
          <span>Reportes</span>
        </a>
        <a href="../view/Admin.php?view=notificaciones" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-bell" style="width: 24px; text-align: center;"></i>
          <span>Notificaciones</span>
        </a>
        <a href="../view/Admin.php?view=configuracion" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-cog" style="width: 24px; text-align: center;"></i>
          <span>Configuración</span>
        </a>
        <a href="../view/Admin.php?view=password" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-key" style="width: 24px; text-align: center;"></i>
          <span>Cambiar contraseña</span>
        </a>
        <?php endif; ?>
        
        <?php if ($es_encargado): ?>
        <a href="../view/Encargado.php" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-home" style="width: 24px; text-align: center;"></i>
          <span>Inicio</span>
        </a>
        <a href="../view/Encargado.php?view=calendario_equipos" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-calendar-alt" style="width: 24px; text-align: center;"></i>
          <span>Calendario Equipos</span>
        </a>
        <a href="../view/Encargado.php?view=calendario" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-calendar" style="width: 24px; text-align: center;"></i>
          <span>Calendario Aulas</span>
        </a>
        <a href="../view/Encargado.php?view=devolucion" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-undo" style="width: 24px; text-align: center;"></i>
          <span>Devolución</span>
        </a>
        <a href="../view/Encargado.php?view=notificaciones" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-bell" style="width: 24px; text-align: center;"></i>
          <span>Notificaciones</span>
        </a>
        <a href="../view/Encargado.php?view=password" class="nav-link text-white d-flex align-items-center gap-3 py-3">
          <i class="fas fa-key" style="width: 24px; text-align: center;"></i>
          <span>Cambiar contraseña</span>
        </a>
        <?php endif; ?>
        
        <div class="mt-auto pt-3 border-top border-white-50">
          <a href="../controllers/LogoutController.php" class="nav-link d-flex align-items-center gap-3 py-3 text-danger">
            <i class="fas fa-sign-out-alt" style="width: 24px; text-align: center;"></i>
            <span>Cerrar sesión</span>
          </a>
        </div>
      </nav>
    </div>
  </div>
</div>

<!-- Barra de navegación superior -->
<nav class="navbar navbar-expand-lg navbar-dark bg-brand shadow-sm mb-0 sticky-top">
  <div class="container-fluid">
    <!-- Hamburguesa: solo móvil - Abre el offcanvas -->
    <button class="hamburger-btn d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu" title="Menú">
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
                <?php
                  $tituloN = (string)($n['titulo'] ?? '');
                  $icon = 'fa-info-circle text-secondary';
                  if (stripos($tituloN, 'reserva') !== false) { $icon = 'fa-calendar-check text-success'; }
                  elseif (stripos($tituloN, 'préstamo') !== false || stripos($tituloN, 'prestamo') !== false) { $icon = 'fa-laptop text-primary'; }
                  elseif (stripos($tituloN, 'devolución') !== false || stripos($tituloN, 'devolucion') !== false) { $icon = 'fa-undo text-info'; }
                  elseif (stripos($tituloN, 'cancelación') !== false || stripos($tituloN, 'cancelacion') !== false) { $icon = 'fa-ban text-warning'; }
                  
                  // Procesar URL - Todas las notificaciones ahora van a la página de Notificaciones
                  $urlNotif = $n['url'] ?? '#';
                  // Las URLs ya vienen en el formato correcto desde NotificationService
                  // No necesitamos procesarlas
                ?>
                <a class="list-group-item list-group-item-action d-flex align-items-start gap-3" 
                   href="#" 
                   data-url="<?= htmlspecialchars($urlNotif) ?>"
                   data-notif-id="<?= (int)$n['id_notificacion'] ?>">
                  <div class="pt-1" style="width:22px; text-align:center;">
                    <i class="fas <?= $icon ?>"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="fw-semibold small mb-1"><?= htmlspecialchars($tituloN) ?></div>
                    <div class="small text-muted"><?= htmlspecialchars(mb_strimwidth($n['mensaje'], 0, 80, '…')) ?></div>
                  </div>
                  <?php if (!(int)$n['leida']): ?>
                    <span class="badge bg-primary rounded-pill ms-auto">nuevo</span>
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
          <?php if ($foto_perfil): ?>
            <img src="../../Public/<?= htmlspecialchars($foto_perfil) ?>" alt="Foto de perfil" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;border:2px solid rgba(255,255,255,0.5);">
          <?php else: ?>
            <i class="fas fa-user-circle me-2" style="font-size:1.2rem;"></i>
          <?php endif; ?>
          <span><?= $nombre ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
          <li><span class="dropdown-item-text small text-muted"><?= $tipo ?></span></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="../controllers/LogoutController.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</a></li>
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
  background-color: var(--brand-color) !important;
  position: fixed !important;
  top: 0 !important;
  bottom: 0 !important;
  left: 0 !important;
  z-index: 3200 !important;
}

.offcanvas-backdrop { 
  z-index: 3100 !important;
  background-color: rgba(0,0,0,0.5) !important;
}

/* Header del offcanvas debe estar visible siempre */
.offcanvas-header {
  position: relative !important;
  top: 0 !important;
  z-index: 3201 !important;
  background-color: var(--brand-color) !important;
  padding: 1.5rem 1rem !important;
  min-height: 80px !important;
  flex-shrink: 0 !important;
}

/* Body del offcanvas */
.offcanvas-body {
  overflow-y: auto;
  overflow-x: hidden;
  padding: 0 !important;
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

/* Asegurar clic en campana y usuario por encima de contenidos (máxima prioridad) */
.navbar.sticky-top{ z-index: 3000; position: sticky; }
.dropdown, .navbar .btn-link, .hamburger-btn, .btn-back{ position: relative; z-index: 3001; pointer-events: auto; }
.dropdown-menu{ z-index: 3002; position: absolute; }

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

/* --- Forzar scroll horizontal inferior en todas las tablas (efecto inmediato) --- */
.table-responsive{ display:block; overflow-x:auto; overflow-y:hidden; -webkit-overflow-scrolling:touch; padding-bottom:6px; margin-bottom:-6px; }
.table-responsive>table{ width:max-content; min-width:100%; }
.table th, .table td{ white-space:nowrap; vertical-align:middle; }
/* Utilidad para columnas que sí deban romper línea */
.wrap{ white-space: normal !important; word-break: break-word !important; overflow-wrap: anywhere !important; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../Public/js/notifications.js"></script>
<script src="../../Public/js/theme.js"></script>
<script>
// Exponer nombre del usuario para Tommibot (saludo por voz)
window.__tbUserName = <?= json_encode($nombre, JSON_UNESCAPED_UNICODE) ?>;
// Exponer rol del usuario para navegación por voz
window.__tbUserRole = <?= json_encode($tipo, JSON_UNESCAPED_UNICODE) ?>;
// Inicialización simple de dropdowns (sin prevenir eventos)
  document.addEventListener('DOMContentLoaded', function(){
    var supportsBootstrap = (typeof bootstrap !== 'undefined' && bootstrap.Dropdown);
    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(el){
      try {
        // Inicializa Bootstrap si está disponible
        if (supportsBootstrap){
          var inst = bootstrap.Dropdown.getInstance(el);
          if (inst) inst.dispose();
          new bootstrap.Dropdown(el, { autoClose: true });
        }
        // Fallback SIEMPRE ACTIVO: asegura apertura/cierre aunque Bootstrap falle
        el.addEventListener('click', function(ev){
          // Permitir que Bootstrap maneje primero
          setTimeout(function(){
            var menu = el.parentElement && el.parentElement.querySelector('.dropdown-menu');
            if (!menu) return;
            // Si Bootstrap no abrió, alternar manualmente
            var opened = menu.classList.contains('show');
            if (!opened){
              document.querySelectorAll('.dropdown-menu.show').forEach(function(m){ m.classList.remove('show'); });
              menu.classList.add('show');
            }
          }, 0);
        });
      } catch(e) { /* noop */ }
    });
    // Cerrar al hacer clic fuera
    document.addEventListener('click', function(e){
      document.querySelectorAll('.dropdown-menu.show').forEach(function(m){
        var toggle = m.parentElement && m.parentElement.querySelector('[data-bs-toggle="dropdown"]');
        if (toggle && (toggle.contains(e.target) || m.contains(e.target))) return;
        m.classList.remove('show');
      });
    });
  });
</script>

<?php if ($id_usuario > 0 && strtolower($view ?? '') !== 'tommibot'): ?>
  <link rel="stylesheet" href="../../Public/css/tommibot.css?v=<?= time() ?>">
  <button id="tbm-fab" class="tbm-fab" title="Abrir Tommibot"><i class="fas fa-robot"></i></button>
  <div id="tbm-panel" class="tbm-panel">
    <div class="tbm-card">
      <div class="tbm-header">
        <div class="tbm-avatar">T</div>
        <div>
          <h6 class="tbm-title mb-0">Tommibot</h6>
          <div class="tbm-sub">Asistente del sistema</div>
        </div>
        <button type="button" id="tbm-close" class="btn btn-sm btn-outline-secondary ms-auto">Cerrar</button>
      </div>
      <div class="tbm-body">
        <div class="tbm-chat">
          <div id="tbm-msgs" class="tbm-msgs"></div>
          <div class="tbm-input">
            <input id="tbm-input" class="form-control" placeholder="Escribe tu consulta..." autocomplete="off">
            <button id="tbm-send" class="btn btn-brand tbm-btn" type="button">Enviar</button>
          </div>
          <div class="mt-2 d-flex align-items-center justify-content-between">
            <div class="tbm-voice">
              <button id="tbm-mic" class="btn btn-outline-brand btn-sm tbm-btn" type="button"><i class="fas fa-microphone"></i> Hablar</button>
              <span id="tbm-mic-state" class="state">Pulsa para hablar</span>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="tbm-speak" checked>
              <label class="form-check-label" for="tbm-speak">Leer respuestas</label>
            </div>
          </div>
        </div>
        <aside>
          <div class="tbm-help mb-3">
            <h6 class="mb-2">💡 Preguntas Rápidas</h6>
            <div class="quick-queries-panel" id="quick-queries-panel">
              <!-- Botones se cargarán dinámicamente según el rol -->
            </div>
          </div>
          <div class="small text-muted" id="tbm-footer-text">
            <!-- Texto se cargará dinámicamente -->
          </div>
        </aside>
      </div>
    </div>
  </div>
  <script src="../../Public/js/tommibot.js?v=<?= time() ?>"></script>
  <script>
    (function(){
      var fab = document.getElementById('tbm-fab');
      var panel = document.getElementById('tbm-panel');
      var closeBtn = document.getElementById('tbm-close');
      
      // Abrir panel y cargar preguntas rápidas
      if (fab && panel){ 
        fab.addEventListener('click', function(){ 
          panel.classList.add('show');
          loadQuickQueries(); // Cargar preguntas al abrir
        }); 
      }
      
      if (closeBtn && panel){ 
        closeBtn.addEventListener('click', function(){ 
          panel.classList.remove('show'); 
        }); 
      }
      
      // Cargar preguntas rápidas según el rol
      function loadQuickQueries() {
        var rol = '<?= $_SESSION['tipo'] ?? '' ?>';
        var queriesPanel = document.getElementById('quick-queries-panel');
        var footerText = document.getElementById('tbm-footer-text');
        
        if (!queriesPanel) return;
        
        var queries = [];
        var footer = '';
        
        if (rol === 'Administrador') {
          queries = [
            { emoji: '👥', text: 'Total usuarios', query: '¿Cuántos usuarios hay?' },
            { emoji: '🔑', text: 'Roles del sistema', query: '¿Qué roles existen?' },
            { emoji: '📊', text: 'Info del sistema', query: 'Dame información del sistema' },
            { emoji: '👤', text: 'Gestionar usuarios', query: '¿Cómo gestiono usuarios?' },
            { emoji: '💻', text: 'Gestionar equipos', query: '¿Cómo administro equipos?' },
            { emoji: '🏫', text: 'Gestionar aulas', query: '¿Cómo gestiono aulas?' },
            { emoji: '📝', text: 'Listado usuarios', query: 'Dame un listado de usuarios' },
            { emoji: '💾', text: 'Listado equipos', query: 'Muestra los equipos' },
            { emoji: '⏰', text: 'Préstamos vencidos', query: '¿Hay préstamos vencidos?' },
            { emoji: '⚠️', text: 'Sin verificar', query: '¿Usuarios sin verificar?' },
            { emoji: '📉', text: 'Sin stock', query: '¿Equipos sin stock?' },
            { emoji: '❓', text: 'Guía completa', query: '¿Cómo funciona el sistema?' }
          ];
          footer = '• Tienes acceso completo al sistema.<br>• Puedes gestionar usuarios, equipos, aulas y ver reportes detallados.';
        } else if (rol === 'Profesor') {
          queries = [
            { emoji: '📅', text: 'Hacer reserva', query: '¿Cómo hago una reserva?' },
            { emoji: '💻', text: 'Solicitar préstamo', query: '¿Cómo solicito un préstamo?' },
            { emoji: '📜', text: 'Ver historial', query: 'Muéstrame mi historial' },
            { emoji: '❓', text: 'Guía del sistema', query: '¿Cómo funciona el sistema?' },
            { emoji: '🔑', text: 'Cambiar contraseña', query: '¿Cómo cambio mi contraseña?' },
            { emoji: '💾', text: 'Equipos disponibles', query: '¿Qué equipos están disponibles?' },
            { emoji: '📱', text: 'Verificación SMS', query: '¿Qué es la verificación SMS?' },
            { emoji: '🏫', text: 'Aulas disponibles', query: '¿Qué aulas puedo reservar?' }
          ];
          footer = '• Mínimo 1 día de anticipación para reservas y préstamos.<br>• Si tienes problemas con SMS de verificación, verifica tu número en tu perfil.';
        } else if (rol === 'Encargado') {
          queries = [
            { emoji: '🔄', text: 'Registrar devolución', query: '¿Cómo registro una devolución?' },
            { emoji: '✅', text: 'Validar préstamo', query: '¿Cómo valido un préstamo?' },
            { emoji: '📜', text: 'Ver historial', query: 'Muéstrame el historial' },
            { emoji: '❓', text: 'Guía del sistema', query: '¿Cómo funciona el sistema?' },
            { emoji: '⚠️', text: 'Reportar problema', query: '¿Cómo reporto un equipo dañado?' },
            { emoji: '📦', text: 'Préstamos activos', query: '¿Cuántos préstamos hay activos?' }
          ];
          footer = '• Puedes validar préstamos y registrar devoluciones.<br>• Reporta cualquier problema con equipos al administrador.';
        }
        
        // Generar HTML de botones
        var html = '';
        queries.forEach(function(q) {
          html += '<button class="tbm-chip" data-q="' + q.query + '">' + q.emoji + ' ' + q.text + '</button>';
        });
        
        queriesPanel.innerHTML = html;
        if (footerText) footerText.innerHTML = footer;
        
        // Agregar event listeners a los botones
        queriesPanel.querySelectorAll('.tbm-chip').forEach(function(btn) {
          btn.addEventListener('click', function() {
            var query = this.getAttribute('data-q');
            var inp = document.getElementById('tbm-input');
            if (inp) inp.value = query;
            var sendBtn = document.getElementById('tbm-send');
            if (sendBtn) sendBtn.click();
          });
        });
      }
    })();
  </script>
<?php endif; ?>

<script>
// 🔔 Sistema de notificaciones con redirección
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    // Manejar click en notificaciones individuales
    var notifItems = document.querySelectorAll('#notif-list .list-group-item');
    console.log('📋 Notificaciones encontradas:', notifItems.length);
    
    notifItems.forEach(function(item){
      item.addEventListener('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        
        var url = this.dataset.url || '#';
        var idNotif = this.dataset.notifId;
        
        console.log('🔔 Click en campanita - Redirigiendo a Notificaciones');
        
        // Marcar como leída en background
        if (idNotif) {
          fetch('../../app/api/notificaciones.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=marcar&id=' + idNotif,
            keepalive: true
          });
        }
        
        // Siempre redirigir a la página de notificaciones
        if (url && url !== '#') {
          console.log('🚀 Navegando a:', url);
          window.location.href = url;
        }
      });
    });
    
    // Marcar todas como leídas
    var markAllBtn = document.getElementById('notif-markall');
    if (markAllBtn) {
      markAllBtn.addEventListener('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        
        fetch('../../app/api/notificaciones.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'action=marcar_todas'
        }).then(function(response){
          return response.json();
        }).then(function(data){
          if (data.ok) {
            console.log('✅ Todas las notificaciones marcadas');
            window.location.reload(); // Recargar para actualizar badge
          }
        }).catch(function(err){
          console.error('Error al marcar todas:', err);
        });
      });
    }
  });
})();

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
