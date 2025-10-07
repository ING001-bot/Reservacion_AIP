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

// Filtros simples
$estado = $_GET['estado'] ?? '';
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$q     = $_GET['q'] ?? '';

if ($estado || $desde || $hasta || $q) {
    $prestamos = $controller->obtenerPrestamosFiltrados($estado ?: null, $desde ?: null, $hasta ?: null, $q ?: null);
} else {
    $prestamos = $controller->obtenerTodosPrestamos();
}
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
      /* Scoped polish for Devolucion */
      .card-brand-header { background: linear-gradient(90deg,#5b86e5,#36d1dc); }
      .table-modern thead th { position: sticky; top: 0; z-index: 1; }
      .table-modern td, .table-modern th { vertical-align: middle; }
      .detalle-badges { display: flex; flex-wrap: wrap; gap: .35rem; }
      .detalle-badges .badge { font-weight: 500; }
      .status-badge { font-size: .85rem; }
      .table-modern { font-size: .925rem; }
      .btn-outline-success { border-width: 2px; }
      .filters .form-label { font-weight: 600; }
      @media (max-width: 992px){ .table-modern { font-size: .88rem; } }
      /* Submit animation */
      .modal.submitting .modal-content { transform: scale(0.98); opacity: .85; transition: transform .2s ease, opacity .2s ease; }
      .toast-container { position: fixed; bottom: 1rem; right: 1rem; z-index: 1080; }
    </style>
</head>
<body>
<?php require __DIR__ . '/partials/navbar.php'; ?>
    <main class="container py-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <h1 class="text-brand m-0">📦 Registrar Devolución</h1>
            <div class="text-muted">Encargado: <strong><?= $usuario ?></strong></div>
        </div>
    <!-- Modal Devolver Pack -->
    <div class="modal fade" id="modalDevolverPack" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post" action="?view=devolucion">
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
              <div class="mb-2">
                <label class="form-label">Estado de los equipos</label>
                <select class="form-select" id="estado-entrega-pack" name="estado_entrega_pack">
                  <option value="ok" selected>En buen estado / Todo correcto</option>
                  <option value="mal">Mal estado</option>
                </select>
              </div>
              <label class="form-label">Comentario (opcional)</label>
              <textarea class="form-control" id="comentario-entrega-pack" name="comentario_pack" rows="3" placeholder="Describe los problemas..." disabled></textarea>
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
          <form method="post" action="?view=devolucion">
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
              <label class="form-label">Comentario (opcional)</label>
              <textarea class="form-control" name="comentario_pack" rows="3" placeholder="Observaciones de la devolución..."></textarea>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-success">Marcar como Devuelto</button>
            </div>
          </form>
        </div>
      </div>
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
                    <a class="btn btn-sm btn-outline-secondary rounded-pill px-3 w-100 w-md-auto" href="?view=devolucion">🧹 Limpiar</a>
                </div>
            </div>
        </form>
        <?php
          // Unificar prestamos unitarios y packs en una sola tabla
          $rows = [];
          foreach ($prestamos as $r) {
            $rows[] = [
              'tipo_reg' => 'unitario',
              'detalle' => $r['nombre_equipo'],
              'detalle_badges' => [$r['nombre_equipo']],
              'responsable' => $r['nombre'],
              'aula' => $r['nombre_aula'],
              'tipo_aula' => $r['tipo'],
              'fecha' => $r['fecha_prestamo'],
              'hora_inicio' => $r['hora_inicio'],
              'hora_fin' => $r['hora_fin'],
              'fecha_devolucion' => $r['fecha_devolucion'],
              'estado' => $r['estado'],
              'id' => (int)$r['id_prestamo'],
            ];
          }
          foreach ($packs as $p) {
            $badges = [];
            foreach (($p['items'] ?? []) as $it) {
              $badges[] = $it['tipo_equipo'].' x'.(int)$it['cantidad'].(!empty($it['es_complemento'])?' (C)':'');
            }
            $rows[] = [
              'tipo_reg' => 'pack',
              'detalle' => 'Pack',
              'detalle_badges' => !empty($badges) ? $badges : ['Pack'],
              'responsable' => $p['nombre_usuario'],
              'aula' => $p['nombre_aula'],
              'tipo_aula' => $p['tipo_aula'] ?? '-',
              'fecha' => $p['fecha_prestamo'],
              'hora_inicio' => $p['hora_inicio'],
              'hora_fin' => $p['hora_fin'],
              'fecha_devolucion' => $p['fecha_devolucion'],
              'estado' => $p['estado'],
              'id_pack' => (int)$p['id_pack'],
            ];
          }
          // Ordenar por fecha desc y hora desc
          usort($rows, function($a,$b){
            $cmp = strcmp($b['fecha'] ?? '', $a['fecha'] ?? '');
            if ($cmp !== 0) return $cmp;
            return strcmp($b['hora_inicio'] ?? '', $a['hora_inicio'] ?? '');
          });
        ?>

        <div class="card shadow-lg mb-4">
            <div class="card-header text-white card-brand-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                  <h5 class="mb-0">Préstamos (unitarios y packs)</h5>
                  <span class="small opacity-75">Gestión de devoluciones</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-modern table-striped table-bordered align-middle text-center mb-0">
                        <thead class="table-primary align-middle">
                            <tr>
                                <th class="text-nowrap">Detalle</th>
                                <th class="text-nowrap">Responsable</th>
                                <th class="text-nowrap">Aula</th>
                                <th class="text-nowrap">Fecha</th>
                                <th class="text-nowrap">Hora Inicio</th>
                                <th class="text-nowrap">Hora Fin</th>
                                <th class="text-nowrap">Fecha Devolución</th>
                                <th class="text-nowrap">Estado</th>
                                <th class="text-nowrap">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(!empty($rows)): ?>
                            <?php foreach($rows as $r): ?>
                                <tr>
                                    <td style="min-width:260px; text-align:left;">
                                      <div class="detalle-badges">
                                        <?php foreach (($r['detalle_badges'] ?? []) as $chunk): ?>
                                          <span class="badge bg-secondary"><?= htmlspecialchars($chunk) ?></span>
                                        <?php endforeach; ?>
                                      </div>
                                    </td>
                                    <td><?= htmlspecialchars($r['responsable'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($r['aula'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($r['fecha'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($r['hora_inicio'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($r['hora_fin'] ?: '-') ?></td>
                                    <td><?= !empty($r['fecha_devolucion']) ? htmlspecialchars($r['fecha_devolucion']) : '---' ?></td>
                                    <td>
                                        <?php if(($r['estado'] ?? '')==='Prestado'): ?>
                                            <span class="badge bg-warning text-dark status-badge">⏳ Prestado</span>
                                        <?php else: ?>
                                            <span class="badge bg-success status-badge">✅ Devuelto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(($r['estado'] ?? '')==='Prestado'): ?>
                                            <?php if(($r['tipo_reg'] ?? '')==='unitario'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalDevolver" data-id="<?= (int)$r['id'] ?>" data-equipo="<?= htmlspecialchars($r['detalle']) ?>">Confirmar</button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalDevolverPack" data-idpack="<?= (int)($r['id_pack'] ?? 0) ?>" data-detalle="<?= htmlspecialchars(implode(' | ', $r['detalle_badges'] ?? [])) ?>">Confirmar</button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-success fw-bold">✔</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center text-muted">No hay préstamos registrados.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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

    <!-- Modal Devolver unitario -->
    <div class="modal fade" id="modalDevolver" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="post" action="?view=devolucion">
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
              <div class="mb-2">
                <label class="form-label">Estado del equipo</label>
                <select class="form-select" id="estado-entrega" name="estado_entrega">
                  <option value="ok" selected>En buen estado / Todo correcto</option>
                  <option value="mal">Mal estado</option>
                </select>
              </div>
              <label class="form-label">Comentario (opcional)</label>
              <textarea class="form-control" id="comentario-entrega" name="comentario" rows="3" placeholder="Describe el problema..." disabled></textarea>
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
    // submit animation
    const form = modal.querySelector('form');
    form?.addEventListener('submit', function(){
      modal.classList.add('submitting');
      const btn = form.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Guardando...';
      }
      const inputs = form.querySelectorAll('input, select, textarea, button');
      inputs.forEach(el=>{ if (el!==btn) el.disabled = true; });
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
    // submit animation pack
    const formP = modalPack.querySelector('form');
    formP?.addEventListener('submit', function(){
      modalPack.classList.add('submitting');
      const btn = formP.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Guardando...';
      }
      const inputs = formP.querySelectorAll('input, select, textarea, button');
      inputs.forEach(el=>{ if (el!==btn) el.disabled = true; });
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
