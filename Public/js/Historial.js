(function () {
    // datos del PHP
    const dataInit = (typeof initialData !== 'undefined') ? initialData : { fecha_inicio: null, fecha_fin: null, aulas: [] };
    const AJAX_URL = (typeof ajaxUrl !== 'undefined') ? ajaxUrl : '../controllers/HistorialController.php';

    const intervalMinutes = 45; // como pediste (45 min)
    const turnoRanges = {
        'manana': { start: '06:00', end: '12:45' }, // incluye 12:45 final
        'tarde' : { start: '13:00', end: '19:00' }  // termina a las 19:00
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
        for(let i=0;i<6;i++){
            const d = days[i];
            const label = d.toLocaleDateString(undefined, { weekday: 'short', day:'2-digit', month:'2-digit' });
            html += `<th>${label}</th>`;
        }
        html += '</tr></thead><tbody>';

        // For each slot (45-min), for each day, mark occupation and text if start/end falls in slot
        for(let s=0; s<slots.length; s++){
            const slot = slots[s];
            const slotStartSec = timeToSeconds(slot.start + ':00');
            const slotEndSec   = timeToSeconds(slot.end + ':00');

            html += `<tr><td class="time-col">${slot.start}</td>`;

            for(let d=0; d<6; d++){
                const dayStr = days[d].toISOString().slice(0,10);
                const diaReservas = reservasPorDia[dayStr] || [];

                let occupied = false;
                let texts = []; // may collect multiple reservation labels if multiple overlap

                // Iterate each reservation for that day and check overlap with slot
                for(let ri=0; ri<diaReservas.length; ri++){
                    const r = diaReservas[ri];
                    if (r.inicioSec == null || r.finSec == null) continue;
                    // overlap if slotStart < r.fin && slotEnd > r.inicio
                    if (slotStartSec < r.finSec && slotEndSec > r.inicioSec) {
                        occupied = true;
                        // determine whether slot contains start, end or both
                        const isStartCell = (r.inicioSec >= slotStartSec && r.inicioSec < slotEndSec);
                        const isEndCell   = (r.finSec > slotStartSec && r.finSec <= slotEndSec);
                        if (isStartCell && isEndCell) {
                            texts.push(`${formatHM(r.inicioStr)} - ${formatHM(r.finStr)}`);
                        } else if (isStartCell) {
                            texts.push(formatHM(r.inicioStr));
                        } else if (isEndCell) {
                            texts.push(formatHM(r.finStr));
                        } else {
                            // middle cell: no text
                        }
                    }
                }

                if (occupied) {
                    if (texts.length > 0) {
                        // join multiple texts with " | " in case of multiple overlapping reservations (rare)
                        const content = texts.join(' | ');
                        html += `<td class="occupied"><span>${content}</span></td>`;
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
