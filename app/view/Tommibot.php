<?php
if (session_status()===PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['tipo'] ?? '') !== 'Profesor') { header('Location: ../../Public/index.php'); exit; }
$nombre = htmlspecialchars($_SESSION['usuario']);
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
          <h6 class="mb-2">Sugerencias</h6>
          <div>
            <span class="tbm-chip" data-q="¿Cómo reservo un aula?">¿Cómo reservo un aula?</span>
            <span class="tbm-chip" data-q="Quiero pedir un préstamo de equipo">Préstamo de equipo</span>
            <span class="tbm-chip" data-q="Ver mi historial">Ver mi historial</span>
            <span class="tbm-chip" data-q="Cambiar mi contraseña">Cambiar contraseña</span>
          </div>
        </div>
        <div class="small text-muted">
          • Mínimo 1 día de anticipación para reservas y préstamos.
          <br>• Si tienes problemas con SMS de verificación, verifica tu número en tu perfil.
        </div>
      </aside>
    </div>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
<script src="../../Public/js/tommibot.js?v=<?=time()?>"></script>
<script>
  document.addEventListener('click', function(e){
    const t = e.target.closest('.tbm-chip'); if(!t) return; const q = t.getAttribute('data-q');
    const inp = document.getElementById('tbm-input'); if(inp){ inp.value = q; }
    const btn = document.getElementById('tbm-send'); if(btn){ btn.click(); }
  });
</script>
