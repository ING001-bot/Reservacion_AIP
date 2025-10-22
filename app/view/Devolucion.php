<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/conexion.php';
require_once '../controllers/PrestamoController.php';

$controller = new PrestamoController($conexion);

if (!isset($_SESSION['usuario']) || $_SESSION['tipo']!=='Encargado') {
    die("Acceso denegado");
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['devolver_id'])) {
        $id = intval($_POST['devolver_id']);
        $coment = isset($_POST['comentario']) ? trim($_POST['comentario']) : null;
        if ($controller->devolverEquipo($id, $coment)) {
            $mensaje = '✅ Devolución registrada correctamente.';
        } else {
            $mensaje = '❌ No se pudo registrar la devolución.';
        }
    }
    if (isset($_POST['devolver_grupo_ids'])) {
        $idsJson = $_POST['devolver_grupo_ids'] ?? '[]';
        $ids = json_decode($idsJson, true);
        $coment = isset($_POST['comentario_grupo']) ? trim($_POST['comentario_grupo']) : null;
        
        if (is_array($ids) && !empty($ids)) {
            $exitosos = 0;
            foreach ($ids as $id) {
                if ($controller->devolverEquipo(intval($id), $coment)) {
                    $exitosos++;
                }
            }
            if ($exitosos === count($ids)) {
                $mensaje = '✅ Devolución de ' . $exitosos . ' equipo(s) registrada correctamente.';
            } else if ($exitosos > 0) {
                $mensaje = '⚠️ Se registraron ' . $exitosos . ' de ' . count($ids) . ' devoluciones.';
            } else {
                $mensaje = '❌ No se pudo registrar ninguna devolución.';
            }
        }
    }
    if (isset($_POST['devolver_pack_id'])) {
        $idp = intval($_POST['devolver_pack_id']);
        $comentp = isset($_POST['comentario_pack']) ? trim($_POST['comentario_pack']) : null;
        if ($controller->devolverPack($idp, $comentp)) {
            $mensaje = '✅ Devolución de pack registrada correctamente.';
        } else {
            $mensaje = '❌ No se pudo registrar la devolución del pack.';
        }
    }
}

// Filtros simples - Por defecto mostrar todos los estados (así, al confirmar, la fila sigue visible)
$estado = $_GET['estado'] ?? '';
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$q     = $_GET['q'] ?? '';

// Si no hay filtros de fecha, limitar a últimos 30 días para mejor rendimiento
if (empty($desde) && empty($hasta)) {
    $desde = date('Y-m-d', strtotime('-30 days'));
}

// Siempre usar filtros para optimizar la consulta
$prestamos = $controller->obtenerPrestamosFiltrados($estado ?: null, $desde ?: null, $hasta ?: null, $q ?: null);
$packs = $controller->listarPacksFiltrados($estado ?: null, $desde ?: null, $hasta ?: null, $q ?: null);
$usuario = htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Devolución</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Estilos propios -->
    <link rel="stylesheet" href="../../Public/css/brand.css">
    <style>
      /* Estilos simples para tabla de préstamos */
      .table-brand {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      }
      
      .table-brand thead {
        background: #0d6efd;
        color: white;
      }
      
      .table-brand thead th {
        font-weight: 600;
        padding: 0.75rem;
        border: none;
      }
      
      .table-brand tbody tr:hover {
        background-color: #f8f9fa;
      }
      
      .table-brand tbody td {
        padding: 0.75rem;
        vertical-align: middle;
      }
      
      .badge {
        padding: 0.35rem 0.65rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 4px;
      }
      
      .badge.bg-info {
        background: #0dcaf0 !important;
        color: #000 !important;
      }
      
      .badge.bg-warning {
        background: #ffc107 !important;
        color: #000 !important;
      }
      
      .badge.bg-success {
        background: #198754 !important;
        color: #fff !important;
      }
      
      .btn-success {
        background: #198754;
        border: none;
        font-weight: 500;
      }
      
      .btn-success:hover {
        background: #157347;
      }
      
      .filters .form-label { 
        font-weight: 600; 
      }
      
      .text-brand {
        color: #0d6efd;
        font-weight: 700;
      }
      
      /* Submit animation */
      .modal.submitting .modal-content { 
        transform: scale(0.98); 
        opacity: .85; 
        transition: transform .2s ease, opacity .2s ease; 
      }
      
      .toast-container { 
        position: fixed; 
        bottom: 1rem; 
        right: 1rem; 
        z-index: 1080; 
      }
      
      /* Responsive */
      @media (max-width: 768px) {
        .table-brand {
          font-size: 0.85rem;
        }
        
        .table-brand thead th,
        .table-brand tbody td {
          padding: 0.6rem 0.4rem;
        }
        
        .badge {
          font-size: 0.75rem;
          padding: 0.3rem 0.5rem;
        }
      }
    </style>
