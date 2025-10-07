// Public/js/Historial.js
document.addEventListener('DOMContentLoaded', function(){
    const btnManana = document.getElementById('btn-manana');
    const btnTarde  = document.getElementById('btn-tarde');
    const prevWeek  = document.getElementById('prev-week');
    const nextWeek  = document.getElementById('next-week');
    const startInput = document.getElementById('start-of-week');
    const calendarios = document.getElementById('calendarios');
    const pdfStart = document.getElementById('pdf-start-week');
    const pdfTurno = document.getElementById('pdf-turno');

    let turno = 'manana';
    let startOfWeek = startInput.value || new Date().toISOString().substr(0,10);

    function setActiveTurn(button){
        btnManana.classList.remove('active');
        btnTarde.classList.remove('active');
        button.classList.add('active');
    }

    btnManana.addEventListener('click', ()=>{ turno = 'manana'; setActiveTurn(btnManana); pdfTurno.value='manana'; loadAll(); });
    btnTarde.addEventListener('click', ()=>{ turno = 'tarde'; setActiveTurn(btnTarde); pdfTurno.value='tarde'; loadAll(); });

    prevWeek.addEventListener('click', ()=>{ startOfWeek = shiftWeek(startOfWeek, -7); startInput.value = startOfWeek; pdfStart.value = startOfWeek; loadAll(); });
    nextWeek.addEventListener('click', ()=>{ startOfWeek = shiftWeek(startOfWeek, 7); startInput.value = startOfWeek; pdfStart.value = startOfWeek; loadAll(); });

    function shiftWeek(dateStr, deltaDays){
        const d = new Date(dateStr + 'T00:00:00');
        d.setDate(d.getDate() + deltaDays);
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth()+1).padStart(2,'0');
        const dd = String(d.getDate()).padStart(2,'0');
        return `${yyyy}-${mm}-${dd}`;
    }

    async function loadAll(){
        try {
            const urlHist = `/Sistema_reserva_AIP/app/api/Historial_fetch.php?start=${startOfWeek}&turno=${turno}&_=${Date.now()}`;
            console.log('Fetch historial URL:', urlHist);
            const resp = await fetch(urlHist);
            if (!resp.ok) {
                const text = await resp.text();
                throw new Error(`HTTP ${resp.status} al cargar historial. Respuesta: ${text.slice(0,300)}`);
            }
            const ct = resp.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                const text = await resp.text();
                throw new Error(`Respuesta no JSON desde Historial_fetch.php. Content-Type: ${ct}. Cuerpo: ${text.slice(0,300)}`);
            }
            const data = await resp.json();
            console.log('Historial_fetch data:', data);
            renderCalendarios(data, turno);
            loadPrestamos();
        } catch (e) {
            console.error('Error al cargar historial', e);
        }
    }

    async function loadPrestamos(){
        try {
            const urlPrest = `/Sistema_reserva_AIP/app/api/Prestamo_fetch.php?_=${Date.now()}`;
            console.log('Fetch prestamos URL:', urlPrest);
            const resp = await fetch(urlPrest);
            if (!resp.ok) {
                const text = await resp.text();
                throw new Error(`HTTP ${resp.status} al cargar prestamos. Respuesta: ${text.slice(0,300)}`);
            }
            const ct = resp.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                const text = await resp.text();
                throw new Error(`Respuesta no JSON desde Prestamo_fetch.php. Content-Type: ${ct}. Cuerpo: ${text.slice(0,300)}`);
            }
            const data = await resp.json();
            renderPrestamos(data);
        } catch (e) {
            console.error('Error al cargar prestamos', e);
        }
    }

    function renderCalendarios(data, turno){
        calendarios.innerHTML = '';

        const containerLeft = document.createElement('div');
        containerLeft.className = 'calendario-box';

        const containerRight = document.createElement('div');
        containerRight.className = 'calendario-box';

        const titleLeft = document.createElement('h3');
        const cancelCount1 = Object.values(data.cancel1 || {}).reduce((acc, arr)=> acc + (arr?.length||0), 0);
        titleLeft.textContent = (data.aip1_nombre || 'AIP 1') + (cancelCount1 ? `  ·  ${cancelCount1} cancelada(s)` : '');
        const titleRight = document.createElement('h3');
        const cancelCount2 = Object.values(data.cancel2 || {}).reduce((acc, arr)=> acc + (arr?.length||0), 0);
        titleRight.textContent = (data.aip2_nombre || 'AIP 2') + (cancelCount2 ? `  ·  ${cancelCount2} cancelada(s)` : '');

        containerLeft.appendChild(titleLeft);
        containerRight.appendChild(titleRight);

        const tableLeft = buildTableForAula(data.aip1, data.cancel1, turno, startOfWeek);
        const tableRight = buildTableForAula(data.aip2, data.cancel2, turno, startOfWeek);

        containerLeft.appendChild(tableLeft);
        containerRight.appendChild(tableRight);

        calendarios.appendChild(containerLeft);
        calendarios.appendChild(containerRight);
    }

   function buildTableForAula(aipData, cancelData, turno, startWeek){
    const table = document.createElement('table');
    table.className = 'calendario-table';

    const thead = document.createElement('thead');
    const trHead = document.createElement('tr');
    const thEmpty = document.createElement('th');
    thEmpty.textContent = '';
    trHead.appendChild(thEmpty);

    const dias = getWeekDates(startWeek);
    dias.forEach(fecha => {
        const th = document.createElement('th');
        const d = new Date(fecha + 'T00:00:00');
        const label = d.toLocaleDateString('es-PE', { weekday:'long' });
        const nice = label.charAt(0).toUpperCase() + label.slice(1);
        th.innerHTML = `${nice}<br><small>${fecha}</small>`;
        trHead.appendChild(th);
    });
    thead.appendChild(trHead);
    table.appendChild(thead);

    const tbody = document.createElement('tbody');
    const times = getTimesForTurno(turno);

    // cancelBelow: agenda de colocación para la palabra CANCELADO por fecha (columna) y fila (índice de times)
    // Estructura: { [fecha]: { [rowIndex]: true } }
    const cancelBelow = {};

    times.forEach((time, idx) => {
        const tr = document.createElement('tr');
        const timeTd = document.createElement('td');
        timeTd.className = 'hora-cell';
        timeTd.textContent = time;
        tr.appendChild(timeTd);

        dias.forEach(fecha => {
            const td = document.createElement('td');
            td.dataset.fecha = fecha;
            td.dataset.hora = time;
            td.className = 'cell';

            const reservas = (aipData && aipData[fecha]) ? aipData[fecha] : [];
            const canceladas = (cancelData && cancelData[fecha]) ? cancelData[fecha] : [];

            // 1) Si está agendado que en ESTA fila/fecha vaya "CANCELADO", colócalo primero
            if (cancelBelow[fecha] && cancelBelow[fecha][idx]) {
                td.classList.add('canceled-below');
                td.innerHTML = `<div class="cancel-label">CANCELADO</div>`;
                delete cancelBelow[fecha][idx];
            }

            // 2) Mostrar reservas (solo las horas) en la PRIMERA celda del rango
            //    Si esta celda está designada para mostrar "CANCELADO" abajo, NO sobrescribirla con horas
            reservas.forEach(r => {
                if (td.classList.contains('canceled-below')) return; // mantener solo CANCELADO aquí
                if (isTimeBetween(time, r.hora_inicio, r.hora_fin)) {
                    td.classList.add('reserved');
                    if (!r._shown) {
                        td.innerHTML = `<div class="res-label">${r.hora_inicio.substr(0,5)} - ${r.hora_fin.substr(0,5)}</div>`;
                        r._shown = true;
                    }
                }
            });

            // 3) Si hay cancelaciones para este bloque, imprimimos SOLO las horas en esta fila
            //    y agendamos mostrar "CANCELADO" en la fila inmediatamente inferior (idx+1)
            canceladas.forEach(c => {
                if (isTimeBetween(time, c.hora_inicio || '', c.hora_fin || '')) {
                    td.classList.add('canceled');
                    if (!c._scheduled) {
                        const horas = `${(c.hora_inicio||'').substr(0,5)} - ${(c.hora_fin||'').substr(0,5)}`;
                        if (!td.innerHTML) {
                            td.innerHTML = `<div class="res-label">${horas}</div>`;
                        }
                        const nextIdx = idx + 1;
                        if (nextIdx < times.length) {
                            if (!cancelBelow[fecha]) cancelBelow[fecha] = {};
                            cancelBelow[fecha][nextIdx] = true;
                        } else {
                            // Fila final: mostrar también aquí como respaldo
                            td.classList.add('canceled-below');
                            td.innerHTML += `<div class="cancel-label">CANCELADO</div>`;
                        }
                        c._scheduled = true;
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

    function getWeekDates(start){
        // Genera fechas de Lunes a Sábado (6 días) a partir de start (que debe ser lunes)
        const base = new Date(start + 'T00:00:00');
        const days = [];
        for (let i=0; i<6; i++){
            const d = new Date(base);
            d.setDate(base.getDate()+i);
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth()+1).padStart(2,'0');
            const dd = String(d.getDate()).padStart(2,'0');
            days.push(`${yyyy}-${mm}-${dd}`);
        }
        return days;
    }

    function getTimesForTurno(turno){
        const times = [];
        let start = turno === 'manana' ? '06:00' : '13:00';
        let end   = turno === 'manana' ? '12:45' : '19:00';
        let [sh, sm] = start.split(':').map(Number);
        const [eh, em] = end.split(':').map(Number);
        while (sh < eh || (sh === eh && sm <= em)){
            times.push(String(sh).padStart(2,'0') + ':' + String(sm).padStart(2,'0'));
            sm += 45;
            if (sm >= 60){ sm = 0; sh++; }
        }
        return times;
    }

    function isTimeBetween(t, start, end){
      const tt = (t.length===5 ? t+':00' : t);
      if (start.length===5) start += ':00';
      if (end.length===5) end += ':00';

        // Incluir también la hora final exacta
      return (tt >= start && tt <= end);
    }
    function renderPrestamos(data){
        const container = document.getElementById('tabla-prestamos');
        if (!data || data.length === 0) {
            container.innerHTML = '<p>No hay préstamos registrados.</p>';
            return;
        }
        let html = '<table class="tabla-prestamos"><thead><tr><th>ID</th><th>Equipo</th><th>Profesor</th><th>Aula</th><th>Fecha</th><th>Hora inicio</th><th>Hora fin</th><th>Estado</th></tr></thead><tbody>';
        data.forEach(p => {
            html += `<tr><td>${p.id_prestamo}</td><td>${p.nombre_equipo}</td><td>${p.nombre}</td><td>${p.nombre_aula}</td><td>${p.fecha_prestamo}</td><td>${p.hora_inicio}</td><td>${p.hora_fin || ''}</td><td>${p.estado}</td></tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    }

    loadAll();
});
