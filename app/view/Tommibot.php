<?php
if (session_status()===PHP_SESSION_NONE) session_start();
// Permitir acceso a Tommibot a cualquier usuario autenticado (Profesor, Administrador, Encargado)
if (!isset($_SESSION['usuario'])) { header('Location: ../../Public/index.php'); exit; }

// Prevenir caché del navegador (solo si no es vista embebida)
if (!defined('EMBEDDED_VIEW')) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
}

$nombre = htmlspecialchars($_SESSION['usuario']);
$rol = htmlspecialchars($_SESSION['tipo'] ?? '');
?>
<link rel="stylesheet" href="../../Public/css/tommibot.css?v=<?=time()?>">
<div class="tbm-wrap">
  <div class="tbm-card">
    <div class="tbm-header">
      <div class="tbm-avatar">T</div>
      <div>
        <h5 class="tbm-title mb-0">Tommibot</h5>
        <div class="tbm-sub">Asistente para docentes • Hola <?= $nombre ?>, ¿qué necesitas hoy?</div>
      </div>
    </div>
    <div class="tbm-body">
      <div>
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
      </div>
      <aside>
        <div class="tbm-help mb-3">
          <h6 class="mb-2">💡 Preguntas Rápidas</h6>
          <div class="quick-queries-panel">
            <?php if ($rol === 'Profesor'): ?>
              <button class="tbm-chip" data-q="¿Cómo hago una reserva?">📅 Hacer reserva</button>
              <button class="tbm-chip" data-q="¿Cómo solicito un préstamo?">💻 Solicitar préstamo</button>
              <button class="tbm-chip" data-q="Muéstrame mi historial">📜 Ver historial</button>
              <button class="tbm-chip" data-q="¿Cómo funciona el sistema?">❓ Guía del sistema</button>
              <button class="tbm-chip" data-q="¿Cómo cambio mi contraseña?">🔑 Cambiar contraseña</button>
              <button class="tbm-chip" data-q="¿Qué equipos están disponibles?">💾 Equipos disponibles</button>
              <button class="tbm-chip" data-q="¿Qué es la verificación SMS?">📱 Verificación SMS</button>
              <button class="tbm-chip" data-q="¿Qué aulas puedo reservar?">🏫 Aulas disponibles</button>
            <?php elseif ($rol === 'Encargado'): ?>
              <button class="tbm-chip" data-q="¿Cómo registro una devolución?">🔄 Registrar devolución</button>
              <button class="tbm-chip" data-q="¿Cómo valido un préstamo?">✅ Validar préstamo</button>
              <button class="tbm-chip" data-q="Muéstrame el historial">📜 Ver historial</button>
              <button class="tbm-chip" data-q="¿Cómo funciona el sistema?">❓ Guía del sistema</button>
              <button class="tbm-chip" data-q="¿Cómo reporto un equipo dañado?">⚠️ Reportar problema</button>
              <button class="tbm-chip" data-q="¿Cuántos préstamos hay activos?">📦 Préstamos activos</button>
            <?php elseif ($rol === 'Administrador'): ?>
              <button class="tbm-chip" data-q="¿Cuántos usuarios hay?">👥 Total usuarios</button>
              <button class="tbm-chip" data-q="¿Qué roles existen?">🔑 Roles del sistema</button>
              <button class="tbm-chip" data-q="Dame información del sistema">📊 Info del sistema</button>
              <button class="tbm-chip" data-q="¿Cómo gestiono usuarios?">👤 Gestionar usuarios</button>
              <button class="tbm-chip" data-q="¿Cómo administro equipos?">💻 Gestionar equipos</button>
              <button class="tbm-chip" data-q="¿Cómo gestiono aulas?">🏫 Gestionar aulas</button>
              <button class="tbm-chip" data-q="Dame un listado de usuarios">📝 Listado usuarios</button>
              <button class="tbm-chip" data-q="Muestra los equipos">💾 Listado equipos</button>
              <button class="tbm-chip" data-q="¿Hay préstamos vencidos?">⏰ Préstamos vencidos</button>
              <button class="tbm-chip" data-q="¿Usuarios sin verificar?">⚠️ Sin verificar</button>
              <button class="tbm-chip" data-q="¿Equipos sin stock?">📉 Sin stock</button>
              <button class="tbm-chip" data-q="¿Cómo funciona el sistema?">❓ Guía completa</button>
            <?php else: ?>
              <button class="tbm-chip" data-q="¿Cómo uso el sistema?">❓ Guía</button>
              <button class="tbm-chip" data-q="Ayuda">💡 Ayuda</button>
            <?php endif; ?>
          </div>
        </div>
        <div class="small text-muted">
          <?php if ($rol === 'Profesor'): ?>
            • Mínimo 1 día de anticipación para reservas y préstamos.
            <br>• Si tienes problemas con SMS de verificación, verifica tu número en tu perfil.
          <?php elseif ($rol === 'Encargado'): ?>
            • Puedes validar préstamos y registrar devoluciones.
            <br>• Reporta cualquier problema con equipos al administrador.
          <?php elseif ($rol === 'Administrador'): ?>
            • Tienes acceso completo al sistema.
            <br>• Puedes gestionar usuarios, equipos, aulas y ver reportes detallados.
          <?php endif; ?>
        </div>
      </aside>
    </div>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
<script>
  // Exponer datos de sesión al frontend para personalizar comportamiento por rol
  window.__tbUserName = '<?= $nombre ?>';
  window.__tbUserRole = '<?= $rol ?>';
</script>
<script src="../../Public/js/tommibot.js?v=<?=time()?>"></script>
<script>
  // Click en los botones de preguntas rápidas
  document.addEventListener('click', function(e){
    const t = e.target.closest('.tbm-chip');
    if (!t) return;
    
    const q = t.getAttribute('data-q');
    if (!q) return;
    
    // Colocar la pregunta en el input
    const inp = document.getElementById('tbm-input');
    if (inp) {
      inp.value = q;
    }
    
    // Enviar automáticamente
    const btn = document.getElementById('tbm-send');
    if (btn) {
      btn.click();
    }
  });
</script>
