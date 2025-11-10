(function(){
  const apiUrl = '../../app/api/Tommibot_chat.php';
  const qs = sel => document.querySelector(sel);
  const elMsgs = () => qs('#tbm-msgs');
  const elInput = () => qs('#tbm-input');
  const elSend = () => qs('#tbm-send');
  const elMic = () => qs('#tbm-mic');
  const elMicState = () => qs('#tbm-mic-state');
  const elSpeak = () => qs('#tbm-speak');
  const userName = (window.__tbUserName || '').trim();
  let lastMode = 'text';
  let hasGreeted = false; // Para evitar saludos repetitivos
  let voiceCommands = null; // Cache de comandos de voz del KB
  const userRole = (window.__tbUserRole || '').trim();

  function appendMsg(kind, text){
    const wrap = elMsgs(); if (!wrap) return;
    const div = document.createElement('div');
    div.className = 'tbm-msg ' + (kind==='user'?'user':'bot');
    // Convertir saltos de línea en <br> y mantener formato
    const formattedText = escapeHtml(text).replace(/\n/g, '<br>');
    div.innerHTML = `${formattedText}<span class="tbm-time">${new Date().toLocaleTimeString()}</span>`;
    wrap.appendChild(div); wrap.scrollTop = wrap.scrollHeight;
  }
  function escapeHtml(s){
    return (s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]));
  }
  
  /**
   * Procesa comandos de voz ejecutables
   * Retorna true si ejecutó un comando, false si no
   */
  function executeVoiceCommand(text) {
    const lower = text.toLowerCase().trim();
    
    // Utilidad: intentar hacer clic en enlaces/botones con ciertos textos
    function tryClickByText(texts){
      const candidates = Array.from(document.querySelectorAll('a,button'));
      const norm = s => (s||'').toLowerCase().trim().replace(/\s+/g,' ');
      for (const t of texts){
        for (const el of candidates){
          const et = norm(el.innerText || el.textContent || '');
          if (et.includes(norm(t))) { el.click(); return true; }
        }
      }
      return false;
    }
    
    // Comandos de navegación (con sinónimos y variantes)
    const synonyms = {
      reservas: [
        'ir a reservas','abre reservas','muéstrame reservas','navega a reservas','quiero reservar','haz una reserva','hacer una reserva','reservar aula','llévame a reservas','llevarme a reservas','ve a reservas'
      ],
      prestamo: [
        'ir a préstamos','ir a prestamo','abre préstamos','muéstrame préstamos','solicitar préstamo','pedir equipo','hacer un préstamo','llévame a préstamos','llevarme a préstamos','ve a préstamos'
      ],
      historial: [
        'ir a historial','abre historial','muéstrame historial','ver mi historial','mis reservas','mis préstamos','mis prestamos','historia','llévame a historial','llevarme a historial','ve a historial','llévame a historia','llevarme a historia'
      ],
      password: [
        'cambiar contraseña','ir a contraseña','modificar contraseña','actualizar contraseña','cambiar mi contraseña'
      ],
      tommibot: [
        'abrir tommibot','ir a tommibot','chat'
      ],
      usuarios: [
        'gestionar usuarios','administrar usuarios','ir a usuarios'
      ],
      reportes: [
        'ver reportes','ir a reportes','abrir reportes','reportes y filtros'
      ],
      estadisticas: [
        'ver estadísticas','ir a estadísticas','gráficos','analytics'
      ],
      devolucion: [
        'gestionar devoluciones','ir a devoluciones','registrar devolución','devolución'
      ]
    };
    
    // Mapeo de URL por rol/destino
    function targetUrl(dest){
      const role = (userRole || '').toLowerCase();
      switch(dest){
        case 'reservas':
          return role === 'profesor' ? '../view/Profesor.php?view=reserva' : '../view/Profesor.php?view=reserva';
        case 'prestamo':
          return role === 'profesor' ? '../view/Profesor.php?view=prestamo' : '../view/Profesor.php?view=prestamo';
        case 'historial':
          if (role === 'administrador') return '../view/HistorialGlobal.php';
          if (role === 'encargado') return '../view/Historial.php';
          return '../view/Profesor.php?view=historial';
        case 'password':
          return '../view/Profesor.php?view=password';
        case 'tommibot':
          return '../view/Profesor.php?view=tommibot';
        case 'usuarios':
          return '../view/Admin.php';
        case 'reportes':
          return '../view/HistorialReportes.php';
        case 'estadisticas':
          return '../view/Admin.php#estadisticas';
        case 'devolucion':
          return '../view/Devolucion.php';
        default:
          return '../view/Dashboard.php';
      }
    }
    
    // Buscar coincidencia de destino y ejecutar
    for (const [dest, patterns] of Object.entries(synonyms)){
      if (patterns.some(p => lower.includes(p))){
        // Reglas por rol: permitir solo destinos válidos
        const role = (userRole||'').toLowerCase();
        const allowedByRole = {
          'profesor': new Set(['reservas','prestamo','historial','password','tommibot']),
          'administrador': new Set(['usuarios','reportes','estadisticas','historial','tommibot']),
          'encargado': new Set(['devolucion','historial','tommibot'])
        };
        const allow = allowedByRole[role] ? allowedByRole[role].has(dest) : true;
        if (!allow){
          appendMsg('bot', '⚠️ Esta acción no está disponible para tu rol.');
          if (elSpeak() && elSpeak().checked) speak('Acción no disponible para tu rol');
          return true;
        }
        appendMsg('bot', `📦 Navegando a ${dest.charAt(0).toUpperCase() + dest.slice(1)}...`);
        speak(`Abriendo ${dest}`);
        setTimeout(() => {
          // 1) Intentar clic en UI visible
          const clicked = tryClickByText(dest === 'historial' ? ['Historial','Mis reservas','Mis préstamos','Mis prestamos']
                                   : dest === 'reservas' ? ['Reservas','Reservar aula']
                                   : dest === 'prestamo' ? ['Préstamos','Prestamos','Solicitar préstamo']
                                   : dest === 'password' ? ['Cambiar contraseña']
                                   : dest === 'usuarios' ? ['Gestionar Usuarios','Usuarios']
                                   : dest === 'reportes' ? ['Reportes y Filtros','Reportes']
                                   : dest === 'estadisticas' ? ['Estadísticas','Analytics']
                                   : dest === 'devolucion' ? ['Devolución','Devoluciones']
                                   : []);
          if (!clicked){
            // 2) Redirigir por URL por rol
            window.location.href = targetUrl(dest);
          }
        }, 350);
        return true;
      }
    }
    
    // Comando de descarga PDF
    if (lower.includes('descargar') && (lower.includes('pdf') || lower.includes('historial') || lower.includes('reporte'))) {
      appendMsg('bot', '📎 Descargando PDF del historial...');
      speak('Descargando PDF');
      setTimeout(() => {
        const downloadBtn = document.querySelector('[data-action="download-pdf"]') || 
                           document.querySelector('.btn-download-pdf') ||
                           document.getElementById('downloadPDF');
        if (downloadBtn) {
          downloadBtn.click();
        } else {
          appendMsg('bot', '⚠️ No se encontró el botón de descarga. Asegúrate de estar en la vista Historial.');
        }
      }, 500);
      return true;
    }
    
    // Comando de ayuda
    if (lower.includes('qué puedes hacer') || lower.includes('comandos de voz') || (lower.includes('ayuda') && lower.includes('voz'))) {
      const helpMsg = '🎯 Comandos de voz disponibles:\n' +
        '• "Ir a [Reservas/Préstamos/Historial]" - Navegar a módulos\n' +
        '• "Descargar PDF" - Descargar historial\n' +
        '• "Cambiar contraseña" - Abrir cambio de contraseña\n' +
        '• También puedo responder preguntas sobre el sistema o temas generales. 😊';
      appendMsg('bot', helpMsg);
      speak('Te muestro los comandos disponibles');
      return true;
    }
    
    return false; // No se ejecutó ningún comando
  }

  async function sendText(){
    const inp = elInput(); if (!inp) return; const text = (inp.value||'').trim(); if (!text) return;
    appendMsg('user', text); inp.value = ''; elSend().disabled = true;
    
    // Intentar ejecutar comando de voz primero
    if (lastMode === 'voice' && executeVoiceCommand(text)) {
      elSend().disabled = false;
      lastMode = 'text';
      return;
    }
    
    try{
      const res = await fetch(apiUrl, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ message:text, mode:lastMode }) });
      const data = await res.json();
      const reply = data && data.reply ? data.reply : 'No pude procesar tu solicitud por ahora.';
      appendMsg('bot', reply);
      if (elSpeak() && elSpeak().checked) speak(reply);
    }catch(e){ appendMsg('bot','Ocurrió un error al conectar con Tommibot.'); }
    finally{ elSend().disabled = false; lastMode = 'text'; }
  }

  // Voice: Web Speech API
  let recog = null; let listening = false; let selectedVoice = null;
  function initVoice(){
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition; if (!SR) return;
    recog = new SR();
    recog.lang = 'es-PE';
    recog.interimResults = false; recog.maxAlternatives = 1;
    recog.onstart = () => { 
      listening = true; 
      if(elMicState()) elMicState().textContent = '🎙️ Escuchando...';
      // Solo saludar la primera vez
      if (!hasGreeted) {
        if (userName) { 
          speak('Hola ' + userName + ', te escucho.'); 
        } else { 
          speak('Te escucho.'); 
        }
        hasGreeted = true;
      }
    };
    recog.onend = () => { listening = false; if(elMicState()) elMicState().textContent = 'Pulsa para hablar'; };
    recog.onerror = (err) => { 
      listening = false; 
      if(elMicState()) elMicState().textContent = 'Error de micrófono';
      console.error('Speech recognition error:', err);
    };
    recog.onresult = (ev) => {
      try{
        const text = ev.results[0][0].transcript;
        if (elInput()) elInput().value = text;
        lastMode = 'voice';
        sendText();
      }catch(_){ /* noop */ }
    };
  }
  function toggleMic(){ 
    if(!recog){ 
      initVoice(); 
      if(!recog){ 
        alert('Reconocimiento de voz no soportado en este navegador. Usa Chrome, Edge o Safari.'); 
        return; 
      } 
    }
    if(listening){ 
      try{ recog.stop(); }catch(_){ } 
    } else { 
      try{ recog.start(); }catch(err){ 
        console.error('Error starting recognition:', err);
        if(elMicState()) elMicState().textContent = 'Error al iniciar';
      } 
    }
  }

  function speak(text){ try{
    if (!window.speechSynthesis) return;
    // Cancelar voz previa si existe
    window.speechSynthesis.cancel();
    
    const u = new SpeechSynthesisUtterance(text);
    u.lang = 'es-PE';
    u.rate = 1.05; // un poco más ágil (voz adolescente)
    u.pitch = 1.3; // timbre juvenil
    u.volume = 0.9;
    if (selectedVoice) u.voice = selectedVoice;
    window.speechSynthesis.speak(u);
  }catch(_){ }
  }

  function pickYouthVoice(){
    try{
      const voices = window.speechSynthesis.getVoices();
      // Preferencias: voces en español con nombre joven/natural
      const prefs = ['Google español', 'es-ES', 'es-US', 'es-PE'];
      selectedVoice = null;
      for (let p of prefs){
        const v = voices.find(v => (v.lang||'').toLowerCase().startsWith(p.toLowerCase()) || (v.name||'').toLowerCase().includes(p.toLowerCase()));
        if (v){ selectedVoice = v; break; }
      }
    }catch(_){ selectedVoice = null; }
  }

  document.addEventListener('DOMContentLoaded', function(){
    if (window.speechSynthesis){
      pickYouthVoice();
      window.speechSynthesis.onvoiceschanged = pickYouthVoice;
    }
    const btn = elSend(); if (btn) btn.addEventListener('click', sendText);
    const inp = elInput(); if (inp) inp.addEventListener('keydown', e => { if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); sendText(); }});
    const mic = elMic(); if (mic) mic.addEventListener('click', toggleMic);
    initVoice();
    
    // Mensaje de bienvenida personalizado por rol
    const role = (userRole||'').toLowerCase();
    let welcomeMsg = '';
    if (role === 'administrador'){
      welcomeMsg = '🤖 ¡Hola! Soy Tommibot. Te ayudo con gestión de usuarios, reportes y estadísticas.\n' +
                   'Prueba: "Ver reportes", "Gestionar usuarios" o "Ver estadísticas".';
    } else if (role === 'encargado'){
      welcomeMsg = '🤖 ¡Hola! Soy Tommibot. Te ayudo con devoluciones y control de préstamos.\n' +
                   'Prueba: "Ir a devoluciones" o "Ver historial".';
    } else {
      welcomeMsg = '🤖 ¡Hola! Soy Tommibot. Te ayudo con reservas, préstamos e historial.\n' +
                   'Prueba: "Ir a reservas", "Ir a préstamos" o "Muéstrame historial".';
    }
    appendMsg('bot', welcomeMsg);
  });
})();
