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
    
    // Si el texto contiene HTML de botones, renderizarlo directamente
    if (text.includes('<button') || text.includes('<div class=\'quick-queries\'')) {
      // Convertir saltos de línea pero mantener HTML de botones
      const formattedText = text.replace(/\n/g, '<br>');
      div.innerHTML = `${formattedText}<span class="tbm-time">${new Date().toLocaleTimeString()}</span>`;
    } else {
      // Comportamiento normal: escapar HTML
      const formattedText = escapeHtml(text).replace(/\n/g, '<br>');
      div.innerHTML = `${formattedText}<span class="tbm-time">${new Date().toLocaleTimeString()}</span>`;
    }
    
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
          // 1) Intentar clic en UI visible (mantener dentro del panel actual)
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
            // Mantener navegación embebida: no redirigir toda la página
            appendMsg('bot', '⚠️ No encontré el control para abrir "' + dest + '" dentro del panel actual. Intenta usar el menú lateral.');
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
    
    // Intentar ejecutar comando (voz o texto) primero
    try {
      if (executeVoiceCommand(text)) {
        elSend().disabled = false;
        lastMode = 'text';
        return;
      }
    } catch(_) { /* noop */ }
    
    try{
      const res = await fetch(apiUrl, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ message:text, mode:lastMode }) });
      
      if (!res.ok) {
        throw new Error(`Error HTTP: ${res.status}`);
      }
      
      const data = await res.json();
      
      // Verificar si hay error en la respuesta
      if (data && data.ok === false) {
        const errorMsg = data.error || 'Ocurrió un error al procesar tu mensaje.';
        appendMsg('bot', '❌ ' + errorMsg);
        console.error('Error de Tommibot:', data.details || errorMsg);
        elSend().disabled = false;
        lastMode = 'text';
        return;
      }
      
      const reply = data && data.reply ? data.reply : 'No pude procesar tu solicitud por ahora.';
      appendMsg('bot', reply);
      if (elSpeak() && elSpeak().checked) speak(reply);
      if (data && Array.isArray(data.actions) && data.actions.length){
        executeActions(data.actions);
      }
    }catch(e){ 
      console.error('Error en Tommibot:', e);
      appendMsg('bot','❌ Ocurrió un error al conectar con Tommibot. Por favor, verifica tu conexión e intenta nuevamente.');
    }
    finally{ elSend().disabled = false; lastMode = 'text'; }
  }

  /**
   * Ejecuta acciones enviadas por el backend (navegación, clicks, etc.)
   */
  function executeActions(actions) {
    if (!Array.isArray(actions)) return;
    
    actions.forEach(action => {
      if (!action || !action.type) return;
      
      switch (action.type) {
        case 'navigate':
          navigateToTarget(action.target);
          break;
        case 'offer':
          // Mostrar botones de confirmación para navegar
          showNavigationOffer(action.target, action.message);
          break;
        case 'click':
          if (action.selector) {
            const element = document.querySelector(action.selector);
            if (element) element.click();
          }
          break;
        default:
          console.warn('Acción desconocida:', action.type);
      }
    });
  }

  /**
   * Muestra botones de confirmación para navegar
   */
  function showNavigationOffer(target, message) {
    const chatBox = document.getElementById('tommiChatBox');
    if (!chatBox) return;
    
    const offerDiv = document.createElement('div');
    offerDiv.className = 'msg bot-msg';
    offerDiv.style.cssText = 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 15px; margin: 10px 0;';
    
    offerDiv.innerHTML = `
      <div style="margin-bottom: 10px;">${message || '¿Quieres navegar a este módulo?'}</div>
      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button onclick="window.TommibotNavigate('${target}')" style="background: white; color: #667eea; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: bold;">
          ✅ Sí, ir ahora
        </button>
        <button onclick="this.parentElement.parentElement.remove()" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid white; padding: 8px 16px; border-radius: 8px; cursor: pointer;">
          ❌ No, gracias
        </button>
      </div>
    `;
    
    chatBox.appendChild(offerDiv);
    chatBox.scrollTop = chatBox.scrollHeight;
  }

  // Función global para navegación desde botones
  window.TommibotNavigate = function(target) {
    navigateToTarget(target);
  };

  /**
   * Navega a una sección específica según el rol del usuario
   */
  function navigateToTarget(target) {
    if (!target) return;
    
    const role = (userRole || '').toLowerCase();
    let url = '';
    
    // Verificar si estamos en un dashboard que usa ?view=
    const currentPage = window.location.pathname;
    const isInDashboard = currentPage.includes('Profesor.php') || 
                          currentPage.includes('Admin.php') || 
                          currentPage.includes('Encargado.php');
    
    // Si estamos en dashboard, usar parámetros ?view=
    if (isInDashboard) {
      const viewParam = mapTargetToView(target, role);
      if (viewParam) {
        appendMsg('bot', `✅ Te llevo a ${getTargetName(target)}...`);
        setTimeout(() => {
          window.location.href = '?view=' + viewParam;
        }, 800);
        return;
      }
    }
    
    // Si no estamos en dashboard, navegar a página completa
    switch (target) {
      case 'reservas':
        if (role === 'profesor') url = 'Reserva.php';
        else if (role === 'administrador') url = 'Reserva.php';
        break;
      
      case 'prestamo':
        if (role === 'profesor') url = 'Prestamo.php';
        else if (role === 'administrador') url = 'Prestamo.php';
        break;
      
      case 'historial':
        if (role === 'administrador') url = 'HistorialGlobal.php';
        else if (role === 'encargado') url = 'HistorialGlobal.php';
        else if (role === 'profesor') url = 'Historial.php';
        break;
      
      case 'password':
        url = 'Cambiar_Contraseña.php';
        break;
      
      case 'usuarios':
        if (role === 'administrador') url = 'Crear_Administrador.php';
        break;
      
      case 'aulas':
        if (role === 'administrador') url = 'Crear_Aula.php';
        break;
      
      case 'equipos':
        if (role === 'administrador') url = 'Crear_Equipo.php';
        break;
      
      case 'reportes':
        if (role === 'administrador') url = 'HistorialReportes.php';
        break;
      
      case 'devolucion':
        if (role === 'encargado' || role === 'administrador') url = 'Devolucion.php';
        break;
      
      case 'notificaciones':
        url = 'Notificaciones.php';
        break;
      
      case 'perfil':
        if (role === 'profesor') url = 'Configuracion_Profesor.php';
        else if (role === 'administrador') url = 'Configuracion_Admin.php';
        else if (role === 'encargado') url = 'Configuracion_Encargado.php';
        break;
      
      case 'inicio':
        if (role === 'profesor') url = 'Profesor.php';
        else if (role === 'administrador') url = 'Admin.php';
        else if (role === 'encargado') url = 'Encargado.php';
        break;
      
      default:
        console.warn('Target desconocido:', target);
        return;
    }
    
    // Realizar la navegación
    if (url) {
      appendMsg('bot', `✅ Te llevo a ${getTargetName(target)}...`);
      setTimeout(() => {
        window.location.href = url;
      }, 800);
    }
  }

  /**
   * Mapea target a parámetro view para dashboards
   */
  function mapTargetToView(target, role) {
    const viewMap = {
      'reservas': 'reserva',
      'prestamo': 'prestamo',
      'historial': 'historial',
      'password': 'password',
      'usuarios': 'usuarios',
      'aulas': 'aulas',
      'equipos': 'equipos',
      'reportes': 'reportes',
      'devolucion': 'devolucion',
      'notificaciones': 'notificaciones',
      'perfil': 'configuracion',
      'inicio': 'inicio'
    };
    
    return viewMap[target] || null;
  }

  /**
   * Obtiene un nombre legible para el target
   */
  function getTargetName(target) {
    const names = {
      'reservas': 'Reservar Aula',
      'prestamo': 'Préstamo de Equipos',
      'historial': 'Historial',
      'password': 'Cambiar Contraseña',
      'usuarios': 'Gestión de Usuarios',
      'aulas': 'Gestión de Aulas',
      'equipos': 'Gestión de Equipos',
      'reportes': 'Reportes y Filtros',
      'devolucion': 'Devolución de Equipos',
      'notificaciones': 'Notificaciones',
      'perfil': 'Configuración',
      'inicio': 'Inicio'
    };
    return names[target] || target;
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
        if (typeof showWarning === 'function') {
          showWarning('Navegador no compatible', 'El reconocimiento de voz no está disponible. Usa Chrome, Edge o Safari.');
        } else {
          alert('Reconocimiento de voz no soportado en este navegador. Usa Chrome, Edge o Safari.');
        }
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
    
    // Limpiar el texto antes de leerlo
    let cleanText = text;
    
    // Eliminar emojis (todos los caracteres Unicode de emojis)
    cleanText = cleanText.replace(/[\u{1F300}-\u{1F9FF}]|[\u{2600}-\u{26FF}]|[\u{2700}-\u{27BF}]|[\u{1F000}-\u{1F02F}]|[\u{1F0A0}-\u{1F0FF}]|[\u{1F100}-\u{1F64F}]|[\u{1F680}-\u{1F6FF}]|[\u{1F910}-\u{1F96B}]|[\u{1F980}-\u{1F9E0}]/gu, '');
    
    // Eliminar markdown (**, __, etc.)
    cleanText = cleanText.replace(/\*\*/g, ''); // Eliminar **
    cleanText = cleanText.replace(/\*/g, ''); // Eliminar *
    cleanText = cleanText.replace(/\_\_/g, ''); // Eliminar __
    cleanText = cleanText.replace(/\_/g, ''); // Eliminar _
    cleanText = cleanText.replace(/\#\#\#/g, ''); // Eliminar ###
    cleanText = cleanText.replace(/\#\#/g, ''); // Eliminar ##
    cleanText = cleanText.replace(/\#/g, ''); // Eliminar #
    
    // Eliminar símbolos especiales comunes
    cleanText = cleanText.replace(/[\u2713\u2714\u2705\u2611]/g, ''); // ✓ ✔ ✅ ☑
    cleanText = cleanText.replace(/[\u274C\u2716\u2717\u274E]/g, ''); // ❌ ✖ ✗ ❎
    cleanText = cleanText.replace(/[\u26A0\u26A1\u2B50\u2B55]/g, ''); // ⚠ ⚡ ⭐ ⭕
    cleanText = cleanText.replace(/[\u{1F7E0}-\u{1F7EB}]/gu, ''); // Círculos de colores
    
    // Eliminar flechas y símbolos matemáticos
    cleanText = cleanText.replace(/[\u2190-\u21FF]/g, ''); // ← → ↑ ↓
    cleanText = cleanText.replace(/[\u2200-\u22FF]/g, ''); // Símbolos matemáticos
    
    // Eliminar paréntesis vacíos y corchetes
    cleanText = cleanText.replace(/\(\s*\)/g, '');
    cleanText = cleanText.replace(/\[\s*\]/g, '');
    
    // Eliminar saltos de línea múltiples y espacios extra
    cleanText = cleanText.replace(/\n{3,}/g, '. '); // Múltiples saltos = pausa
    cleanText = cleanText.replace(/\n{2}/g, '. '); // Doble salto = pausa
    cleanText = cleanText.replace(/\n/g, '. '); // Salto simple = pausa
    cleanText = cleanText.replace(/\s{2,}/g, ' '); // Múltiples espacios = uno
    
    // Eliminar guiones y listas
    cleanText = cleanText.replace(/^[\-\•\*]\s*/gm, ''); // Eliminar bullets
    
    // Limpiar espacios al inicio y final
    cleanText = cleanText.trim();
    
    const u = new SpeechSynthesisUtterance(cleanText);
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
    
    // NO mostrar mensaje de bienvenida automático - solo panel lateral
  });
  
  /**
   * Función global para enviar consultas desde botones HTML
   */
  window.sendQuery = function(query) {
    if (!query) return;
    
    // Mostrar la consulta como mensaje del usuario
    appendMsg('user', query);
    
    // Enviar al servidor
    sendToTommibot(query, 'text');
    
    // Actualizar el input (opcional)
    const inp = elInput();
    if (inp) {
      inp.value = '';
      inp.focus();
    }
  };
})();