</head>
<body>
<?php require __DIR__ . '/partials/navbar.php'; ?>
    <main class="container py-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <h1 class="text-brand m-0">📦 Registrar Devolución</h1>
            <div class="text-muted">Encargado: <strong><?= $usuario ?></strong></div>
        </div>

        <form class="card p-3 shadow-sm mb-3" method="get" action="">
            <div class="row g-2 align-items-end filters">
                <div class="col-6 col-md-2">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="Prestado" <?= $estado==='Prestado'?'selected':'' ?>>Prestado</option>
                        <option value="Devuelto" <?= $estado==='Devuelto'?'selected':'' ?>>Devuelto</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" class="form-control" name="desde" value="<?= htmlspecialchars($desde) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" class="form-control" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Buscar</label>
                    <input type="text" class="form-control" name="q" placeholder="Equipo, profesor o aula" value="<?= htmlspecialchars($q) ?>">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2 justify-content-end align-items-center">
                    <button class="btn btn-sm btn-brand rounded-pill px-3 w-100 w-md-auto" type="submit">🔎 Aplicar</button>
                    <a class="btn btn-sm btn-outline-secondary rounded-pill px-3 w-100 w-md-auto" href="Devolucion.php">🧹 Limpiar</a>
                </div>
            </div>
        </form>
        <?php
          // Optimización máxima: Procesamiento rápido
          $rows = [];
          
          // Agrupar préstamos unitarios (solo si hay datos)
          if (!empty($prestamos)) {
            $grupos = [];
            foreach ($prestamos as $r) {
              // Algunos selects no incluyen id_aula; usar nombre_aula como parte de la clave de agrupación
              $aulaKey = $r['nombre_aula'] ?? '';
              $key = $r['nombre'].'|'.$r['fecha_prestamo'].'|'.$r['hora_inicio'].'|'.$aulaKey;
              
              if (!isset($grupos[$key])) {
                $grupos[$key] = [
                  'tipo_reg' => 'unitario_grupo',
                  'detalle_badges' => [$r['nombre_equipo']],
                  'ids_prestamos' => [(int)$r['id_prestamo']],
                  'responsable' => $r['nombre'],
                  'aula' => $r['nombre_aula'],
                  'fecha' => $r['fecha_prestamo'],
                  'hora_inicio' => $r['hora_inicio'],
                  'hora_fin' => $r['hora_fin'],
                  'fecha_devolucion' => $r['fecha_devolucion'],
                  'estado' => $r['estado'],
                ];
              } else {
                $grupos[$key]['detalle_badges'][] = $r['nombre_equipo'];
                $grupos[$key]['ids_prestamos'][] = (int)$r['id_prestamo'];
              }
            }
            $rows = array_values($grupos);
          }
          
          // Agregar packs (solo si hay datos)
          if (!empty($packs)) {
            foreach ($packs as $p) {
              $badges = [];
              if (isset($p['items'])) {
                foreach ($p['items'] as $it) {
                  $badges[] = $it['tipo_equipo'].' x'.$it['cantidad'].($it['es_complemento']?' (C)':'');
                }
              }
              
              $rows[] = [
                'tipo_reg' => 'pack',
                'detalle_badges' => $badges ?: ['Pack'],
                'responsable' => $p['nombre_usuario'],
                'aula' => $p['nombre_aula'],
                'fecha' => $p['fecha_prestamo'],
                'hora_inicio' => $p['hora_inicio'],
                'hora_fin' => $p['hora_fin'],
                'fecha_devolucion' => $p['fecha_devolucion'],
                'estado' => $p['estado'],
                'id_pack' => $p['id_pack'],
              ];
            }
          }
        ?>

        <h2 class="text-center text-brand mb-3">📖 Préstamos Registrados</h2>
        <div class="table-responsive shadow-lg">
            <table class="table table-hover align-middle text-center table-brand">
                <thead class="table-primary text-center">
                    <tr>
                        <th>Equipo(s)</th>
                        <th>Responsable</th>
                        <th>Aula</th>
                        <th>Fecha</th>
                        <th>Hora Inicio</th>
                        <th>Hora Fin</th>
                        <th>Estado</th>
                        <th>Devolución</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                        <tbody>
                        <?php if(!empty($rows)): ?>
                            <?php foreach($rows as $r): ?>
                                <tr>
                                    <td>
                                        <?php foreach (($r['detalle_badges'] ?? []) as $chunk): ?>
                                            <span class="badge bg-info me-1"><?= htmlspecialchars($chunk) ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td><?= htmlspecialchars($r['responsable'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($r['aula'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($r['fecha'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($r['hora_inicio'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($r['hora_fin'] ?: '-') ?></td>
                                    <td>
                                        <?php if(($r['estado'] ?? '')==='Prestado'): ?>
                                            <span class="badge bg-warning">Prestado</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Devuelto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= !empty($r['fecha_devolucion']) ? htmlspecialchars($r['fecha_devolucion']) : '-' ?></td>
                                    <td>
                                        <?php if(($r['estado'] ?? '')==='Prestado'): ?>
                                            <?php if(($r['tipo_reg'] ?? '')==='unitario_grupo'): ?>
                                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalDevolverGrupo" data-ids="<?= htmlspecialchars(json_encode($r['ids_prestamos'] ?? [])) ?>" data-equipos="<?= htmlspecialchars(implode(', ', $r['detalle_badges'] ?? [])) ?>">
                                                    ✅ Confirmar
                                                </button>
                                            <?php elseif(($r['tipo_reg'] ?? '')==='pack'): ?>
                                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalDevolverPack" data-idpack="<?= (int)($r['id_pack'] ?? 0) ?>" data-detalle="<?= htmlspecialchars(implode(' | ', $r['detalle_badges'] ?? [])) ?>">
                                                    ✅ Confirmar
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-success">✔ Devuelto</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-muted py-4">
                                    No hay préstamos registrados.
                                </td>
                            </tr>
                        <?php endif; ?>
            </tbody>
        </table>
    </div>

        
    </main>

    <!-- Toast feedback -->
    <div class="toast-container">
      <div id="toastFeedback" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body" id="toastFeedbackBody">Acción realizada</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    </div>

    <!-- Modal Devolver Grupo de Equipos -->
    <div class="modal fade" id="modalDevolverGrupo" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post" action="" id="formDevolverGrupo">
            <div class="modal-header">
              <h5 class="modal-title">Confirmar Devolución de Equipos</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="devolver_grupo_ids" id="devolver-grupo-ids" value="">
              <div class="mb-2">
                <div class="small text-muted">Equipos</div>
                <div id="devolver-grupo-equipos" class="fw-semibold"></div>
              </div>
              <div class="mb-3">
                <label class="form-label">Estado de los equipos</label>
                <select class="form-select" id="estado-entrega-grupo" name="estado_entrega_grupo">
                  <option value="ok" selected>En buen estado / Todo correcto</option>
                  <option value="mal">Mal estado</option>
                </select>
              </div>
              <label class="form-label">Comentario</label>
              <textarea class="form-control" id="comentario-entrega-grupo" name="comentario_grupo" rows="3" placeholder="Describe el problema..." disabled></textarea>
              <small class="text-muted">Solo requerido si el estado es "Mal estado"</small>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-success">Marcar como Devuelto</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Devolver Pack -->
    <div class="modal fade" id="modalDevolverPack" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post" action="">
            <div class="modal-header">
              <h5 class="modal-title">Confirmar Devolución de Pack</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="devolver_pack_id" id="devolver-pack-id" value="">
              <div class="mb-2">
                <div class="small text-muted">Detalle</div>
                <div id="devolver-pack-detalle" class="fw-semibold"></div>
              </div>
              <div class="mb-3">
                <label class="form-label">Estado de los equipos</label>
                <select class="form-select" id="estado-entrega-pack" name="estado_entrega_pack">
                  <option value="ok" selected>En buen estado / Todo correcto</option>
                  <option value="mal">Mal estado</option>
                </select>
              </div>
              <label class="form-label">Comentario</label>
              <textarea class="form-control" id="comentario-entrega-pack" name="comentario_pack" rows="3" placeholder="Describe el problema..." disabled></textarea>
              <small class="text-muted">Solo requerido si el estado es "Mal estado"</small>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-success">Marcar como Devuelto</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Devolver unitario -->
    <div class="modal fade" id="modalDevolver" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post" action="">
            <div class="modal-header">
              <h5 class="modal-title">Confirmar Devolución</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="devolver_id" id="devolver-id" value="">
              <div class="mb-2">
                <div class="small text-muted">Equipo</div>
                <div id="devolver-equipo" class="fw-semibold"></div>
              </div>
              <div class="mb-3">
                <label class="form-label">Estado del equipo</label>
                <select class="form-select" id="estado-entrega" name="estado_entrega">
                  <option value="ok" selected>En buen estado / Todo correcto</option>
                  <option value="mal">Mal estado</option>
                </select>
              </div>
              <label class="form-label">Comentario</label>
              <textarea class="form-control" id="comentario-entrega" name="comentario" rows="3" placeholder="Describe el problema..." disabled></textarea>
              <small class="text-muted">Solo requerido si el estado es "Mal estado"</small>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-success">Marcar como Devuelto</button>
            </div>
          </form>
        </div>
      </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function(){
  // Modal para grupo de equipos
  const modalGrupo = document.getElementById('modalDevolverGrupo');
  if (modalGrupo) {
    const estadoSelG = modalGrupo.querySelector('#estado-entrega-grupo');
    const comentarioG = modalGrupo.querySelector('#comentario-entrega-grupo');
    
    function refreshComentarioGrupo(){
      const mal = estadoSelG.value === 'mal';
      comentarioG.disabled = !mal;
      comentarioG.required = mal;
      if (!mal) comentarioG.value = '';
    }
    
    const formG = modalGrupo.querySelector('form');
    formG?.addEventListener('submit', function(){
      const btn = formG.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
      }
    });
    
    modalGrupo.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const ids = button?.getAttribute('data-ids') || '[]';
      const equipos = button?.getAttribute('data-equipos') || '';
      modalGrupo.querySelector('#devolver-grupo-ids').value = ids;
      modalGrupo.querySelector('#devolver-grupo-equipos').textContent = equipos;
      if (estadoSelG) estadoSelG.value = 'ok';
      if (comentarioG) { comentarioG.value=''; comentarioG.disabled = true; comentarioG.required = false; }
    });
    
    if (estadoSelG) estadoSelG.addEventListener('change', refreshComentarioGrupo);
  }
  
  // Modal unitario
  const modal = document.getElementById('modalDevolver');
  if (modal) {
    const estadoSel = modal.querySelector('#estado-entrega');
    const comentario = modal.querySelector('#comentario-entrega');
    
    function refreshComentario(){
      const mal = estadoSel.value === 'mal';
      comentario.disabled = !mal;
      comentario.required = mal;
      if (!mal) comentario.value = '';
    }
    
    const form = modal.querySelector('form');
    form?.addEventListener('submit', function(){
      const btn = form.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
      }
    });
    
    modal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const id = button?.getAttribute('data-id') || '';
      const equipo = button?.getAttribute('data-equipo') || '';
      modal.querySelector('#devolver-id').value = id;
      modal.querySelector('#devolver-equipo').textContent = equipo;
      if (estadoSel) estadoSel.value = 'ok';
      if (comentario) { comentario.value=''; comentario.disabled = true; comentario.required = false; }
    });
    
    if (estadoSel) estadoSel.addEventListener('change', refreshComentario);
  }
  // Modal para packs
  const modalPack = document.getElementById('modalDevolverPack');
  if (modalPack) {
    const estadoSelP = modalPack.querySelector('#estado-entrega-pack');
    const comentarioP = modalPack.querySelector('#comentario-entrega-pack');
    
    function refreshComentarioPack(){
      const mal = estadoSelP.value === 'mal';
      comentarioP.disabled = !mal;
      comentarioP.required = mal;
      if (!mal) comentarioP.value = '';
    }
    
    const formP = modalPack.querySelector('form');
    formP?.addEventListener('submit', function(){
      const btn = formP.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
      }
    });
    
    modalPack.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const idp = button?.getAttribute('data-idpack') || '';
      const detalle = button?.getAttribute('data-detalle') || '';
      modalPack.querySelector('#devolver-pack-id').value = idp;
      modalPack.querySelector('#devolver-pack-detalle').textContent = detalle;
      if (estadoSelP) estadoSelP.value = 'ok';
      if (comentarioP) { comentarioP.value=''; comentarioP.disabled = true; comentarioP.required = false; }
    });
    
    if (estadoSelP) estadoSelP.addEventListener('change', refreshComentarioPack);
  }

  // Toast after server feedback
  <?php if (!empty($mensaje)): ?>
    (function(){
      const toastEl = document.getElementById('toastFeedback');
      const body = document.getElementById('toastFeedbackBody');
      if (body) body.textContent = <?= json_encode($mensaje, JSON_UNESCAPED_UNICODE) ?>;
      const t = new bootstrap.Toast(toastEl, { delay: 3000 });
      t.show();
    })();
  <?php endif; ?>
});
// Bootstrap Bundle (modals)
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
