// Public/js/HistorialEstadisticas.js
(function(){
  const root = document.getElementById('historial-reportes');
  if (!root) return;

  const formFiltros = document.getElementById('form-filtros');
  const btnReset = document.getElementById('btn-reset');

  const statReservas = document.getElementById('stat-reservas');
  const statCancel = document.getElementById('stat-cancelaciones');
  const statPrestamos = document.getElementById('stat-prestamos');
  const statHoras = document.getElementById('stat-horas');

  const listTopProfRes = document.getElementById('list-top-prof-reservas');
  const listTopProfPrest = document.getElementById('list-top-prof-prestamos');
  const listTopAulas = document.getElementById('list-top-aulas');

  const chartCanvas = document.getElementById('chart-reservas-dia');
  let chartInstance = null;

  function buildQuery(){
    const fd = new FormData(formFiltros);
    const params = new URLSearchParams();
    for (const [k,v] of fd.entries()){
      if (String(v).trim() !== '') params.append(k, String(v));
    }
    params.append('_', Date.now());
    return params.toString() ? ('?' + params.toString()) : '';
  }

  function setCard(el, val){ if (el) el.textContent = String(val ?? 0); }

  function renderList(el, arr, labelKey, valueKey){
    if (!el) return;
    if (!arr || arr.length === 0){ el.innerHTML = '<li class="text-muted">Sin datos en el rango seleccionado.</li>'; return; }
    el.innerHTML = arr.map((it, idx) => {
      const label = (it[labelKey] || '—').toString();
      const value = it[valueKey] ?? 0;
      return `<li><strong>${idx+1}.</strong> ${label} <span class="text-muted">(${value})</span></li>`;
    }).join('');
  }

  function renderChart(seriesObj){
    if (!chartCanvas) return;
    const labels = Object.keys(seriesObj || {}).sort();
    const data = labels.map(k => seriesObj[k]);

    if (chartInstance){ chartInstance.destroy(); }
    chartInstance = new Chart(chartCanvas, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Reservas',
          data,
          borderColor: '#1e6bd6',
          backgroundColor: 'rgba(30,107,214,0.12)',
          tension: 0.3,
          fill: true,
          pointRadius: 3,
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { maxRotation: 0, autoSkip: true }, grid: { display: false } },
          y: { beginAtZero: true, grid: { color: '#eef2f7' } }
        }
      }
    });
  }

  async function loadStats(){
    try{
      const url = `/Sistema_reserva_AIP/app/api/HistorialEstadisticas_fetch.php${buildQuery()}`;
      const resp = await fetch(url);
      if (!resp.ok){ const t = await resp.text(); throw new Error(`HTTP ${resp.status}. ${t.slice(0,200)}`); }
      const ct = resp.headers.get('content-type')||'';
      if (!ct.includes('application/json')){ const t=await resp.text(); throw new Error(`No JSON. CT: ${ct}. Body: ${t.slice(0,200)}`); }
      const json = await resp.json();
      const res = json || {};
      const resumen = res.resumen || {};

      setCard(statReservas, resumen.reservas);
      setCard(statCancel, resumen.cancelaciones);
      setCard(statPrestamos, resumen.prestamos);
      setCard(statHoras, resumen.horas_reservadas);

      renderList(listTopProfRes, res.top_profesores_reservas || [], 'profesor', 'cantidad');
      renderList(listTopProfPrest, res.top_profesores_prestamos || [], 'profesor', 'cantidad');
      renderList(listTopAulas, res.top_aulas_reservas || [], 'aula', 'cantidad');
      renderChart(res.reservas_por_dia || {});
    }catch(e){
      console.error('Error al cargar estadísticas', e);
      setCard(statReservas, 0); setCard(statCancel, 0); setCard(statPrestamos, 0); setCard(statHoras, 0);
      if (listTopProfRes) listTopProfRes.innerHTML = '<li class="text-danger">Error al cargar</li>';
      if (listTopProfPrest) listTopProfPrest.innerHTML = '<li class="text-danger">Error al cargar</li>';
      if (listTopAulas) listTopAulas.innerHTML = '<li class="text-danger">Error al cargar</li>';
      renderChart({});
    }
  }

  // Enlazar con los mismos controles de filtros
  formFiltros?.addEventListener('submit', (e)=>{ e.preventDefault(); loadStats(); });
  btnReset?.addEventListener('click', ()=>{ setTimeout(loadStats, 0); });
  document.querySelectorAll('[data-range]')?.forEach(btn => btn.addEventListener('click', ()=> setTimeout(loadStats, 0)));

  // Inicial
  loadStats();
})();
