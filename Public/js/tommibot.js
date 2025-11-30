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
    
    // TTS DESACTIVADO - El bot solo responde por texto
    // NO se llama a speak() - el bot NO hablará
  }
  
  // Exponer appendMsg globalmente para el saludo automático
  window.tomibot_appendMsg = appendMsg;
  function escapeHtml(s){
    return (s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]));
  }
  
  /**
   * Procesa comandos de voz ejecutables
   * Retorna true si ejecutó un comando, false si no
   */
  function executeVoiceCommand(text) {
    const lower = text.toLowerCase().trim();
    
    // IMPORTANTE: Solo ejecutar comandos de navegación si hay palabras EXPLÍCITAS de navegación
    const hasNavigationIntent = /\b(ir a|abre|abrir|muéstrame|navega a|llévame a|ve a|vamos a)\b/i.test(lower);
    
    // Si NO hay intención de navegación, NO ejecutar comandos, dejar que el chatbot responda
    if (!hasNavigationIntent && !lower.includes('descargar') && !lower.includes('qué puedes hacer') && !lower.includes('comandos de voz')) {
      console.log('💬 No hay comando de navegación, enviando al chatbot para respuesta');
      return false; // Dejar que el chatbot responda
    }
    
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
    
    // Comandos de navegación (SOLO con palabras explícitas)
    const synonyms = {
      reservas: [
        'ir a reservas','abre reservas','abrir reservas','muéstrame reservas','navega a reservas','llévame a reservas','ve a reservas','vamos a reservas'
      ],
      prestamo: [
        'ir a préstamos','ir a prestamo','abre préstamos','abrir préstamos','muéstrame préstamos','llévame a préstamos','ve a préstamos','vamos a préstamos'
      ],
      historial: [
        'ir a historial','abre historial','abrir historial','muéstrame historial','llévame a historial','ve a historial','vamos a historial','llévame a historia','ve a historia'
      ],
      password: [
        'cambiar contraseña','ir a contraseña','modificar contraseña','actualizar contraseña','cambiar mi contraseña'
      ],
      tommibot: [
        'abrir tommibot','ir a tommibot','chat'
      ],
      usuarios: [
        'gestionar usuarios','administrar usuarios','ir a usuarios','abre usuarios'
      ],
      reportes: [
        'ver reportes','ir a reportes','abrir reportes','abre reportes'
      ],
      estadisticas: [
        'ver estadísticas','ir a estadísticas','gráficos','analytics'
      ],
      devolucion: [
        'gestionar devoluciones','ir a devoluciones','registrar devolución','abre devoluciones'
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
          return true;
        }
        appendMsg('bot', `📦 Navegando a ${dest.charAt(0).toUpperCase() + dest.slice(1)}...`);
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
    
    // Comando de ayuda (NUNCA debe navegar)
    if (lower.includes('qué puedes hacer') || lower.includes('comandos de voz') || (lower.includes('ayuda') && lower.includes('voz'))) {
      const helpMsg = '🎯 Comandos de voz disponibles:\n' +
        '• "Ir a [Reservas/Préstamos/Historial]" - Navegar a módulos\n' +
        '• "Descargar PDF" - Descargar historial\n' +
        '• "Cambiar contraseña" - Abrir cambio de contraseña\n' +
        '• También puedo responder preguntas sobre el sistema o temas generales. 😊';
      appendMsg('bot', helpMsg);
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
  let recog = null; 
  let listening = false; 
  let selectedVoice = null; 
  let isSpeaking = false; // IMPORTANTE: Iniciar en false
  let silenceTimer = null; // Timer para detectar fin de frase
  const SILENCE_DELAY = 1500; // 1.5 segundos de silencio = fin de frase
  
  function initVoice(){
    console.log('🎬 ========== INICIALIZANDO RECONOCIMIENTO DE VOZ ==========');
    console.log('👤 Rol del usuario:', window.__tbUserRole || 'No detectado');
    console.log('👤 Nombre del usuario:', window.__tbUserName || 'No detectado');
    console.log('🎯 Clase del body:', document.body.className);
    console.log('🎯 Es admin?:', document.body.classList.contains('admin-dashboard'));
    
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition; 
    if (!SR) {
      console.error('❌ Speech Recognition NO disponible en este navegador');
      console.error('  → SpeechRecognition:', window.SpeechRecognition);
      console.error('  → webkitSpeechRecognition:', window.webkitSpeechRecognition);
      return;
    }
    
    console.log('✅ Speech Recognition disponible');
    recog = new SR();
    recog.lang = 'es-ES';
    recog.continuous = true;
    recog.interimResults = true;
    recog.maxAlternatives = 3;
    
    console.log('✅ Configuración de reconocimiento:');
    console.log('  → lang:', recog.lang);
    console.log('  → continuous:', recog.continuous);
    console.log('  → interimResults:', recog.interimResults);
    console.log('  → maxAlternatives:', recog.maxAlternatives);
    
    recog.onstart = () => { 
      listening = true;
      isSpeaking = false; // IMPORTANTE: Asegurar que esté en false al iniciar
      console.log('✅ ========== RECONOCIMIENTO INICIADO ==========');
      console.log('  → listening:', listening);
      console.log('  → isSpeaking:', isSpeaking);
      console.log('  → Idioma:', recog.lang);
      
      if(elMicState()) {
        elMicState().textContent = '🎤️ Escuchando...';
        elMicState().style.color = '#ff0000'; // Rojo para indicar grabación
      }
      if(elMic()) {
        elMic().classList.add('recording'); // Agregar clase para animación
      }
      
      console.log('👂 Micrófono ACTIVO - Habla ahora (se reiniciará automáticamente si no detecta voz)');
    };
    
    recog.onend = () => { 
      listening = false; 
      
      // Limpiar timer de silencio si existe
      if (silenceTimer) {
        clearTimeout(silenceTimer);
        silenceTimer = null;
      }
      
      if(elMicState()) {
        elMicState().textContent = 'Pulsa para hablar';
        elMicState().style.color = '#667eea'; // Restaurar color original
      }
      if(elMic()) {
        elMic().classList.remove('recording');
      }
    };
    
    recog.onerror = (err) => { 
      listening = false; 
      console.error('❌ ========== ERROR DE SPEECH RECOGNITION ==========');
      console.error('  → Tipo de error:', err.error);
      console.error('  → Mensaje:', err.message);
      console.error('  → Rol del usuario:', window.__tbUserRole || 'No detectado');
      
      // NO quitar la clase 'recording' ni cambiar el estado visual para no-speech
      // porque vamos a reiniciar automáticamente
      if (err.error !== 'no-speech') {
        if(elMicState()) {
          elMicState().style.color = '#667eea';
        }
        if(elMic()) {
          elMic().classList.remove('recording');
        }
      }
      
      // Mensajes de error específicos
      let errorMsg = '';
      switch(err.error) {
        case 'no-speech':
          // Ignorar este error - es normal si el usuario no habla inmediatamente
          console.log('ℹ️ No se detectó voz (timeout normal - no es error real)');
          console.log('🔄 Reiniciando reconocimiento automáticamente...');
          console.log('⚠️ ADMINISTRADOR: Si no funciona, verifica:');
          console.log('  1. Permite el micrófono en la configuración del navegador');
          console.log('  2. Habla MÁS FUERTE y más CERCA del micrófono');
          console.log('  3. Verifica que el micrófono funcione (prueba en otra app)');
          console.log('  4. Usa Chrome o Edge (mejor compatibilidad)');
          
          // Reiniciar reconocimiento automáticamente (sin verificar el botón)
          setTimeout(() => {
            if (recog) {
              try {
                recog.start();
                console.log('✅ Reconocimiento reiniciado - HABLA AHORA MÁS FUERTE');
              } catch(e) {
                // Si falla porque ya está activo, ignorar
                if (e.name !== 'InvalidStateError') {
                  console.error('❌ Error al reiniciar:', e);
                }
              }
            }
          }, 100);
          return; // No mostrar mensaje al usuario
          break;
        case 'audio-capture':
          errorMsg = '🎙️ **Error al acceder al micrófono**\n\n' +
                     '⚠️ No se pudo capturar audio.\n\n' +
                     '**Soluciones:**\n' +
                     '1. Verifica que tu micrófono esté conectado\n' +
                     '2. Cierra otras aplicaciones que usen el micrófono\n' +
                     '3. Recarga la página (F5)\n' +
                     '4. Intenta con otro navegador (Chrome recomendado)';
          break;
        case 'not-allowed':
          errorMsg = '🎙️ **Permiso de micrófono DENEGADO**\n\n' +
                     '⚠️ Debes permitir el acceso al micrófono.\n\n' +
                     '**Cómo permitir acceso:**\n' +
                     '1. Haz clic en el ícono de candado 🔒 en la barra de direcciones\n' +
                     '2. Busca "Micrófono" en permisos\n' +
                     '3. Selecciona "Permitir"\n' +
                     '4. Recarga la página (F5)\n\n' +
                     '💡 El sistema necesita el micrófono para reconocimiento de voz.';
          break;
        case 'network':
          errorMsg = '🎙️ **Error de red**\n\nVerifica tu conexión a internet.';
          break;
        case 'aborted':
          // Silenciar este error (ocurre al detener manualmente)
          console.log('ℹ️ Reconocimiento detenido manualmente (normal)');
          return;
        default:
          errorMsg = '🎙️ **Error: ' + err.error + '**\n\nIntenta nuevamente o usa el teclado para escribir.';
      }
      
      if (errorMsg) {
        appendMsg('bot', errorMsg);
        // NO llamar speak() aquí - appendMsg() ya lo hace automáticamente
      }
    };
    
    recog.onresult = (ev) => {
      try{
        console.log('🎤 ========== VOZ DETECTADA ==========');
        console.log('  → Rol:', window.__tbUserRole);
        console.log('  → Número de resultados:', ev.results.length);
        
        const last = ev.results.length - 1;
        const text = ev.results[last][0].transcript;
        const confidence = ev.results[last][0].confidence;
        const isFinal = ev.results[last].isFinal;
        
        console.log('📝 Transcripción:', text);
        console.log('  → Confianza:', confidence);
        console.log('  → Final:', isFinal);
        console.log('  → isSpeaking:', isSpeaking);
        
        // Ignorar TODO si el bot está hablando
        if (isSpeaking) {
          console.log('⚠️ Ignorando transcripción porque el bot está hablando');
          return;
        }
        
        // Mostrar transcripción en tiempo real en el input
        if (elInput()) {
          elInput().value = text;
        }
        
        // Limpiar timer anterior si existe
        if (silenceTimer) {
          clearTimeout(silenceTimer);
          silenceTimer = null;
        }
        
        // Si es resultado FINAL, iniciar timer de silencio
        if (isFinal) {
          console.log('✅ Resultado final detectado, esperando silencio...');
          
          // Esperar SILENCE_DELAY ms de silencio antes de enviar
          silenceTimer = setTimeout(() => {
            console.log('🚀 Silencio detectado, enviando mensaje automáticamente');
            
            // NO detenemos el reconocimiento, solo enviamos el mensaje
            // El reconocimiento continúa activo para la siguiente pregunta
            
            // Enviar el mensaje automáticamente
            lastMode = 'voice';
            sendText();
            
            silenceTimer = null;
          }, SILENCE_DELAY);
        }
      }catch(e){ 
        console.error('❌ Error procesando resultado de voz:', e);
        appendMsg('bot', '❌ Error al procesar el audio. Intenta nuevamente.');
        
        // Limpiar timer
        if (silenceTimer) {
          clearTimeout(silenceTimer);
          silenceTimer = null;
        }
      }
    };
  }
  
  function toggleMic(){ 
    console.log('🎬 ========== TOGGLE MIC ==========');
    console.log('  → recog existe:', !!recog);
    console.log('  → listening:', listening);
    console.log('  → isSpeaking:', isSpeaking);
    console.log('  → Rol del usuario:', window.__tbUserRole || 'No detectado');
    
    if(!recog){ 
      console.log('🔄 Inicializando reconocimiento por primera vez...');
      initVoice(); 
      if(!recog){ 
        console.error('❌ NO se pudo inicializar el reconocimiento de voz');
        const errorMsg = '❌ **Reconocimiento de voz no disponible**\n\n' +
                        'Tu navegador no soporta reconocimiento de voz.\n\n' +
                        '✅ **Navegadores compatibles:**\n' +
                        '• Google Chrome (recomendado)\n' +
                        '• Microsoft Edge\n' +
                        '• Safari (macOS/iOS)\n\n' +
                        '💡 Actualiza tu navegador o usa uno compatible.';
        
        appendMsg('bot', errorMsg);
        
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'Navegador no compatible',
            html: 'El reconocimiento de voz no está disponible.<br><br>' +
                  '<strong>Usa:</strong><br>' +
                  '• Google Chrome (recomendado)<br>' +
                  '• Microsoft Edge<br>' +
                  '• Safari (macOS/iOS)',
            confirmButtonText: 'Entendido'
          });
        } else {
          alert('Reconocimiento de voz no soportado. Usa Chrome, Edge o Safari.');
        }
        return; 
      }
      console.log('✅ Reconocimiento inicializado exitosamente');
    }
    
    // IMPORTANTE: Si el chatbot está hablando, detener la síntesis de voz primero
    if (isSpeaking && window.speechSynthesis) {
      console.log('🛑 Deteniendo TTS porque el usuario activó el micrófono');
      window.speechSynthesis.cancel();
      isSpeaking = false;
    }
    
    if(listening){ 
      console.log('🛑 Deteniendo reconocimiento...');
      try{ 
        recog.stop(); 
        console.log('✅ Reconocimiento detenido');
      }catch(e){ 
        console.error('❌ Error al detener reconocimiento:', e);
      } 
    } else { 
      console.log('▶️ Iniciando reconocimiento...');
      console.log('  → Rol del usuario:', window.__tbUserRole || 'No detectado');
      console.log('  → Verificando permisos de micrófono...');
      
      // VERIFICACIÓN PROACTIVA DE PERMISOS (solo navegadores compatibles)
      if (navigator.permissions && navigator.permissions.query) {
        navigator.permissions.query({ name: 'microphone' })
          .then(permissionStatus => {
            console.log('🎤 Estado del permiso de micrófono:', permissionStatus.state);
            
            if (permissionStatus.state === 'denied') {
              console.error('❌ Permiso de micrófono DENEGADO por el usuario');
              const errorMsg = '🎙️ **Permiso de micrófono DENEGADO**\n\n' +
                             '⚠️ Debes permitir el acceso al micrófono.\n\n' +
                             '**Cómo permitir acceso:**\n' +
                             '1. Haz clic en el ícono de candado 🔒 en la barra de direcciones\n' +
                             '2. Busca "Micrófono" en permisos\n' +
                             '3. Selecciona "Permitir"\n' +
                             '4. Recarga la página (F5)';
              appendMsg('bot', errorMsg);
              return;
            }
            
            // Intentar iniciar reconocimiento
            try{ 
              recog.start(); 
              console.log('✅ Comando start() ejecutado correctamente');
            } catch(err) {
              console.error('❌ Error al iniciar:', err);
              handleMicrophoneError(err);
            }
          })
          .catch(err => {
            console.warn('⚠️ No se pudo verificar permisos (navegador antiguo), intentando iniciar...');
            // Si falla la verificación, intentar de todas formas
            try{ 
              recog.start(); 
              console.log('✅ Comando start() ejecutado');
            } catch(startErr) {
              console.error('❌ Error al iniciar:', startErr);
              handleMicrophoneError(startErr);
            }
          });
      } else {
        // Navegador sin API de permisos, intentar directamente
        console.log('ℹ️ API de permisos no disponible, iniciando directamente...');
        try{ 
          recog.start(); 
          console.log('✅ Comando start() ejecutado');
          console.log('💡 Si no funciona, verifica permisos manualmente en el navegador');
        }catch(err){ 
          console.error('❌ Error al iniciar reconocimiento:', err);
          handleMicrophoneError(err);
        }
      }
    }
  }
  
  // Función helper para manejar errores de micrófono
  function handleMicrophoneError(err) {
    console.error('  → Nombre del error:', err.name);
    console.error('  → Mensaje:', err.message);
        
    if(elMicState()) {
      elMicState().textContent = 'Error al iniciar';
      elMicState().style.color = '#ff0000';
    }
    
    // Mensaje de error al usuario
    let errorMsg = '❌ No se pudo iniciar el reconocimiento de voz.\n\n';
    
    if (err.name === 'InvalidStateError') {
      errorMsg += '💡 Ya hay una sesión de reconocimiento activa. Espera un momento e intenta nuevamente.';
    } else if (err.name === 'NotAllowedError') {
      errorMsg += '🔒 Permiso denegado. Ve a la configuración del navegador y permite el acceso al micrófono para este sitio.';
    } else {
      errorMsg += '💡 Verifica que tu micrófono esté conectado y funcionando, y que hayas dado permisos al navegador.';
    }
    
    appendMsg('bot', errorMsg);
  }

  function speak(text){ 
    try{
      if (!window.speechSynthesis) {
        console.error('❌ [SPEAK] speechSynthesis NO disponible en este navegador');
        return;
      }
      
      if (!text || text.trim() === '') {
        console.warn('⚠️ [SPEAK] Texto vacío, no hay nada que leer');
        return;
      }
      
      console.log('🔊 ========== SPEAK DESACTIVADO ==========');
      console.log('⚠️ TTS está desactivado - el bot solo responde por texto');
      return; // TTS desactivado - salir inmediatamente
    
    // Limpiar el texto antes de leerlo
    let cleanText = text;
    
    // ========== PASO 1: ELIMINAR TODO EL HTML ==========
    // Eliminar todas las etiquetas HTML completas (apertura y cierre)
    cleanText = cleanText.replace(/<[^>]*>/g, '');
    
    // Eliminar entidades HTML
    cleanText = cleanText.replace(/&nbsp;/g, ' ');
    cleanText = cleanText.replace(/&lt;/g, '<');
    cleanText = cleanText.replace(/&gt;/g, '>');
    cleanText = cleanText.replace(/&amp;/g, '&');
    cleanText = cleanText.replace(/&quot;/g, '"');
    cleanText = cleanText.replace(/&#39;/g, "'");
    cleanText = cleanText.replace(/&[a-z]+;/gi, ''); // Otras entidades
    
    // ========== PASO 2: ELIMINAR EMOJIS ==========
    // Eliminar emojis (todos los caracteres Unicode de emojis)
    cleanText = cleanText.replace(/[\u{1F300}-\u{1F9FF}]|[\u{2600}-\u{26FF}]|[\u{2700}-\u{27BF}]|[\u{1F000}-\u{1F02F}]|[\u{1F0A0}-\u{1F0FF}]|[\u{1F100}-\u{1F64F}]|[\u{1F680}-\u{1F6FF}]|[\u{1F910}-\u{1F96B}]|[\u{1F980}-\u{1F9E0}]/gu, '');
    
    // ========== PASO 3: ELIMINAR MARKDOWN ==========
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
    
    // Eliminar símbolos de puntuación molestos EXCEPTO / en contexto de números
    // Primero protegemos fracciones y rangos numéricos
    cleanText = cleanText.replace(/(\d+)\s*\/\s*(\d+)/g, '$1 de $2'); // "2/2" -> "2 de 2"
    
    // Ahora eliminamos símbolos molestos (sin afectar números)
    cleanText = cleanText.replace(/[%\$·"&\(\)\?¿!<>:;,\.]/g, '');
    
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
    
    // CRÍTICO: Asegurar que las voces estén cargadas antes de hablar
    const voices = window.speechSynthesis.getVoices();
    if (voices.length === 0) {
      console.warn('⚠️ [SPEAK] Voces aún no cargadas, esperando...');
      // Forzar recarga de voces
      window.speechSynthesis.onvoiceschanged = () => {
        pickYouthVoice();
        console.log('🔄 [SPEAK] Voces cargadas, reintentando speak()');
      };
      // Reintentar después de 100ms
      setTimeout(() => speak(text), 100);
      return;
    }
    
    // Si no hay voz seleccionada, intentar seleccionar una ahora
    if (!selectedVoice) {
      console.warn('⚠️ [SPEAK] No hay voz seleccionada, buscando...');
      pickYouthVoice();
    }
    
    const u = new SpeechSynthesisUtterance(cleanText);
    u.lang = 'es-PE';
    u.rate = 1.05; // un poco más ágil (voz adolescente)
    u.pitch = 1.3; // timbre juvenil
    u.volume = 0.9;
    if (selectedVoice) {
      u.voice = selectedVoice;
      console.log('🔊 [SPEAK] Usando voz:', selectedVoice.name);
    } else {
      console.warn('⚠️ [SPEAK] Usando voz por defecto del navegador');
    }
    
    // Evento cuando termine de hablar
    u.onend = () => {
      console.log('✅ [TTS] Síntesis de voz FINALIZADA');
      
      // Esperar 800ms antes de reactivar micrófono (evita capturar eco residual)
      setTimeout(() => {
        isSpeaking = false;
        console.log('✅ [TTS] isSpeaking = FALSE');
        
        // CRÍTICO: Reactivar reconocimiento SI el botón de micrófono estaba activo
        const micBtn = elMic();
        if (micBtn && micBtn.classList.contains('recording')) {
          console.log('🔄 [TTS] Reactivando reconocimiento de voz...');
          if (recog && !listening) {
            try {
              recog.start();
              console.log('✅ [TTS] Reconocimiento reactivado exitosamente');
            } catch(e) {
              console.error('❌ [TTS] Error al reactivar reconocimiento:', e);
              // Si hay error, probablemente ya está activo, ignorar
            }
          }
        }
      }, 800);
    };
    
    u.onerror = (err) => {
      console.error('❌ [TTS] ERROR en síntesis de voz:', err);
      console.error('  → Error name:', err.name);
      console.error('  → Error message:', err.message);
      isSpeaking = false;
    };
    
    u.onstart = () => {
      console.log('▶️ [TTS] Síntesis de voz INICIADA (reproduciendo audio)');
    };
    
    console.log('🔊 [SPEAK] Ejecutando speechSynthesis.speak()...');
    window.speechSynthesis.speak(u);
    console.log('✅ [SPEAK] Utterance enviado a la cola de TTS');
    console.log('  → speaking:', window.speechSynthesis.speaking);
    console.log('  → pending:', window.speechSynthesis.pending);
    console.log('========================================');
  }catch(e){ 
    console.error('❌ [SPEAK] Exception:', e);
    isSpeaking = false;
  }
  }

  function pickYouthVoice(){
    try{
      const voices = window.speechSynthesis.getVoices();
      console.log('🔍 [pickYouthVoice] Total de voces disponibles:', voices.length);
      
      // Preferencias: voces en español con nombre joven/natural
      const prefs = ['Google español', 'es-ES', 'es-US', 'es-PE', 'es-MX', 'Spanish'];
      selectedVoice = null;
      
      for (let p of prefs){
        const v = voices.find(v => (v.lang||'').toLowerCase().startsWith(p.toLowerCase()) || (v.name||'').toLowerCase().includes(p.toLowerCase()));
        if (v){ 
          selectedVoice = v; 
          console.log('✅ [pickYouthVoice] Voz seleccionada:', v.name, '(', v.lang, ')');
          break; 
        }
      }
      
      if (!selectedVoice && voices.length > 0) {
        // Si no encuentra ninguna preferencia, usa la primera voz en español disponible
        selectedVoice = voices.find(v => (v.lang||'').toLowerCase().startsWith('es'));
        console.log('⚠️ [pickYouthVoice] Voz por defecto:', selectedVoice ? selectedVoice.name : 'Ninguna');
      }
      
      if (!selectedVoice) {
        console.log('❌ [pickYouthVoice] No se encontró ninguna voz en español');
      }
    }catch(err){ 
      console.error('❌ [pickYouthVoice] Error:', err);
      selectedVoice = null; 
    }
  }

  // DOMContentLoaded eliminado - toda la inicialización se hace en initTommibot()
  
  /**
   * Función global para enviar consultas desde botones HTML (preguntas rápidas)
   */
  window.sendQuery = async function(query) {
    if (!query) return;
    
    // Mostrar la consulta como mensaje del usuario
    appendMsg('user', query);
    
    // Deshabilitar botón de envío temporalmente
    const sendBtn = elSend();
    if (sendBtn) sendBtn.disabled = true;
    
    // Intentar ejecutar comando primero
    try {
      if (executeVoiceCommand(query)) {
        if (sendBtn) sendBtn.disabled = false;
        return;
      }
    } catch(_) { /* continuar si no es comando */ }
    
    try {
      // Enviar al servidor
      const res = await fetch(apiUrl, { 
        method:'POST', 
        headers:{'Content-Type':'application/json'}, 
        body: JSON.stringify({ message: query, mode: 'text' }) 
      });
      
      if (!res.ok) {
        throw new Error(`Error HTTP: ${res.status}`);
      }
      
      const data = await res.json();
      
      if (data && data.ok === false) {
        const errorMsg = data.error || 'Ocurrió un error al procesar tu mensaje.';
        appendMsg('bot', '❌ ' + errorMsg);
        console.error('Error de Tommibot:', data.details || errorMsg);
      } else {
        const reply = data && data.reply ? data.reply : 'No pude procesar tu solicitud.';
        appendMsg('bot', reply);
        // NO llamar speak() aquí - appendMsg() ya lo hace automáticamente
        
        if (data && Array.isArray(data.actions) && data.actions.length){
          executeActions(data.actions);
        }
      }
    } catch(e) { 
      console.error('Error en sendQuery:', e);
      appendMsg('bot','❌ Error al conectar con Tommibot. Verifica tu conexión.');
    } finally {
      if (sendBtn) sendBtn.disabled = false;
    }
    
    // Limpiar input
    const inp = elInput();
    if (inp) {
      inp.value = '';
      inp.focus();
    }
  };
  
  // INICIALIZACIÓN INMEDIATA (no esperar DOMContentLoaded)
  function initTommibot() {
    console.log('🚀 ========== TOMMIBOT INICIALIZADO ==========');
    console.log('  → isSpeaking:', isSpeaking);
    console.log('  → listening:', listening);
    
    // TTS COMPLETAMENTE DESACTIVADO - El bot solo responde por texto
    const speakCheckbox = elSpeak();
    if (speakCheckbox) {
      speakCheckbox.checked = false;
      speakCheckbox.disabled = true; // Deshabilitar checkbox para que no se pueda activar
      console.log('❌ TTS DESACTIVADO - El bot solo responderá por texto');
    }
    
    // Configurar event listeners para botones e input
    const btn = elSend();
    if (btn) {
      btn.addEventListener('click', sendText);
      console.log('✅ Event listener agregado al botón Enviar');
    }
    
    const inp = elInput();
    if (inp) {
      inp.addEventListener('keydown', e => {
        if(e.key==='Enter' && !e.shiftKey){
          e.preventDefault();
          sendText();
        }
      });
      console.log('✅ Event listener agregado al input (Enter para enviar)');
    }
    
    const mic = elMic();
    if (mic) {
      mic.addEventListener('click', toggleMic);
      console.log('✅ Event listener agregado al botón de micrófono');
    } else {
      console.warn('⚠️ Botón de micrófono NO encontrado en el DOM');
    }
    
    // INICIALIZAR RECONOCIMIENTO DE VOZ
    initVoice();
    console.log('🎤 Reconocimiento de voz inicializado');
    
    console.log('💬 Tommibot configurado para respuestas SOLO POR TEXTO');
    console.log('🎤 Micrófono listo para todos los roles (Admin, Profesor, Encargado)');
  }
  
  // EXPONER FUNCIONES GLOBALMENTE para uso externo (ej: Admin fix)
  window.tomibot_initVoice = initVoice;
  window.tomibot_appendMsg = appendMsg;
  
  // Ejecutar inmediatamente si el DOM ya está listo, sino esperar
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTommibot);
  } else {
    initTommibot();
  }
})();
