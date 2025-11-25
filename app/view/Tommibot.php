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
          <div>
            <?php if ($rol === 'Profesor'): ?>
              <span class="tbm-chip" data-q="¿Cómo hago una reserva de aula?">📅 Hacer reserva</span>
              <span class="tbm-chip" data-q="¿Cómo solicito un préstamo de equipo?">💻 Solicitar préstamo</span>
              <span class="tbm-chip" data-q="¿Cómo veo mi historial?">📜 Ver historial</span>
              <span class="tbm-chip" data-q="Explícame el sistema como profesor">❓ Guía del sistema</span>
              <span class="tbm-chip" data-q="¿Cómo cambio mi contraseña?">🔑 Cambiar contraseña</span>
              <span class="tbm-chip" data-q="¿Qué necesito para hacer una reserva?">📋 Requisitos</span>
            <?php elseif ($rol === 'Encargado'): ?>
              <span class="tbm-chip" data-q="¿Cómo registro una devolución?">🔄 Registrar devolución</span>
              <span class="tbm-chip" data-q="¿Cómo valido un préstamo?">✅ Validar préstamo</span>
              <span class="tbm-chip" data-q="Muéstrame el historial de hoy">📜 Historial</span>
              <span class="tbm-chip" data-q="Explícame mis funciones como encargado">❓ Mis funciones</span>
              <span class="tbm-chip" data-q="¿Cómo reporto un problema con un equipo?">⚠️ Reportar problema</span>
              <span class="tbm-chip" data-q="¿Cómo verifico el estado de los equipos?">💻 Estado equipos</span>
            <?php elseif ($rol === 'Administrador'): ?>
              <span class="tbm-chip" data-q="¿Cuántos usuarios hay registrados?">👥 Total usuarios</span>
              <span class="tbm-chip" data-q="¿Cuáles son los roles de los usuarios?">🎭 Roles usuarios</span>
              <span class="tbm-chip" data-q="¿Qué números están verificados?">📞 Teléfonos verificados</span>
              <span class="tbm-chip" data-q="¿Quiénes hacen más reservas?">📊 Estadísticas reservas</span>
              <span class="tbm-chip" data-q="Muéstrame reportes del sistema">📈 Ver reportes</span>
              <span class="tbm-chip" data-q="¿Cómo gestiono usuarios?">👤 Gestionar usuarios</span>
              <span class="tbm-chip" data-q="¿Cómo administro equipos?">💻 Gestionar equipos</span>
              <span class="tbm-chip" data-q="Explícame todas las funciones de administrador">❓ Guía completa</span>
            <?php else: ?>
              <span class="tbm-chip" data-q="¿Cómo uso el sistema?">❓ Guía</span>
              <span class="tbm-chip" data-q="Ayuda">💡 Ayuda</span>
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
  document.addEventListener('click', function(e){
    const t = e.target.closest('.tbm-chip'); if(!t) return; const q = t.getAttribute('data-q');
    const inp = document.getElementById('tbm-input'); if(inp){ inp.value = q; }
    const btn = document.getElementById('tbm-send'); if(btn){ btn.click(); }
  });
</script>
