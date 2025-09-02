(function () {
    // datos del PHP
    const dataInit = (typeof initialData !== 'undefined') ? initialData : { fecha_inicio: null, fecha_fin: null, aulas: [] };
    const AJAX_URL = (typeof ajaxUrl !== 'undefined') ? ajaxUrl : '../controllers/HistorialController.php';

    const intervalMinutes = 45; // (45 min)
    const turnoRanges = {
        'manana': { start: '06:00', end: '13:15' }, // incluye 12:45 final
        'tarde' : { start: '13:00', end: '19:15' }  // termina a las 19:00
    };

    // DOM
    const btnPrev = document.getElementById('btnPrev');
    const btnNext = document.getElementById('btnNext');
    const btnManana = document.getElementById('btnManana');
    const btnTarde = document.getElementById('btnTarde');
    const calAip1 = document.getElementById('cal-aip1');
    const calAip2 = document.getElementById('cal-aip2');
    const weekRangeServer = document.getElementById('weekRangeServer');

    let weekOffset = 0;
    let turno = 'manana';

    // render inicial
    renderResponse(dataInit, turno);
    setTurnoButtons();

    // listeners
    btnPrev && btnPrev.addEventListener('click', e => { e.preventDefault(); weekOffset--; loadWeek(); });
    btnNext && btnNext.addEventListener('click', e => { e.preventDefault(); weekOffset++; loadWeek(); });
    btnManana && btnManana.addEventListener('click', e => { e.preventDefault(); turno='manana'; setTurnoButtons(); loadWeek(); });
    btnTarde  && btnTarde.addEventListener('click', e => { e.preventDefault(); turno='tarde';  setTurnoButtons(); loadWeek(); });

    function setTurnoButtons(){
        if(!btnManana || !btnTarde) return;
        btnManana.classList.toggle('btn-success', turno==='manana');
        btnManana.classList.toggle('btn-outline-secondary', turno!=='manana');
        btnTarde.classList.toggle('btn-success', turno==='tarde');
        btnTarde.classList.toggle('btn-outline-secondary', turno!=='tarde');
    }

    function loadWeek(){
        const params = new URLSearchParams({ action: 'reservasSemana', semana: weekOffset });
        fetch(AJAX_URL + '?' + params.toString())
            .then(r => { if(!r.ok) throw new Error('Error en la petición'); return r.json(); })
            .then(data => {
                if(data.error) throw new Error(data.error);
                renderResponse(data, turno);
            })
            .catch(err => {
                console.error('Carga semana error:', err);
                alert('Error al cargar la semana. Revisa la consola.');
            });
    }

    function renderResponse(data, turnoActual){
        if (weekRangeServer && data.fecha_inicio && data.fecha_fin) {
            weekRangeServer.textContent = 'Semana: ' + data.fecha_inicio + ' → ' + data.fecha_fin;
        }
        const aulas = data.aulas || [];
        renderCalendarInto(aulas[0] || null, calAip1, turnoActual, data.fecha_inicio);
        renderCalendarInto(aulas[1] || null, calAip2, turnoActual, data.fecha_inicio);
    }

    // util: HH:MM[:SS] -> segundos
    function timeToSeconds(t){
        if(!t) return null;
        const parts = t.split(':').map(Number);
        if(parts.length === 2) parts.push(0);
        return parts[0]*3600 + parts[1]*60 + parts[2];
    }
    function formatHM(t){ if(!t) return ''; return t.slice(0,5); }
    function pad(n){ return n<10 ? '0'+n : ''+n; }

    // Render calendar for one aula. IMPORTANT: days are Monday..Saturday (6 days)
    // Render calendar for one aula. IMPORTANT: days are Monday..Saturday (6 days)
    function renderCalendarInto(aulaObj, container, turnoActual, fechaInicioSemana){
        if(!container) return;
        if(!aulaObj){
            container.innerHTML = '<div class="calendar-title">—</div><div class="text-muted p-3">Aula no encontrada.</div>';
            return;
        }

        // days: Monday -> Saturday (6 days)
        const start = new Date(fechaInicioSemana + 'T00:00:00');
        const days = [];
        for(let d=0; d<6; d++){
            const dt = new Date(start);
            dt.setDate(start.getDate() + d);
            days.push(dt);
        }

        const range = turnoRanges[turnoActual];
        const slots = generateTimeSlots(range.start, range.end, intervalMinutes);

        // Group reservations per day for faster checks. Normalize to seconds.
        const reservasPorDia = {};
        (aulaObj.reservas || []).forEach(r => {
            const fecha = (r.fecha || '').slice(0,10);
            const inicioStr = r.hora_inicio || '';
            const finStr = r.hora_fin || '';
            const inicioSec = timeToSeconds(inicioStr.length===5? inicioStr + ':00' : inicioStr);
            const finSec    = timeToSeconds(finStr.length===5? finStr + ':00' : finStr);
            if(!reservasPorDia[fecha]) reservasPorDia[fecha] = [];
            reservasPorDia[fecha].push({ inicioStr, finStr, inicioSec, finSec, profesor: r.profesor || '' });
        });

        // Build table header (6 days)
        let html = '<div class="table-responsive-calendar"><table class="calendar">';
        html += '<thead><tr><th class="time-col"></th>';
        for (let i = 0; i < 6; i++) {
            const d = days[i];
            const label = d.toLocaleDateString(undefined, { weekday: 'short', day: '2-digit', month: '2-digit' });
            html += `<th>${label}</th>`;
        }
        html += '</tr></thead><tbody>';

        // Para mostrar el rango real solo una vez por reserva y por día,
        // registramos si ya "pusimos" la etiqueta de inicio en ese día.
        const labelPlacedByDay = Array(6).fill(null).map(() => new Set());

        for (let s = 0; s < slots.length; s++) {
            const slot = slots[s];
            const slotStartSec = timeToSeconds(slot.start + ':00');
            const slotEndSec   = timeToSeconds(slot.end  + ':00');

            html += `<tr><td class="time-col">${slot.start}</td>`;

            for (let d = 0; d < 6; d++) {
                const dayStr = days[d].toISOString().slice(0, 10);
                const diaReservas = reservasPorDia[dayStr] || [];
                const labelPlaced = labelPlacedByDay[d];

                let occupied = false;
                const texts = [];

                // Recorremos reservas del día y vemos solape con el slot
                for (let ri = 0; ri < diaReservas.length; ri++) {
                    const r = diaReservas[ri];
                    if (r.inicioSec == null || r.finSec == null) continue;

                    // ¿Este slot se solapa con la reserva?
                    if (slotStartSec < r.finSec && slotEndSec > r.inicioSec) {
                        occupied = true;

                        // 👇 LÓGICA CLAVE:
                        // Etiquetar en la PRIMERA celda cuyo INICIO DE SLOT sea >= hora de inicio real.
                        if (!labelPlaced.has(ri) && slotStartSec >= r.inicioSec) {
                            texts.push(`${formatHM(r.inicioStr)} - ${formatHM(r.finStr)}`);
                            labelPlaced.add(ri); // ya etiquetamos esta reserva en este día
                        }
                    }
                }

                if (occupied) {
                    if (texts.length > 0) {
                        html += `<td class="occupied"><span>${texts.join(' | ')}</span></td>`;
                    } else {
                        html += `<td class="occupied"></td>`;
                    }
                } else {
                    html += `<td class="free"></td>`;
                }
            }

            html += '</tr>';
        }

        html += '</tbody></table></div>';

        container.innerHTML = '<div class="calendar-title">' + aulaObj.nombre_aula + ' (Turno: ' + (turnoActual==='manana' ? 'Mañana' : 'Tarde') + ')</div>' + html;
    }

    // Generate slots between hInicio and hFin with intervalMinutes (last slot may be truncated to hFin)
    function generateTimeSlots(hInicio, hFin, intervalMinutes){
        const slots = [];
        const [h0, m0] = hInicio.split(':').map(Number);
        const [hf, mf] = hFin.split(':').map(Number);

        let cur = new Date();
        cur.setHours(h0, m0, 0, 0);
        let end = new Date();
        end.setHours(hf, mf, 0, 0);

        while (cur < end) {
            let next = new Date(cur.getTime() + intervalMinutes * 60000);
            if (next > end) next = new Date(end); // truncar la última franja
            const s = pad(cur.getHours()) + ':' + pad(cur.getMinutes());
            const e = pad(next.getHours()) + ':' + pad(next.getMinutes());
            slots.push({ start: s, end: e });
            cur = next;
        }
        
        return slots;
    }

})();
