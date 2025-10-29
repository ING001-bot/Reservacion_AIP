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

  function appendMsg(kind, text){
    const wrap = elMsgs(); if (!wrap) return;
    const div = document.createElement('div');
    div.className = 'tbm-msg ' + (kind==='user'?'user':'bot');
    div.innerHTML = `${escapeHtml(text)}<span class="tbm-time">${new Date().toLocaleTimeString()}</span>`;
    wrap.appendChild(div); wrap.scrollTop = wrap.scrollHeight;
  }
  function escapeHtml(s){
    return (s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]));
  }

  async function sendText(){
    const inp = elInput(); if (!inp) return; const text = (inp.value||'').trim(); if (!text) return;
    appendMsg('user', text); inp.value = ''; elSend().disabled = true;
    try{
      const res = await fetch(apiUrl, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ message:text }) });
      const data = await res.json();
      const reply = data && data.reply ? data.reply : 'No pude procesar tu solicitud por ahora.';
      appendMsg('bot', reply);
      if (elSpeak() && elSpeak().checked) speak(reply);
    }catch(e){ appendMsg('bot','Ocurrió un error al conectar con Tommibot.'); }
    finally{ elSend().disabled = false; }
  }

  // Voice: Web Speech API
  let recog = null; let listening = false; let selectedVoice = null;
  function initVoice(){
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition; if (!SR) return;
    recog = new SR();
    recog.lang = 'es-PE';
    recog.interimResults = false; recog.maxAlternatives = 1;
    recog.onstart = () => { listening = true; if(elMicState()) elMicState().textContent = 'Escuchando…'; if (userName) { speak('Hola ' + userName + ', te escucho.'); } else { speak('Hola, te escucho.'); } };
    recog.onend = () => { listening = false; if(elMicState()) elMicState().textContent = 'Pulsa para hablar'; };
    recog.onerror = () => { listening = false; if(elMicState()) elMicState().textContent = 'Error de micrófono'; };
    recog.onresult = (ev) => {
      try{
        const text = ev.results[0][0].transcript;
        if (elInput()) elInput().value = text;
        sendText();
      }catch(_){ /* noop */ }
    };
  }
  function toggleMic(){ if(!recog){ initVoice(); if(!recog){ alert('Reconocimiento de voz no soportado en este navegador.'); return; } }
    if(listening){ try{ recog.stop(); }catch(_){ } }
    else { try{ recog.start(); }catch(_){ } }
  }

  function speak(text){ try{
    if (!window.speechSynthesis) return;
    const u = new SpeechSynthesisUtterance(text);
    u.lang = 'es-PE';
    u.rate = 1.02; // un poco más ágil
    u.pitch = 1.25; // timbre juvenil
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
    // Mensaje de bienvenida
    appendMsg('bot', '¡Hola! Soy Tommibot, tu asistente. Puedo ayudarte con reservas, préstamos, horarios, políticas y más. ¿En qué te ayudo hoy?');
  });
})();
