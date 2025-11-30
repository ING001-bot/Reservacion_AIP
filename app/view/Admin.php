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
<body class="bg-light admin-dashboard">
<?php require __DIR__ . '/partials/navbar.php'; ?>

<div class="d-flex">
  <!-- Sidebar fija -->
  <div class="sidebar text-white" id="sidebarAdmin">
    <div class="px-3 pt-5 pb-3" style="margin-top: 20px;">
      <h4 class="text-white mb-4 fw-bold" style="font-size: 1.4rem; letter-spacing: 1px; text-transform: uppercase;">Panel Admin</h4>
      <nav class="nav flex-column" style="gap: 0.5rem;">
        <a class="nav-link link-sidebar <?= $vista==='inicio'?'active':'' ?>" href="Admin.php?view=inicio"><i class="fas fa-home me-2"></i> Inicio</a>
        <a class="nav-link link-sidebar <?= $vista==='usuarios'?'active':'' ?>" href="Admin.php?view=usuarios"><i class="fas fa-users me-2"></i> Gestión de Usuarios</a>
        <a class="nav-link link-sidebar <?= $vista==='aulas'?'active':'' ?>" href="Admin.php?view=aulas"><i class="fas fa-door-open me-2"></i> Gestión de Aulas</a>
        <a class="nav-link link-sidebar <?= $vista==='equipos'?'active':'' ?>" href="Admin.php?view=equipos"><i class="fas fa-laptop me-2"></i> Gestión de Equipos</a>
        <a class="nav-link link-sidebar <?= $vista==='tipos_equipo'?'active':'' ?>" href="Admin.php?view=tipos_equipo"><i class="fas fa-cogs me-2"></i> Tipos de Equipo</a>
        <a class="nav-link link-sidebar <?= $vista==='historial_global'?'active':'' ?>" href="Admin.php?view=historial_global"><i class="fas fa-calendar me-2"></i> Historial General</a>
        <a class="nav-link link-sidebar <?= $vista==='reportes'?'active':'' ?>" href="Admin.php?view=reportes"><i class="fas fa-chart-line me-2"></i> Reportes y Estadísticas</a>
        <a class="nav-link link-sidebar <?= $vista==='notificaciones'?'active':'' ?>" href="Admin.php?view=notificaciones"><i class="fas fa-bell me-2"></i> Notificaciones</a>
        <a class="nav-link link-sidebar <?= $vista==='password'?'active':'' ?>" href="Admin.php?view=password"><i class="fas fa-key me-2"></i> Cambiar Contraseña</a>
        <a class="nav-link link-sidebar <?= $vista==='configuracion'?'active':'' ?>" href="Admin.php?view=configuracion"><i class="fas fa-cog me-2"></i> Configuración</a>
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

