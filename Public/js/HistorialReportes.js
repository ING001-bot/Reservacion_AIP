// Public/js/HistorialReportes.js
(function(){
  const root = document.getElementById('historial-reportes');
  if (!root) return;
  const role = root.dataset.role || '';

  const formFiltros = document.getElementById('form-filtros');
  const btnReset = document.getElementById('btn-reset');
  const tbody = document.querySelector('#tabla-historial tbody');
  const btnPdf = document.getElementById('btn-export-pdf');
  const btnCsv = document.getElementById('btn-export-csv');
  const countBadge = document.getElementById('count-badge');

  // Rango rápido
  document.querySelectorAll('[data-range]')?.forEach(btn => {
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
      loadData();
    });
  });

  function buildQuery(){
    const fd = new FormData(formFiltros);
    const params = new URLSearchParams();
    for (const [k,v] of fd.entries()){
      if (String(v).trim() !== '') params.append(k, String(v));
    }
    params.append('_', Date.now());
    return params.toString() ? ('?' + params.toString()) : '';
  }

  function badgeTipo(tipo){
    const t = (tipo||'').toLowerCase();
    if (t.includes('préstamo') || t.includes('prestamo')) return '<span class="badge bg-prestamo">Préstamo</span>';
    return '<span class="badge bg-reserva">Reserva</span>';
  }
  function badgeEstado(estado, fecha){
    const e = (estado||'').toLowerCase();
    if (e === 'cancelada') return '<span class="badge bg-cancelada">Cancelada</span>';
    if (e === 'prestado') return '<span class="badge bg-prestado">Prestado</span>';
    if (e === 'devuelto' || e === 'finalizada') return '<span class="badge bg-finalizada">Finalizada</span>';
    if (e === 'incidente') return '<span class="badge bg-incidente">Incidente</span>';
    // Activa -> si la fecha ya pasó, mostrar Finalizada (auto), si no, Programada
    if (e === 'activa') {
      const todayStr = new Date().toISOString().slice(0,10);
      if (fecha && fecha < todayStr) return '<span class="badge bg-finalizada">Finalizada</span>';
      return '<span class="badge bg-programada">Programada</span>';
    }
    return '<span class="badge bg-programada">Programada</span>';
  }

  function renderTable(data){
    if (!data || data.length === 0){
      tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Sin resultados para los filtros seleccionados.</td></tr>';
      if (countBadge) countBadge.textContent = '0';
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
          <td>${badgeEstado(it.estado, it.fecha)}</td>
          <td>${obs}</td>
        </tr>
      `;
    });
    tbody.innerHTML = rows.join('');
    if (countBadge) countBadge.textContent = String(data.length);
  }

  async function loadData(){
    try{
      const url = `../../app/api/HistorialGlobal_fetch.php${buildQuery()}`;
      const resp = await fetch(url);
      if (!resp.ok){ const t = await resp.text(); throw new Error(`HTTP ${resp.status}. ${t.slice(0,200)}`); }
      const ct = resp.headers.get('content-type')||'';
      if (!ct.includes('application/json')){ const t=await resp.text(); throw new Error(`No JSON. CT: ${ct}. Body: ${t.slice(0,200)}`); }
      const json = await resp.json();
      const data = Array.isArray(json.data) ? json.data : [];
      renderTable(data);
    }catch(e){
      console.error('Error al cargar reportes', e);
      tbody.innerHTML = `<tr><td colspan="8" class="text-danger">Error: ${e.message}</td></tr>`;
      if (countBadge) countBadge.textContent = '0';
    }
  }

  function exportCSV(){
    const rows = Array.from(tbody.querySelectorAll('tr'));
    if (!rows.length) return;
    const headers = ['Fecha','Inicio','Fin','Profesor','Aula/Equipo','Tipo','Estado','Observación'];
    const lines = [headers.join(',')];
    rows.forEach(tr => {
      const cols = Array.from(tr.children).map(td => td.innerText);
      lines.push(cols.map(v => '"' + String(v).replace(/"/g,'""') + '"').join(','));
    });
    const blob = new Blob([lines.join('\n')], {type: 'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href=url; a.download='historial_reportes.csv';
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  }

  formFiltros?.addEventListener('submit', (e)=>{ e.preventDefault(); loadData(); });
  btnReset?.addEventListener('click', ()=>{ formFiltros?.reset(); loadData(); });
  btnCsv?.addEventListener('click', exportCSV);
  btnPdf?.addEventListener('click', ()=>{
    const fd = new FormData(formFiltros);
    const params = new URLSearchParams();
    for (const [k,v] of fd.entries()){
      if (String(v).trim() !== '') params.append(k, String(v));
    }
    const url = `../../app/api/HistorialReportes_pdf.php${params.toString() ? ('?' + params.toString()) : ''}`;
    window.open(url, '_blank');
  });

  // inicial
  loadData();
})();
