(function(){
  // Resolver automáticamente la ruta del endpoint según la vista actual
  const candidates = [
    '../../app/api/notificaciones.php', // vistas en app/view/*
    '../app/api/notificaciones.php',    // por si alguna vista estuviera un nivel distinto
    '/Sistema_reserva_AIP/app/api/notificaciones.php', // posible nombre alterno del proyecto
    '/Reservacion_AIP/app/api/notificaciones.php', // absoluta según carpeta del proyecto
    '/app/api/notificaciones.php'       // fallback absoluto
  ];
  let apiBase = sessionStorage.getItem('notif_api_base') || '';

  async function resolveApiBase(){
    // Verificar base en caché; si no responde, limpiar y recalcular
    if (apiBase) {
      try {
        const test = await fetch(apiBase + '?action=pulse', { method:'GET', credentials:'same-origin' });
        if (test.ok) return apiBase;
      } catch(_) { /* ignorar */ }
      sessionStorage.removeItem('notif_api_base');
      apiBase = '';
    }
    for (const url of candidates){
      try{
        const res = await fetch(url + '?action=pulse', { method:'GET', credentials:'same-origin' });
        if (res.ok){ apiBase = url; sessionStorage.setItem('notif_api_base', apiBase); return apiBase; }
      }catch(_){ /* siguiente candidato */ }
    }
    // Último recurso: usar el primero aunque falle para no romper la UI
    apiBase = candidates[0];
    return apiBase;
  }

  function qs(sel, root=document){ return root.querySelector(sel); }
  function qsa(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }

  async function safeJson(res){
    try{ return await res.json(); }catch(_){ return { ok:false, error:'parse' }; }
  }

  async function listar() {
    const base = await resolveApiBase();
    const res = await fetch(`${base}?action=listar&soloNoLeidas=1&limit=10`, {credentials:'same-origin'}).catch(()=>null);
    if (!res || !res.ok) return { ok:true, items:[], noLeidas:0 };
    const data = await safeJson(res);
    if (!data.ok) return { ok:true, items:[], noLeidas:0 };
    return data;
  }
  async function marcarTodas(){
    const base = await resolveApiBase();
    const res = await fetch(base, {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      credentials:'same-origin',
      body: new URLSearchParams({action:'marcar_todas'})
    }).catch(()=>null);
    if (!res || !res.ok) return { ok:true };
    const data = await safeJson(res);
    if (!data.ok) return { ok:true };
    return data;
  }

  function renderList(items){
    const list = qs('#notif-list');
    if (!list) return;
    list.innerHTML = '';
    if (!items || !items.length) {
      list.innerHTML = '<div class="p-3 text-muted small">Sin notificaciones nuevas.</div>';
      return;
    }
    items.forEach(n => {
      const a = document.createElement('a');
      a.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start';
      a.href = n.url || '#';
      a.innerHTML = `
        <div>
          <div class="fw-semibold small">${escapeHtml(n.titulo||'')}</div>
          <div class="small text-muted">${escapeHtml(truncate(n.mensaje||'', 80))}</div>
        </div>
        ${Number(n.leida) ? '' : '<span class="badge bg-primary rounded-pill">nuevo</span>'}
      `;
      list.appendChild(a);
    });
  }

  function updateCount(count){
    // Intentar actualizar un badge cercano al botón de campana
    const btn = qs('#notifDropdown');
    if (!btn) return;
    let badge = btn.querySelector('.badge');
    if (!badge && count>0){
      badge = document.createElement('span');
      badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark';
      btn.appendChild(badge);
    }
    if (badge){
      badge.textContent = count;
      badge.style.display = count>0 ? 'inline' : 'none';
    }
  }

  function truncate(s, len){ return s.length>len ? s.slice(0,len-1)+'…' : s; }
  function escapeHtml(s){
    return String(s).replace(/[&<>"']/g, (c) => (
      { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]
    ));
  }

  document.addEventListener('DOMContentLoaded', () => {
    const dd = qs('#notifDropdown');
    const markAll = qs('#notif-markall');
    let loading = false;

    async function loadAndRender(){
      if (loading) return; // evitar doble carga
      loading = true;
      try {
        const {items, noLeidas} = await listar();
        renderList(items);
        updateCount(noLeidas);
      } catch (e) {
        // sin bloqueo
      } finally {
        loading = false;
      }
    }

    if (dd) {
      // Caso Bootstrap: evento nativo
      dd.addEventListener('show.bs.dropdown', loadAndRender);
      // Fallback: si no existe Bootstrap, al hacer click cargamos y alternamos menú desde navbar fallback
      dd.addEventListener('click', loadAndRender);
    }
    if (markAll) {
      markAll.addEventListener('click', async (e) => {
        e.preventDefault();
        try {
          await marcarTodas();
          updateCount(0);
          renderList([]);
        } catch (e) {}
      });
    }
  });
})();
