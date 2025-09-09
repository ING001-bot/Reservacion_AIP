// Public/js/historial.js
(() => {
  if (typeof initialData === 'undefined') {
    console.error('initialData no definido');
    return;
  }

  const AJAX_URL = typeof AJAX_URL !== 'undefined' ? AJAX_URL : '../controllers/HistorialController.php';
  const intervalMinutes = 45;
  const turnoRanges = {
    manana: { start: '06:00', end: '12:45' },
    tarde : { start: '13:00', end: '19:00' }
  };

  const elWeekRange = document.getElementById('weekRangeDisplay');
  const btnPrev = document.getElementById('btnPrev');
  const btnNext = document.getElementById('btnNext');
  const btnManana = document.getElementById('btnManana');
  const btnTarde = document.getElementById('btnTarde');
  const btnPdf = document.getElementById('btnPdf');
  const calAip1 = document.getElementById('cal-aip1');
  const calAip2 = document.getElementById('cal-aip2');

  let weekOffset = 0;
  let turno = 'manana';
  let data = initialData;

  function pad(n){ return n < 10 ? '0' + n : '' + n; }
  function parseTimeToMinutes(t){
    if(!t) return null;
    const parts = t.split(':').map(Number);
    return parts[0]*60 + (parts[1]||0);
  }
  function dateFromYMD(ymd){
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(ymd);
    if(!m) return new Date();
    return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
  }
  function ymdFromDate(dt){
    return dt.getFullYear() + '-' + pad(dt.getMonth()+1) + '-' + pad(dt.getDate());
  }

  function generateSlots(hInicio, hFin, intervalMin){
    const [h0, m0] = hInicio.split(':').map(Number);
    const [hf, mf] = hFin.split(':').map(Number);

    const slots = [];
    let curMin = h0*60 + m0;
    const endMin = hf*60 + mf;

    while (curMin < endMin) {
      let nextMin = curMin + intervalMin;
      if (nextMin > endMin) nextMin = endMin;
      const s = pad(Math.floor(curMin/60)) + ':' + pad(curMin%60);
      const e = pad(Math.floor(nextMin/60)) + ':' + pad(nextMin%60);
      slots.push({ start: s, end: e, startMin: curMin, endMin: nextMin });
      curMin = nextMin;
    }
    return slots;
  }

  function renderCalendarInto(aulaObj, container, turnoActual, fechaInicioSemana){
    if(!container) return;
    if(!aulaObj){
      container.innerHTML = '<div class="calendar-title">—</div><div class="text-muted p-3">Aula no encontrada.</div>';
      return;
    }

    const startDate = dateFromYMD(fechaInicioSemana);
    const days = [];
    for(let d=0; d<6; d++){
      const dt = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate() + d);
      days.push(dt);
    }

    const range = turnoRanges[turnoActual];
    const slots = generateSlots(range.start, range.end, intervalMinutes);
    const reservasPorDia = aulaObj.reservas || {};

    let html = '<div class="table-responsive-calendar"><table class="calendar">';
    html += '<thead><tr><th class="time-col"></th>';
    for (let i=0;i<6;i++){
      const d = days[i];
      const label = d.toLocaleDateString(undefined,{weekday:'short', day:'2-digit', month:'2-digit'});
      html += `<th>${label}</th>`;
    }
    html += '</tr></thead><tbody>';

    const labelPlacedByDay = Array(6).fill(null).map(()=> new Set());

    for (let s=0; s<slots.length; s++){
      const slot = slots[s];
      html += `<tr><td class="time-col">${slot.start}</td>`;

      for (let d=0; d<6; d++){
        const dayYMD = ymdFromDate(days[d]);
        const diaReservas = reservasPorDia[dayYMD] || [];
        let occupied = false;
        let cellText = '';

        for (let ri=0; ri<diaReservas.length; ri++){
          const r = diaReservas[ri];
          if (!r.hora_inicio || !r.hora_fin) continue;
          const rStart = parseTimeToMinutes(r.hora_inicio.slice(0,5));
          const rEnd = parseTimeToMinutes(r.hora_fin.slice(0,5));
          if (isNaN(rStart) || isNaN(rEnd) || rEnd <= rStart) continue;

          // redondeo: primer slot.startMin >= rStart
          let roundedStart = slots.length ? slots[0].startMin : rStart;
          for (let sj=0; sj<slots.length; sj++){
            if (slots[sj].startMin >= rStart) { roundedStart = slots[sj].startMin; break; }
          }

          if (slot.startMin >= roundedStart && slot.startMin < rEnd) {
            occupied = true;
            if (!labelPlacedByDay[d].has(ri)) {
              cellText = `${r.hora_inicio.slice(0,5)} - ${r.hora_fin.slice(0,5)}`;
              labelPlacedByDay[d].add(ri);
            }
          }
        }

        if (occupied) {
          html += cellText ? `<td class="occupied"><span>${cellText}</span></td>` : `<td class="occupied"></td>`;
        } else {
          html += `<td class="free"></td>`;
        }
      }

      html += '</tr>';
    }

    html += '</tbody></table></div>';

    container.innerHTML = '<div class="calendar-title">' + aulaObj.nombre_aula + ' (Turno: ' + (turnoActual==='manana' ? 'Mañana' : 'Tarde') + ')</div>' + html;
  }

  function renderAll(){
    if (!data || !data.aulas) return;
    elWeekRange && (elWeekRange.textContent = (data.rango_semana?.inicio || '') + ' → ' + (data.rango_semana?.fin || ''));
    const aula1 = data.aulas[0] || null;
    const aula2 = data.aulas[1] || null;
    renderCalendarInto(aula1, calAip1, turno, data.fecha_inicio);
    renderCalendarInto(aula2, calAip2, turno, data.fecha_inicio);
    btnManana.classList.toggle('btn-success', turno === 'manana');
    btnManana.classList.toggle('btn-outline-brand', turno !== 'manana');
    btnTarde.classList.toggle('btn-success', turno === 'tarde');
    btnTarde.classList.toggle('btn-outline-brand', turno !== 'tarde');
    if (btnPdf) {
      const url = AJAX_URL + '?action=exportPdf&semana=' + weekOffset + '&turno=' + encodeURIComponent(turno);
      btnPdf.setAttribute('href', url);
    }
  }

  function loadWeek(offset) {
    fetch(AJAX_URL + '?action=reservasSemana&semana=' + encodeURIComponent(offset))
      .then(r => { if (!r.ok) throw new Error('Error al cargar semana'); return r.json(); })
      .then(json => {
        data = json;
        weekOffset = offset;
        renderAll();
      })
      .catch(err => {
        console.error('Error loadWeek:', err);
        alert('Error al cargar la semana. Revisa la consola.');
      });
  }

  btnPrev && btnPrev.addEventListener('click', () => loadWeek(weekOffset - 1));
  btnNext && btnNext.addEventListener('click', () => loadWeek(weekOffset + 1));
  btnManana && btnManana.addEventListener('click', () => { turno = 'manana'; renderAll(); });
  btnTarde && btnTarde.addEventListener('click', () => { turno = 'tarde'; renderAll(); });

  // Inicial
  renderAll();
})();
