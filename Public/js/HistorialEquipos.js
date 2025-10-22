// Public/js/HistorialEquipos.js
// Vista: Historial / Equipos
(function(){
  const apiBase = '/Sistema_reserva_AIP/app/api/HistorialEquipos_fetch.php';
  const eqStart = document.getElementById('eq-start-of-week');
  const btnPrev = document.getElementById('eq-prev-week');
  const btnNext = document.getElementById('eq-next-week');
  const btnManana = document.getElementById('eq-btn-manana');
  const btnTarde = document.getElementById('eq-btn-tarde');
  const rangeDisplay = document.getElementById('eq-week-range-display');
  const grid = document.getElementById('calendarios-equipos');
  const tablaHost = document.getElementById('tabla-equipos');
  const searchInput = document.getElementById('eq-search');
  const pdfStart = document.getElementById('eq-pdf-start-week');
  const pdfTurno = document.getElementById('eq-pdf-turno');
  const pdfQ = document.getElementById('eq-pdf-q');

  if (!eqStart || !btnPrev || !btnNext || !btnManana || !btnTarde || !grid || !tablaHost) {
    window.HistorialEquipos = { init: function(){} };
    return;
  }

  let turno = 'manana';
  let startOfWeek = eqStart.value || new Date().toISOString().slice(0,10);
  let q = '';

  function setActive(btn){ btnManana.classList.remove('active'); btnTarde.classList.remove('active'); btn.classList.add('active'); }

  function fmtWeekRange(monday){
    const start = new Date(monday+'T00:00:00');
    const end = new Date(monday+'T00:00:00'); end.setDate(end.getDate()+5);
    const opts = { day:'2-digit', month:'short' };
    rangeDisplay.textContent = `${start.toLocaleDateString('es-PE',opts)} - ${end.toLocaleDateString('es-PE',opts)}`;
  }

  function shift(dateStr, delta){ const d=new Date(dateStr+'T00:00:00'); d.setDate(d.getDate()+delta); const y=d.getFullYear(), m=String(d.getMonth()+1).padStart(2,'0'), da=String(d.getDate()).padStart(2,'0'); return `${y}-${m}-${da}`; }

  async function load(){
    try {
      const url = `${apiBase}?start=${encodeURIComponent(startOfWeek)}&turno=${encodeURIComponent(turno)}${q?`&q=${encodeURIComponent(q)}`:''}`;
      const resp = await fetch(url);
      if (!resp.ok) throw new Error('HTTP '+resp.status);
      const data = await resp.json();
      // sync PDF fields
      if (pdfStart) pdfStart.value = startOfWeek;
      if (pdfTurno) pdfTurno.value = turno;
      if (pdfQ) pdfQ.value = q;
      lastData = data || { manana:{}, tarde:{} };
      renderCalendarios(lastData);
      renderTabla(data.tabla||[]);
      if (data.monday) { startOfWeek = data.monday; eqStart.value = startOfWeek; fmtWeekRange(startOfWeek); }
    } catch (e) {
      console.error('HistorialEquipos load error', e);
      grid.innerHTML = '<div class="text-danger">No se pudo cargar la vista de equipos.</div>';
    }
  }

  function getTimes(turno){
    const times=[]; let s = turno==='manana'?'06:00':'13:00'; let e = turno==='manana'?'12:45':'19:00';
    let [sh,sm]=s.split(':').map(Number); const [eh,em]=e.split(':').map(Number);
    while (sh<eh || (sh===eh && sm<=em)){
      times.push(String(sh).padStart(2,'0')+':'+String(sm).padStart(2,'0'));
      sm+=45; if (sm>=60){ sm=0; sh++; }
    }
    return times;
  }

  function isBetween(t,a,b){ const tt=t.length===5? t+':00':t; const aa=a&&a.length===5? a+':00':(a||''); const bb=b&&b.length===5? b+':00':(b||''); return aa && bb && tt>=aa && tt<=bb; }

  function renderCalendarios(data){
    grid.innerHTML='';
    const box = document.createElement('div'); box.className='calendario-box';
    const title = document.createElement('h3'); title.textContent = turno==='manana' ? 'Turno Mañana' : 'Turno Tarde';
    box.appendChild(title);
    const dataset = turno==='manana' ? (data.manana||{}) : (data.tarde||{});
    box.appendChild(buildTable(dataset, turno));
    grid.appendChild(box);
  }

  function buildTable(aipData, turno){
    const table=document.createElement('table'); table.className='calendario-table';
    const thead=document.createElement('thead'); const trHead=document.createElement('tr');
    trHead.appendChild(document.createElement('th'));
    const dias=Object.keys(aipData);
    dias.forEach(fecha=>{ const th=document.createElement('th'); const d=new Date(fecha+'T00:00:00'); th.innerHTML=`${d.toLocaleDateString('es-PE',{weekday:'long'})}<br><small>${fecha}</small>`; trHead.appendChild(th); });
    thead.appendChild(trHead); table.appendChild(thead);

    const tbody=document.createElement('tbody'); const times=getTimes(turno);
    times.forEach(time=>{
      const tr=document.createElement('tr');
      const tdTime=document.createElement('td'); tdTime.className='hora-cell'; tdTime.textContent=time; tr.appendChild(tdTime);
      dias.forEach(fecha=>{
        const td=document.createElement('td'); td.className='cell';
        const items=aipData[fecha]||[];
        items.forEach(it=>{
          if (isBetween(time, it.hora_inicio||'', it.hora_fin||'')){
            td.classList.add('reserved');
            if (!it._shown){
              const horas = `${(it.hora_inicio||'').substring(0,5)} - ${(it.hora_fin||'').substring(0,5)}`;
              const prof  = it.profesor? `<br><small>${it.profesor}</small>` : '';
              td.innerHTML = `<div class="res-label">${horas}${prof}</div>`;
              it._shown=true;
            }
          }
        });
        tr.appendChild(td);
      });
      tbody.appendChild(tr);
    });
    table.appendChild(tbody);
    return table;
  }

  function renderTabla(rows){
    // Construir tabla igual al estilo de profesores
    const html = [`<table class="table table-hover align-middle text-center">`,
      `<thead class="table-primary"><tr>`,
      `<th>Profesor</th><th>Equipo solicitado</th><th>Aula</th><th>Hora inicio</th><th>Hora fin</th><th>Fecha</th>`,
      `</tr></thead><tbody>`];
    if (!rows.length){
      html.push(`<tr><td colspan="6" class="text-muted">No hay registros.</td></tr>`);
    } else {
      rows.forEach(r=>{
        html.push(`<tr>`+
          `<td>${escapeHtml(r.profesor||'')}</td>`+
          `<td>${escapeHtml(r.equipo||'')}</td>`+
          `<td>${escapeHtml(r.aula||'')}</td>`+
          `<td>${escapeHtml((r.hora_inicio||'').substring(0,5))}</td>`+
          `<td>${escapeHtml((r.hora_fin||'').substring(0,5))}</td>`+
          `<td>${escapeHtml(r.fecha||'')}</td>`+
        `</tr>`);
      });
    }
    html.push(`</tbody></table>`);
    tablaHost.innerHTML = html.join('');
  }

  function escapeHtml(s){ return String(s).replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;' }[c])); }

  // Bindings
  if (btnManana) btnManana.addEventListener('click', ()=>{ turno='manana'; setActive(btnManana); if (pdfTurno) pdfTurno.value='manana'; renderCalendarios(lastData); });
  if (btnTarde) btnTarde.addEventListener('click', ()=>{ turno='tarde'; setActive(btnTarde); if (pdfTurno) pdfTurno.value='tarde'; renderCalendarios(lastData); });
  let lastData = { manana:{}, tarde:{} };
  btnPrev.addEventListener('click', ()=>{ startOfWeek = shift(startOfWeek, -7); eqStart.value=startOfWeek; fmtWeekRange(startOfWeek); load(); });
  btnNext.addEventListener('click', ()=>{ startOfWeek = shift(startOfWeek, 7); eqStart.value=startOfWeek; fmtWeekRange(startOfWeek); load(); });
  if (searchInput){ searchInput.addEventListener('input', ()=>{ q = searchInput.value.trim(); load(); }); }

  // Expose init
  window.HistorialEquipos = {
    init: function(){ fmtWeekRange(startOfWeek); setActive(btnManana); if (pdfTurno) pdfTurno.value='manana'; load(); }
  };
})();
