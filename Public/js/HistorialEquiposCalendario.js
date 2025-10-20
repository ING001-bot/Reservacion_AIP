// Public/js/HistorialEquiposCalendario.js
(function(){
  const root = document.getElementById('section-equipos');
  if (!root) return;
  const btnManana = root.querySelector('#eq-btn-manana');
  const btnTarde  = root.querySelector('#eq-btn-tarde');
  const prevWeek  = root.querySelector('#eq-prev-week');
  const nextWeek  = root.querySelector('#eq-next-week');
  const startInput = root.querySelector('#eq-start-of-week');
  const weekRangeDisplay = root.querySelector('#eq-week-range-display');
  const calendarios = root.querySelector('#calendarios-equipos');
  const searchInput = root.querySelector('#eq-search');
  const tableContainer = root.querySelector('#eq-table-container');
  // Inputs globales de PDF (están fuera de esta sección)
  const pdfStartTop = document.getElementById('pdf-start-week');
  const pdfTurnoTop = document.getElementById('pdf-turno');
  const pdfStartBot = document.getElementById('pdf-start-week-bottom');
  const pdfTurnoBot = document.getElementById('pdf-turno-bottom');
  const globalRoot = document.getElementById('historial-global');
  // Mostrar columna Docente en:
  // - HistorialGlobal no embebido (tiene #historial-global)
  // - Admin embebido en Admin.php?view=historial_global
  const inAdminHistGlobal = /Admin\.php$/i.test(location.pathname) && /view=historial_global/i.test(location.search);
  const showProfesorCol = !!globalRoot || inAdminHistGlobal;

  let turno = 'manana';
  let startOfWeek = startInput.value || new Date().toISOString().substr(0,10);

  function getMonday(dateStr){
    const d = new Date(dateStr+'T00:00:00');
    const day = d.getDay(); // 0=Sun,1=Mon,...6=Sat
    const diff = (day === 0 ? -6 : 1 - day); // move to Monday
    d.setDate(d.getDate()+diff);
    const yyyy=d.getFullYear();
    const mm=String(d.getMonth()+1).padStart(2,'0');
    const dd=String(d.getDate()).padStart(2,'0');
    return `${yyyy}-${mm}-${dd}`;
  }

  function updateWeekRangeDisplay(monday){
    if(!weekRangeDisplay) return;
    const start = new Date(monday+'T00:00:00');
    const end = new Date(monday+'T00:00:00');
    end.setDate(end.getDate()+5);
    const opts = {day:'2-digit', month:'short'};
    const startStr = start.toLocaleDateString('es-PE', opts);
    const endStr = end.toLocaleDateString('es-PE', opts);
    weekRangeDisplay.textContent = `${startStr} - ${endStr}`;
  }
  function setActiveTurn(btn){
    btnManana.classList.remove('active');
    btnTarde.classList.remove('active');
    btn.classList.add('active');
  }
  function shiftWeek(dateStr, delta){
    const d = new Date(dateStr+'T00:00:00');
    d.setDate(d.getDate()+delta);
    const yyyy=d.getFullYear();
    const mm=String(d.getMonth()+1).padStart(2,'0');
    const dd=String(d.getDate()).padStart(2,'0');
    return `${yyyy}-${mm}-${dd}`;
  }
  function getWeekDates(start){
    const base = new Date(start+'T00:00:00');
    const days=[];
    for(let i=0;i<6;i++){
      const d=new Date(base);
      d.setDate(base.getDate()+i);
      const yyyy=d.getFullYear();
      const mm=String(d.getMonth()+1).padStart(2,'0');
      const dd=String(d.getDate()).padStart(2,'0');
      days.push(`${yyyy}-${mm}-${dd}`);
    }
    return days;
  }
  function getTimes(turno){
    const times=[]; let start = turno==='manana'?'06:00':'13:00'; let end = turno==='manana'?'12:45':'19:00';
    let [sh,sm]=start.split(':').map(Number); const [eh,em]=end.split(':').map(Number);
    while (sh<eh || (sh===eh && sm<=em)){
      times.push(String(sh).padStart(2,'0')+':'+String(sm).padStart(2,'0'));
      sm+=45; if(sm>=60){ sm=0; sh++; }
    }
    return times;
  }
  function isBetween(t,a,b){ const tt=t.length===5?t+':00':t; if(a.length===5) a+=':00'; if(b.length===5) b+=':00'; return (tt>=a && tt<=b); }

  async function loadCalendar(){
    try{
      const q = encodeURIComponent((searchInput && searchInput.value) || '');
      const url = `../../app/api/HistorialEquiposCalendario_fetch.php?start=${startOfWeek}&turno=${turno}&q=${q}`;
      const resp = await fetch(url);
      if(!resp.ok){ const t=await resp.text(); throw new Error(`HTTP ${resp.status} – ${t.slice(0,200)}`); }
      const ct = resp.headers.get('content-type')||'';
      if(!ct.includes('application/json')){ const t=await resp.text(); throw new Error(`No JSON – ${t.slice(0,200)}`); }
      const data = await resp.json();
      // Asegurar consistencia con el lunes calculado por el servidor
      if (data && data.monday){
        startOfWeek = data.monday;
        if (startInput) startInput.value = startOfWeek;
        updateWeekRangeDisplay(startOfWeek);
      }
      renderCalendarios(data, turno);
      renderTabla(data);
    }catch(e){
      console.error('Error al cargar calendario de equipos', e);
      calendarios.innerHTML = '<div class="text-danger">No se pudo cargar el calendario de equipos. '+e.message+'</div>';
      if (tableContainer) tableContainer.innerHTML = '';
    }
  }

  function renderCalendarios(data, turno){
    calendarios.innerHTML='';
    const box = document.createElement('div'); box.className='calendario-box';
    const title = document.createElement('h3'); title.textContent = 'Calendario de préstamos';
    box.appendChild(title);
    const table = buildTable(data.agenda||{}, turno, data.monday);
    box.appendChild(table);
    calendarios.appendChild(box);
  }

  function buildTable(tipoData, turno, startWeek){
    const table=document.createElement('table'); table.className='calendario-table';
    const thead=document.createElement('thead'); const trHead=document.createElement('tr');
    const thEmpty=document.createElement('th'); thEmpty.textContent='Hora'; trHead.appendChild(thEmpty);
    const dias=getWeekDates(startWeek);
    dias.forEach(fecha=>{ const th=document.createElement('th'); const d=new Date(fecha+'T00:00:00'); const label=d.toLocaleDateString('es-PE',{weekday:'long'}); const nice=label.charAt(0).toUpperCase()+label.slice(1); th.innerHTML=`${nice}<br><small>${fecha}</small>`; trHead.appendChild(th); });
    thead.appendChild(trHead); table.appendChild(thead);

    const tbody=document.createElement('tbody'); const times=getTimes(turno);
    times.forEach(time=>{
      const tr=document.createElement('tr');
      const timeTd=document.createElement('td'); timeTd.className='hora-cell'; timeTd.textContent=time; tr.appendChild(timeTd);
      dias.forEach(fecha=>{
        const td=document.createElement('td'); td.className='cell'; td.dataset.fecha=fecha; td.dataset.hora=time;
        const reservas=tipoData[fecha]||[];
        reservas.forEach(r=>{
          if(isBetween(time, r.hora_inicio||'', r.hora_fin||'')){
            td.classList.add('reserved');
            if(!r._shown){
              const horas = `${(r.hora_inicio||'').substr(0,5)} - ${(r.hora_fin||'').substr(0,5)}`;
              const prof = r.profesor?`<br><small>${r.profesor}</small>`:'';
              td.innerHTML = `<div class="res-label">${horas}${prof}</div>`;
              r._shown=true;
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

  function renderTabla(data){
    if (!tableContainer) return;
    const rows = Array.isArray(data.prestamos) ? data.prestamos : [];
    if (rows.length === 0){
      tableContainer.innerHTML = '<div class="text-muted">No hay préstamos en esta semana.</div>';
      return;
    }
    let html = '<div class="table-responsive"><table class="table table-sm table-striped align-middle">';
    html += '<thead><tr>'
      + '<th>Equipo(s)</th>'
      + (showProfesorCol ? '<th>Docente</th>' : '')
      + '<th>Aula</th><th>Fecha</th><th>Hora Inicio</th><th>Hora Fin</th><th>Estado</th>'
      + '</tr></thead><tbody>';
    rows.forEach((r)=>{
      const equiposCell = buildEquiposBadges(r);
      const estadoBadge = buildEstadoBadge(r.estado||'');
      html += '<tr>'
        + `<td>${equiposCell}</td>`
        + (showProfesorCol ? `<td>${escapeHtml(r.profesor||'')}</td>` : '')
        + `<td>${r.aula||''}</td>`
        + `<td>${r.fecha||''}</td>`
        + `<td>${(r.hora_inicio||'').slice(0,5)}</td>`
        + `<td>${(r.hora_fin||'').slice(0,5)}</td>`
        + `<td>${estadoBadge}</td>`
        + '</tr>';
    });
    html += '</tbody></table></div>';
    tableContainer.innerHTML = html;
  }

  function buildEquiposBadges(r){
    // Para packs, endpoint manda 'equipo' como 'Items: A, B, C'; para individuales, el nombre del equipo
    let list = [];
    const eq = (r.equipo||'').trim();
    if (eq.toLowerCase().startsWith('items:')){
      const raw = eq.split(':')[1]||'';
      list = raw.split(',').map(s=>s.trim()).filter(Boolean);
    } else if (eq){
      list = [eq];
    }
    if (list.length===0) return '';
    return list.map(txt=>`<span class="badge bg-info-subtle text-info-emphasis me-1 mb-1">${escapeHtml(txt)}</span>`).join('');
  }

  function buildEstadoBadge(estado){
    const e = (estado||'').toLowerCase();
    let cls = 'bg-secondary';
    if (e==='prestado') cls = 'bg-warning text-dark';
    if (e==='devuelto') cls = 'bg-success';
    return `<span class="badge ${cls}">${escapeHtml(estado)}</span>`;
  }

  function escapeHtml(s){
    return String(s).replace(/[&<>"]+/g, c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"}[c]));
  }

  // events
  function syncPdfFields(){
    if (pdfStartTop) pdfStartTop.value = startOfWeek;
    if (pdfStartBot) pdfStartBot.value = startOfWeek;
    if (pdfTurnoTop) pdfTurnoTop.value = turno;
    if (pdfTurnoBot) pdfTurnoBot.value = turno;
  }

  btnManana.addEventListener('click', ()=>{ turno='manana'; setActiveTurn(btnManana); syncPdfFields(); loadCalendar(); });
  btnTarde.addEventListener('click', ()=>{ turno='tarde'; setActiveTurn(btnTarde); syncPdfFields(); loadCalendar(); });
  prevWeek.addEventListener('click', ()=>{ startOfWeek=shiftWeek(startOfWeek,-7); startInput.value=startOfWeek; updateWeekRangeDisplay(startOfWeek); syncPdfFields(); loadCalendar(); });
  nextWeek.addEventListener('click', ()=>{ startOfWeek=shiftWeek(startOfWeek, 7); startInput.value=startOfWeek; updateWeekRangeDisplay(startOfWeek); syncPdfFields(); loadCalendar(); });
  if (searchInput){
    let t;
    searchInput.addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(()=>loadCalendar(), 300); });
  }

  // init
  if (calendarios && calendarios.classList) { calendarios.classList.add('single'); }
  // Normalizar a lunes antes de primera carga
  startOfWeek = getMonday(startOfWeek);
  if (startInput) startInput.value = startOfWeek;
  updateWeekRangeDisplay(startOfWeek);
  // Sincronizar PDF al inicio
  syncPdfFields();
  loadCalendar();
})();
