(function(){
  // From pages under app/view/*.php, API is at ../api/notificaciones.php
  const apiBase = '../api/notificaciones.php';
  function qs(sel, root=document){ return root.querySelector(sel); }
  function qsa(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }

  async function listar() {
    const res = await fetch(`${apiBase}?action=listar&soloNoLeidas=1&limit=10`, {credentials:'same-origin'});
    const data = await res.json();
    if (!data.ok) throw new Error(data.error||'Error');
    return data;
  }
  async function marcarTodas(){
    const res = await fetch(apiBase, {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      credentials:'same-origin',
      body: new URLSearchParams({action:'marcar_todas'})
    });
    const data = await res.json();
    if (!data.ok) throw new Error(data.error||'Error');
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
    const b = qs('#notif-count');
    if (!b) return;
    b.textContent = count;
    b.style.display = count>0 ? 'inline' : 'none';
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
    if (dd) {
      dd.addEventListener('show.bs.dropdown', async () => {
        try {
          const {items, noLeidas} = await listar();
          renderList(items);
          updateCount(noLeidas);
        } catch (e) {
          // fallback UI
        }
      });
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