<!-- Fix CRÍTICO para micrófono en Administrador -->
<script>
(function() {
  'use strict';
  
  console.log('🔧 [ADMIN FIX] ===== INICIANDO FIX DE MICRÓFONO =====');
  
  window.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 [ADMIN FIX] DOM cargado, configurando micrófono...');
    
    // PASO 1: Enumerar TODOS los dispositivos de audio
    if (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices) {
      navigator.mediaDevices.enumerateDevices()
        .then(function(devices) {
          console.log('🎤 [ADMIN FIX] Dispositivos de audio detectados:');
          
          var audioInputs = devices.filter(function(d) { return d.kind === 'audioinput'; });
          
          audioInputs.forEach(function(device, index) {
            console.log('  ' + (index + 1) + '. ' + (device.label || 'Micrófono ' + (index + 1)));
            console.log('     → ID: ' + device.deviceId.substring(0, 20) + '...');
          });
          
          if (audioInputs.length === 0) {
            console.error('❌ [ADMIN FIX] NO SE DETECTÓ NINGÚN MICRÓFONO');
            console.error('💡 Verifica que el micrófono esté conectado y habilitado en Windows');
            return;
          }
          
          // PASO 2: Buscar el micrófono FÍSICO (NO "Mezcla estéreo")
          var microfono = null;
          
          for (var i = 0; i < audioInputs.length; i++) {
            var label = (audioInputs[i].label || '').toLowerCase();
            
            // Buscar micrófono real (evitar mezcla estéreo, loopback, etc.)
            if (!label.includes('mezcla') && 
                !label.includes('stereo') && 
                !label.includes('mix') &&
                !label.includes('loopback') &&
                !label.includes('what u hear') &&
                (label.includes('micr') || label.includes('headset') || label.includes('array') || label.includes('webcam'))) {
              microfono = audioInputs[i];
              break;
            }
          }
          
          // Si no encuentra uno específico, usar el primero
          if (!microfono && audioInputs.length > 0) {
            microfono = audioInputs[0];
            console.warn('⚠️ [ADMIN FIX] No se encontró micrófono físico, usando el primero disponible');
          }
          
          if (!microfono) {
            console.error('❌ [ADMIN FIX] No se pudo seleccionar un micrófono');
            return;
          }
          
          console.log('✅ [ADMIN FIX] Micrófono seleccionado: ' + (microfono.label || 'Desconocido'));
          
          // PASO 3: Solicitar acceso EXPLÍCITO a ese micrófono específico
          navigator.mediaDevices.getUserMedia({ 
            audio: {
              deviceId: { exact: microfono.deviceId },
              echoCancellation: true,
              noiseSuppression: true,
              autoGainControl: true
            } 
          })
          .then(function(stream) {
            console.log('✅ [ADMIN FIX] ¡Acceso al micrófono OTORGADO!');
            console.log('✅ [ADMIN FIX] Dispositivo activo: ' + stream.getAudioTracks()[0].label);
            console.log('✅ [ADMIN FIX] Configuración:');
            var settings = stream.getAudioTracks()[0].getSettings();
            console.log('   → echoCancellation:', settings.echoCancellation);
            console.log('   → noiseSuppression:', settings.noiseSuppression);
            console.log('   → autoGainControl:', settings.autoGainControl);
            
            // Guardar el deviceId para uso posterior
            window.__adminMicDeviceId = microfono.deviceId;
            
            // Cerrar el stream
            stream.getTracks().forEach(function(track) {
              track.stop();
            });
            
            console.log('✅ [ADMIN FIX] Stream liberado, micrófono listo');
            console.log('');
            console.log('════════════════════════════════════════════════');
            console.log('✅ ¡MICRÓFONO CONFIGURADO CORRECTAMENTE!');
            console.log('💡 Abre el chatbot y prueba el botón del micrófono');
            console.log('💡 IMPORTANTE: Habla CLARO y CERCA del micrófono');
            console.log('════════════════════════════════════════════════');
          })
          .catch(function(err) {
            console.error('❌ [ADMIN FIX] Error al acceder al micrófono:', err.name, '-', err.message);
            console.error('');
            console.error('💡 SOLUCIONES:');
            console.error('   1. Ve a: chrome://settings/content/microphone');
            console.error('   2. Asegúrate que localhost tenga permiso');
            console.error('   3. Verifica que el micrófono no esté siendo usado por otra app');
            console.error('   4. Reinicia el navegador');
          });
        })
        .catch(function(err) {
          console.error('❌ [ADMIN FIX] Error al enumerar dispositivos:', err);
        });
    } else {
      console.error('❌ [ADMIN FIX] enumerateDevices NO disponible');
    }
  });
})();
</script>

<!-- INSTRUCCIONES PARA EL USUARIO -->
<script>
// Mostrar mensaje en consola después de 2 segundos
setTimeout(function() {
  console.log('');
  console.log('════════════════════════════════════════════════');
  console.log('📋 INSTRUCCIONES SI EL MICRÓFONO NO FUNCIONA:');
  console.log('');
  console.log('1. Ve a Configuración de Windows → Sistema → Sonido');
  console.log('2. En "Entrada", selecciona tu micrófono FÍSICO');
  console.log('3. NO selecciones "Mezcla estéreo"');
  console.log('4. Haz clic en "Propiedades del dispositivo"');
  console.log('5. Asegúrate que el volumen esté al 100%');
  console.log('6. Recarga esta página (F5)');
  console.log('');
  console.log('Si sigue sin funcionar:');
  console.log('• Prueba el micrófono en otra app (ej: Grabadora de voz)');
  console.log('• Cierra otras aplicaciones que usen el micrófono');
  console.log('• Reinicia el navegador completamente');
  console.log('════════════════════════════════════════════════');
}, 2000);
</script>
</body>
</html>
