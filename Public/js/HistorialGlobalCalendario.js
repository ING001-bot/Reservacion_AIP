// Public/js/HistorialGlobalCalendario.js
// Renderiza el calendario global (todas las reservas/cancelaciones con nombre del profesor)
(function(){
  const startInput = document.getElementById('start-of-week');
  const btnPrev = document.getElementById('prev-week');
  const btnNext = document.getElementById('next-week');
  const btnManana = document.getElementById('btn-manana');
  const btnTarde = document.getElementById('btn-tarde');
  const calendarios = document.getElementById('calendarios');
  const pdfStart = document.getElementById('pdf-start-week');
  const pdfTurno = document.getElementById('pdf-turno');
  const pdfProf = document.getElementById('pdf-prof');
  const profFilter = document.getElementById('calendar-prof-filter');
  const weekRangeDisplay = document.getElementById('week-range-display');

  if (!startInput || !btnPrev || !btnNext || !btnManana || !btnTarde || !calendarios) return;

  let turno = 'manana';
  
  // Calcular el lunes de la semana actual
  function getMondayOfWeek(dateStr) {
    const d = new Date(dateStr + 'T00:00:00');
    const day = d.getDay(); // 0=domingo, 1=lunes, ..., 6=sábado
    const diff = day === 0 ? -6 : 1 - day; // Si es domingo, retroceder 6 días
    d.setDate(d.getDate() + diff);
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
  }
  
  let startOfWeek = getMondayOfWeek(startInput.value || new Date().toISOString().substr(0,10));
  startInput.value = startOfWeek;
  
  function updateWeekRangeDisplay(monday){
    if(!weekRangeDisplay) return;
    const start = new Date(monday+'T00:00:00');
    const end = new Date(monday+'T00:00:00');
    end.setDate(end.getDate() + 5); // +5 días = sábado
    const opts = {day:'2-digit', month:'short'};
    const startStr = start.toLocaleDateString('es-ES', opts);
    const endStr = end.toLocaleDateString('es-ES', opts);
    weekRangeDisplay.textContent = `${startStr} - ${endStr}`;
  }

  function setActiveTurn(button){
    btnManana.classList.remove('active');
    btnTarde.classList.remove('active');
    button.classList.add('active');
  }

  btnManana.addEventListener('click', ()=>{ turno='manana'; setActiveTurn(btnManana); pdfTurno.value='manana'; loadCalendar(); });
  btnTarde.addEventListener('click', ()=>{ turno='tarde'; setActiveTurn(btnTarde); pdfTurno.value='tarde'; loadCalendar(); });

  btnPrev.addEventListener('click', ()=>{ startOfWeek = shiftWeek(startOfWeek, -7); startInput.value=startOfWeek; pdfStart.value=startOfWeek; updateWeekRangeDisplay(startOfWeek); loadCalendar(); });
  btnNext.addEventListener('click', ()=>{ startOfWeek = shiftWeek(startOfWeek, 7); startInput.value=startOfWeek; pdfStart.value=startOfWeek; updateWeekRangeDisplay(startOfWeek); loadCalendar(); });

  function shiftWeek(dateStr, delta){
    const d = new Date(dateStr+'T00:00:00');
    d.setDate(d.getDate()+delta);
    const yyyy=d.getFullYear();
    const mm=String(d.getMonth()+1).padStart(2,'0');
    const dd=String(d.getDate()).padStart(2,'0');
    return `${yyyy}-${mm}-${dd}`;
  }

  async function loadCalendar(){
    try{
      const prof = (profFilter?.value || '').trim();
      pdfProf.value = prof;
      const url = `../../app/api/HistorialGlobalCalendario_fetch.php?start=${startOfWeek}&turno=${turno}${prof?`&profesor=${encodeURIComponent(prof)}`:''}`;
      const resp = await fetch(url);
      if(!resp.ok){ const t=await resp.text(); throw new Error(`HTTP ${resp.status} – ${t.slice(0,200)}`); }
      const ct = resp.headers.get('content-type')||'';
      if(!ct.includes('application/json')){ const t=await resp.text(); throw new Error(`No JSON – ${t.slice(0,200)}`); }
      const data = await resp.json();
      // Alinear rango visual con el lunes calculado por el servidor
      if (data && data.monday){
        startOfWeek = data.monday;
        startInput.value = startOfWeek;
        if (pdfStart) pdfStart.value = startOfWeek;
        updateWeekRangeDisplay(startOfWeek);
      }
      renderCalendarios(data, turno);
    }catch(e){
      console.error('Error al cargar calendario global', e);
      calendarios.innerHTML = '<div class="text-danger">No se pudo cargar el calendario. Error: ' + e.message + '</div>';
    }
  }

  function renderCalendarios(data, turno){
    calendarios.innerHTML='';
    const containerLeft = document.createElement('div'); containerLeft.className='calendario-box';
    const containerRight= document.createElement('div'); containerRight.className='calendario-box';
    const titleLeft = document.createElement('h3');
    const cancelCount1 = Object.values(data.cancel1||{}).reduce((a,arr)=>a+(arr?.length||0),0);
    titleLeft.textContent = (data.aip1_nombre||'AIP 1') + (cancelCount1?` · ${cancelCount1} cancelada(s)`:'');
    const titleRight = document.createElement('h3');
    const cancelCount2 = Object.values(data.cancel2||{}).reduce((a,arr)=>a+(arr?.length||0),0);
    titleRight.textContent = (data.aip2_nombre||'AIP 2') + (cancelCount2?` · ${cancelCount2} cancelada(s)`:'');
    containerLeft.appendChild(titleLeft); containerRight.appendChild(titleRight);

    const tableLeft = buildTable(data.aip1||{}, data.cancel1||{}, turno);
    const tableRight= buildTable(data.aip2||{}, data.cancel2||{}, turno);
    containerLeft.appendChild(tableLeft); containerRight.appendChild(tableRight);
    calendarios.appendChild(containerLeft); calendarios.appendChild(containerRight);
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

  function isBetween(t, a, b){
    const tt = t.length===5 ? t+':00' : t;
    if (a.length===5) a += ':00';
    if (b.length===5) b += ':00';
    return (tt>=a && tt<=b);
  }

  function buildTable(aipData, cancelData, turno){
    const table=document.createElement('table'); table.className='calendario-table';
    const thead=document.createElement('thead'); const trHead=document.createElement('tr');
    const thEmpty=document.createElement('th'); trHead.appendChild(thEmpty);
    const dias=Object.keys(aipData);
    dias.forEach(fecha=>{ const th=document.createElement('th'); const d=new Date(fecha+'T00:00:00'); th.innerHTML = `${d.toLocaleDateString('es-PE',{weekday:'long'})}<br><small>${fecha}</small>`; trHead.appendChild(th); });
    thead.appendChild(trHead); table.appendChild(thead);

    const tbody=document.createElement('tbody'); const times=getTimes(turno);
    times.forEach(time=>{
      const tr=document.createElement('tr');
      const timeTd=document.createElement('td'); timeTd.className='hora-cell'; timeTd.textContent=time; tr.appendChild(timeTd);
      dias.forEach(fecha=>{
        const td=document.createElement('td'); td.className='cell'; td.dataset.fecha=fecha; td.dataset.hora=time;
        const reservas=aipData[fecha]||[]; const canceladas=(cancelData&&cancelData[fecha])?cancelData[fecha]:[];
        // Reservas: mostrar tiempo y profesor en la primera celda del rango
        reservas.forEach(r=>{
          if(isBetween(time, r.hora_inicio, r.hora_fin)){
            td.classList.add('reserved');
            if(!r._shown){
              const horas = `${r.hora_inicio.substr(0,5)} - ${r.hora_fin.substr(0,5)}`;
              const prof = r.profesor?`<br><small>${r.profesor}</small>`:'';
              td.innerHTML = `<div class="res-label">${horas}${prof}</div>`;
              r._shown=true;
            }
          }
        });
        // Cancelaciones: marcar y mostrar etiqueta CANCELADO
        canceladas.forEach(c=>{
          if(isBetween(time, c.hora_inicio||'', c.hora_fin||'')){
            td.classList.add('canceled');
            if(!c._shown){
              const motivo = (c.motivo||'').replace(/</g,'&lt;').replace(/>/g,'&gt;');
              const horas = `${(c.hora_inicio||'').substr(0,5)} - ${(c.hora_fin||'').substr(0,5)}`;
              const prof = c.profesor?`<br><small>${c.profesor}</small>`:'';
              const top = `<div class="res-label">${horas}${prof}</div>`;
              const bottom = `<div class="cancel-label" title="${motivo}">CANCELADO</div>`;
              td.innerHTML = top + `<div class="mt-1"></div>` + bottom;
              c._shown=true;
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

  // init
  updateWeekRangeDisplay(startOfWeek);
  loadCalendar();
})();
