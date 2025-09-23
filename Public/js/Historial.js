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
            const resp = await fetch(`../../app/api/Historial_fetch.php?start=${startOfWeek}&turno=${turno}`);
            const data = await resp.json();
            renderCalendarios(data, turno);
            loadPrestamos();
        } catch (e) {
            console.error('Error al cargar historial', e);
        }
    }

    async function loadPrestamos(){
        try {
            const resp = await fetch('../../app/api/prestamos_fetch.php');
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
        titleLeft.textContent = data.aip1_nombre || 'AIP 1';
        const titleRight = document.createElement('h3');
        titleRight.textContent = data.aip2_nombre || 'AIP 2';

        containerLeft.appendChild(titleLeft);
        containerRight.appendChild(titleRight);

        const tableLeft = buildTableForAula(data.aip1, turno);
        const tableRight = buildTableForAula(data.aip2, turno);

        containerLeft.appendChild(tableLeft);
        containerRight.appendChild(tableRight);

        calendarios.appendChild(containerLeft);
        calendarios.appendChild(containerRight);
    }

   function buildTableForAula(aipData, turno){
    const table = document.createElement('table');
    table.className = 'calendario-table';

    const thead = document.createElement('thead');
    const trHead = document.createElement('tr');
    const thEmpty = document.createElement('th');
    thEmpty.textContent = '';
    trHead.appendChild(thEmpty);

    const dias = Object.keys(aipData);
    dias.forEach(fecha => {
        const th = document.createElement('th');
        const d = new Date(fecha + 'T00:00:00');
        th.innerHTML = `${d.toLocaleDateString('es-PE', {weekday:'long'})}<br><small>${fecha}</small>`;
        trHead.appendChild(th);
    });
    thead.appendChild(trHead);
    table.appendChild(thead);

    const tbody = document.createElement('tbody');
    const times = getTimesForTurno(turno);

    times.forEach(time => {
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

            const reservas = aipData[fecha] || [];
            reservas.forEach(r => {
                if (isTimeBetween(time, r.hora_inicio, r.hora_fin)) {
                    td.classList.add('reserved');

                    // Mostrar solo en la primera celda del rango
                    if (!r._shown) {
                        td.innerHTML = `<div class="res-label">
                            ${r.hora_inicio.substr(0,5)} - ${r.hora_fin.substr(0,5)}
                            <br><small>${r.profesor}</small>
                        </div>`;
                        r._shown = true; // marcamos que ya se mostró
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
