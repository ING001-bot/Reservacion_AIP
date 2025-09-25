// Public/js/HistorialGlobal.js
(function(){
  const root = document.getElementById('historial-global');
  if (!root) return;
  const role = root.dataset.role || '';

  const panelFiltros = document.getElementById('panel-filtros');
  const panelStats = document.getElementById('panel-stats');
  const formFiltros = document.getElementById('form-filtros');
  const btnReset = document.getElementById('btn-reset');
  const tbody = document.querySelector('#tabla-historial tbody');
  const btnPdf = document.getElementById('btn-export-pdf');
  const btnCsv = document.getElementById('btn-export-csv');

  const statReservas = document.getElementById('stat-reservas');
  const statPrestamos = document.getElementById('stat-prestamos');
  const statCancel = document.getElementById('stat-cancel');
  const statInc = document.getElementById('stat-inc');
  const countBadge = document.getElementById('count-badge');

  // Mostrar filtros y stats solo a Administrador
  const isAdmin = role === 'Administrador';
  if (isAdmin) {
    panelFiltros?.removeAttribute('hidden');
    panelStats?.removeAttribute('hidden');
  }

  let currentData = [];
  const calendarProfFilter = document.getElementById('calendar-prof-filter');
  function syncCalendarProfessor() {
    if (!isAdmin || !formFiltros || !calendarProfFilter) return;
    const input = formFiltros.elements.namedItem('profesor');
    if (input) {
      calendarProfFilter.value = String(input.value || '');
    }
  }

  function buildQuery() {
    if (!isAdmin) return '';
    const fd = new FormData(formFiltros);
    const params = new URLSearchParams();
    for (const [k, v] of fd.entries()) {
      if (String(v).trim() !== '') params.append(k, String(v));
    }
    params.append('_', Date.now());
    return params.toString() ? ('?' + params.toString()) : '';
  }

  async function loadData() {
    try {
      const url = `/Sistema_reserva_AIP/app/api/HistorialGlobal_fetch.php${buildQuery()}`;
      const resp = await fetch(url);
      if (!resp.ok) {
        const text = await resp.text();
        throw new Error(`HTTP ${resp.status}. Respuesta: ${text.slice(0,300)}`);
      }
      const ct = resp.headers.get('content-type') || '';
      if (!ct.includes('application/json')) {
        const text = await resp.text();
        throw new Error(`Respuesta no JSON. CT: ${ct}. Cuerpo: ${text.slice(0,300)}`);
      }
      const json = await resp.json();
      currentData = Array.isArray(json.data) ? json.data : [];
      renderTable(currentData);
      if (isAdmin) computeStats(currentData);
      if (countBadge) countBadge.textContent = String(currentData.length);
    } catch (e) {
      console.error('Error al cargar historial global', e);
      tbody.innerHTML = `<tr><td colspan="8" class="text-danger">Error: ${e.message}</td></tr>`;
      if (countBadge) countBadge.textContent = '0';
    }
  }

  function badgeTipo(tipo) {
    const t = (tipo||'').toLowerCase();
    if (t.includes('préstamo') || t.includes('prestamo')) return '<span class="badge bg-prestamo">Préstamo</span>';
    return '<span class="badge bg-reserva">Reserva</span>';
  }
  function badgeEstado(estado) {
    const e = (estado||'').toLowerCase();
    if (e === 'cancelada') return '<span class="badge bg-cancelada">Cancelada</span>';
    if (e === 'prestado') return '<span class="badge bg-prestado">Prestado</span>';
    if (e === 'devuelto' || e === 'finalizada') return '<span class="badge bg-finalizada">Finalizada</span>';
    if (e === 'incidente') return '<span class="badge bg-incidente">Incidente</span>';
    return '<span class="badge bg-activa">Activa</span>';
  }

  function renderTable(data) {
    if (!data || data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Sin resultados.</td></tr>';
      return;
    }
    const rows = data.map(it => {
      const aulaEquipo = it.equipo ? it.equipo : (it.aula || '');
      const obs = (it.observacion || '').replace(/</g,'&lt;').replace(/>/g,'&gt;');
      return `
        <tr>
          <td>${it.fecha || ''}</td>
          <td>${(it.hora_inicio||'').substring(0,5)}</td>
          <td>${(it.hora_fin||'').substring(0,5)}</td>
          <td>${it.profesor || ''}</td>
          <td>${aulaEquipo}</td>
          <td>${badgeTipo(it.tipo)}</td>
          <td>${badgeEstado(it.estado)}</td>
          <td>${obs}</td>
        </tr>
      `;
    });
    tbody.innerHTML = rows.join('');
  }

  function computeStats(data) {
    const now = new Date();
    const month = now.getMonth();
    const year = now.getFullYear();
    let resCount=0, preCount=0, cancelCount=0, incCount=0;
    data.forEach(it => {
      if (!it.fecha) return;
      const d = new Date(it.fecha + 'T00:00:00');
      if (d.getMonth() === month && d.getFullYear() === year) {
        const t = (it.tipo||'').toLowerCase();
        const e = (it.estado||'').toLowerCase();
        if (t.includes('prest')) preCount++; else resCount++;
        if (e === 'cancelada') cancelCount++;
        if (e === 'incidente') incCount++;
      }
    });
    statReservas.textContent = String(resCount);
    statPrestamos.textContent = String(preCount);
    statCancel.textContent = String(cancelCount);
    statInc.textContent = String(incCount);
  }

  function exportCSV() {
    if (!currentData || currentData.length === 0) return;
    const headers = ['Fecha','Inicio','Fin','Profesor','Aula/Equipo','Tipo','Estado','Observación'];
    const lines = [headers.join(',')];
    currentData.forEach(it => {
      const row = [
        it.fecha || '',
        (it.hora_inicio||'').substring(0,5),
        (it.hora_fin||'').substring(0,5),
        it.profesor || '',
        (it.equipo ? it.equipo : (it.aula||'')),
        (it.tipo || ''),
        (it.estado || ''),
        (it.observacion || '').replace(/\n/g,' ')
      ];
      // CSV escape
      lines.push(row.map(v => '"' + String(v).replace(/"/g,'""') + '"').join(','));
    });
    const blob = new Blob([lines.join('\n')], {type: 'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'historial_global.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  // Eventos
  formFiltros?.addEventListener('submit', (e) => {
    e.preventDefault();
    syncCalendarProfessor();
    loadData();
  });
  btnReset?.addEventListener('click', () => {
    if (!formFiltros) return;
    formFiltros.reset();
    syncCalendarProfessor();
    loadData();
  });
  // Rango rápido
  if (isAdmin) {
    document.querySelectorAll('#panel-filtros [data-range]')?.forEach(btn => {
      btn.addEventListener('click', () => {
        const range = btn.getAttribute('data-range');
        const desde = formFiltros?.elements.namedItem('desde');
        const hasta = formFiltros?.elements.namedItem('hasta');
        if (!desde || !hasta) return;
        const today = new Date();
        const fmt = (d) => {
          const y = d.getFullYear();
          const m = String(d.getMonth()+1).padStart(2,'0');
          const da = String(d.getDate()).padStart(2,'0');
          return `${y}-${m}-${da}`;
        };
        if (range === 'hoy') {
          const f = fmt(today);
          desde.value = f; hasta.value = f;
        } else if (range === '7') {
          const start = new Date(today);
          start.setDate(start.getDate()-6);
          desde.value = fmt(start); hasta.value = fmt(today);
        } else if (range === 'mes') {
          const start = new Date(today.getFullYear(), today.getMonth(), 1);
          const end = new Date(today.getFullYear(), today.getMonth()+1, 0);
          desde.value = fmt(start); hasta.value = fmt(end);
        } else { // 'todo'
          desde.value = ''; hasta.value = '';
        }
        syncCalendarProfessor();
        loadData();
      });
    });
  }
  btnCsv?.addEventListener('click', exportCSV);
  btnPdf?.addEventListener('click', () => {
    // Placeholder: se implementará exportación PDF en backend
    alert('Exportación a PDF se implementará en la siguiente iteración.');
  });

  // Inicial
  loadData();
})();
