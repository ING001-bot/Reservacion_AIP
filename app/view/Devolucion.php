<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/conexion.php';
require_once '../controllers/PrestamoController.php';

$controller = new PrestamoController($conexion);

if (!isset($_SESSION['usuario']) || $_SESSION['tipo']!=='Encargado') {
    die("Acceso denegado");
}

// Marcar notificación como leída si viene desde una notificación
if (isset($_GET['notif_read']) && is_numeric($_GET['notif_read'])) {
    try {
        $stmt = $conexion->prepare("UPDATE notificaciones SET leida = 1 WHERE id_notificacion = ? AND id_usuario = ?");
        $stmt->execute([(int)$_GET['notif_read'], (int)$_SESSION['id_usuario']]);
    } catch (\Throwable $e) {
        // Ignorar errores silenciosamente
    }
}

// Prevenir caché del navegador (solo si no es vista embebida)
if (!defined('EMBEDDED_VIEW')) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Devolución individual
    if (isset($_POST['devolver_id'])) {
        $id = intval($_POST['devolver_id']);
        $coment = isset($_POST['comentario']) ? trim($_POST['comentario']) : null;
        
        if ($controller->devolverEquipo($id, $coment, false)) { // false = no enviar notificación automática
            // Crear notificación agrupada
            try {
                $prestamo = $controller->obtenerPrestamoPorId($id);
                if ($prestamo && $prestamo['id_usuario']) {
                    require_once __DIR__ . '/../lib/NotificationService.php';
                    $notifService = new \App\Lib\NotificationService();
                    $db = $controller->getDb();
                    
                    $equiposDevueltos = [['nombre' => $prestamo['nombre_equipo'] ?? 'Equipo']];
                    $datosDev = [
                        'id_prestamo' => $id,
                        'equipos' => $equiposDevueltos,
                        'encargado' => $_SESSION['usuario'] ?? 'Encargado',
                        'hora_confirmacion' => date('H:i'),
                        'comentario' => $coment
                    ];
                    
                    // Notificar al profesor
                    $notifService->crearNotificacionDevolucionPack(
                        $db,
                        (int)$prestamo['id_usuario'],
                        'Profesor',
                        $datosDev
                    );
                    
                    // Notificar administradores
                    $admins = $controller->listarUsuariosPorRol(['Administrador']);
                    foreach ($admins as $admin) {
                        $notifService->crearNotificacionDevolucionPack(
                            $db,
                            (int)$admin['id_usuario'],
                            'Administrador',
                            $datosDev
                        );
                    }
                }
            } catch (\Exception $e) {
                error_log("Error al crear notificación de devolución: " . $e->getMessage());
            }
            
            $mensaje = '✅ Devolución registrada correctamente.';
        } else {
            $mensaje = '❌ No se pudo registrar la devolución.';
        }
    }
    
    // Devolución grupal
    if (isset($_POST['devolver_grupo_ids'])) {
        $idsJson = $_POST['devolver_grupo_ids'] ?? '[]';
        $ids = json_decode($idsJson, true);
        $coment = isset($_POST['comentario_grupo']) ? trim($_POST['comentario_grupo']) : null;
        
        if (is_array($ids) && !empty($ids)) {
            $exitosos = 0;
            $equiposDevueltos = [];
            $idUsuarioPrestamo = null;
            
            foreach ($ids as $id) {
                if ($controller->devolverEquipo(intval($id), $coment)) {
                    $exitosos++;
                    
                    // Obtener datos del equipo para la notificación
                    try {
                        $prestamo = $controller->obtenerPrestamoPorId(intval($id));
                        if ($prestamo && $prestamo['nombre_equipo']) {
                            $equiposDevueltos[] = ['nombre' => $prestamo['nombre_equipo']];
                            if (!$idUsuarioPrestamo && $prestamo['id_usuario']) {
                                $idUsuarioPrestamo = $prestamo['id_usuario'];
                            }
                        }
                    } catch (\Exception $e) {
                        error_log("Error al obtener datos del préstamo: " . $e->getMessage());
                    }
                }
            }
            
            // Crear notificaciones agrupadas al finalizar todas las devoluciones
            if ($exitosos > 0 && !empty($equiposDevueltos)) {
                try {
                    require_once __DIR__ . '/../lib/NotificationService.php';
                    $notifService = new \App\Lib\NotificationService();
                    $db = $controller->getDb();
                    
                    $datosDev = [
                        'id_prestamo' => $ids[0], // ID del primer préstamo como referencia
                        'equipos' => $equiposDevueltos,
                        'encargado' => $_SESSION['usuario'] ?? 'Encargado',
                        'hora_confirmacion' => date('H:i'),
                        'comentario' => $coment
                    ];
                    
                    // Notificar al profesor
                    if ($idUsuarioPrestamo) {
                        $notifService->crearNotificacionDevolucionPack(
                            $db,
                            $idUsuarioPrestamo,
                            'Profesor',
                            $datosDev
                        );
                    }
                    
                    // Notificar a todos los administradores
                    $admins = $controller->listarUsuariosPorRol(['Administrador']);
                    foreach ($admins as $admin) {
                        $notifService->crearNotificacionDevolucionPack(
                            $db,
                            (int)$admin['id_usuario'],
                            'Administrador',
                            $datosDev
                        );
                    }
                } catch (\Exception $e) {
                    error_log("Error al crear notificaciones de devolución agrupada: " . $e->getMessage());
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
    // Packs eliminados del sistema
}

// Filtros simples - Por defecto mostrar todos los estados (así, al confirmar, la fila sigue visible)
$estado = isset($_GET['estado']) && $_GET['estado'] !== '' ? trim($_GET['estado']) : '';
$desde = isset($_GET['desde']) && $_GET['desde'] !== '' ? trim($_GET['desde']) : '';
$hasta = isset($_GET['hasta']) && $_GET['hasta'] !== '' ? trim($_GET['hasta']) : '';
$q = isset($_GET['q']) && $_GET['q'] !== '' ? trim($_GET['q']) : '';

// Si no hay filtros de fecha, limitar a últimos 30 días para mejor rendimiento
if ($desde === '' && $hasta === '') {
    $desde = date('Y-m-d', strtotime('-30 days'));
}

// Siempre usar filtros para optimizar la consulta
$prestamos = $controller->obtenerPrestamosFiltrados(
    $estado !== '' ? $estado : null, 
    $desde !== '' ? $desde : null, 
    $hasta !== '' ? $hasta : null, 
    $q !== '' ? $q : null
);

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
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h1 class="text-brand m-0">📦 Registrar Devolución</h1>
        <div class="text-muted">Encargado: <strong><?= $usuario ?></strong></div>
    </div>

    <form class="card p-3 shadow-sm mb-3" method="get" action="Devolucion.php">
            <div class="row g-2 align-items-end filters">
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-filter me-1"></i>Estado
                    </label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="Prestado" <?= $estado==='Prestado'?'selected':'' ?>>Prestado</option>
                        <option value="Devuelto" <?= $estado==='Devuelto'?'selected':'' ?>>Devuelto</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-calendar-day me-1"></i>Desde
                    </label>
                    <input type="date" class="form-control form-control-sm" name="desde" value="<?= htmlspecialchars($desde) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-calendar-day me-1"></i>Hasta
                    </label>
                    <input type="date" class="form-control form-control-sm" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-search me-1"></i>Buscar
                    </label>
                    <input type="text" class="form-control form-control-sm" name="q" placeholder="🔍 Buscar por equipo, profesor o aula..." value="<?= htmlspecialchars($q) ?>" autocomplete="off">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2 justify-content-end align-items-center">
                    <button class="btn btn-sm btn-brand rounded-pill px-3 w-100 w-md-auto" type="submit">
                        <i class="fas fa-search me-1"></i>Buscar
                    </button>
                    <a class="btn btn-sm btn-outline-secondary rounded-pill px-3 w-100 w-md-auto" href="Devolucion.php" title="Limpiar filtros">
                        <i class="fas fa-broom me-1"></i>Limpiar
                    </a>
                </div>
            </div>
            
            <?php if (!empty($q) || !empty($estado) || (!empty($desde) && $desde !== date('Y-m-d', strtotime('-30 days'))) || !empty($hasta)): ?>
            <div class="mt-2">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Filtros activos:
                    <?php if ($estado): ?><span class="badge bg-primary"><?= htmlspecialchars($estado) ?></span><?php endif; ?>
                    <?php if ($desde): ?><span class="badge bg-secondary">Desde: <?= htmlspecialchars($desde) ?></span><?php endif; ?>
                    <?php if ($hasta): ?><span class="badge bg-secondary">Hasta: <?= htmlspecialchars($hasta) ?></span><?php endif; ?>
                    <?php if ($q): ?><span class="badge bg-info text-dark">Búsqueda: "<?= htmlspecialchars($q) ?>"</span><?php endif; ?>
                </small>
            </div>
            <?php endif; ?>
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
          
          // Packs eliminados: no agregar filas de packs
        ?>

        <?php if (empty($rows)): ?>
        <div class="alert alert-info d-flex align-items-center shadow-sm" role="alert">
            <i class="fas fa-info-circle fa-2x me-3"></i>
            <div>
                <strong>No hay resultados</strong>
                <?php if (!empty($q) || !empty($estado) || !empty($desde) || !empty($hasta)): ?>
                <p class="mb-0">No se encontraron préstamos con los filtros aplicados. Intenta con otros criterios de búsqueda.</p>
                <?php else: ?>
                <p class="mb-0">No hay préstamos registrados en los últimos 30 días.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="text-brand mb-0">
                <i class="fas fa-clipboard-list me-2"></i>Préstamos Registrados
            </h2>
            <small class="text-muted">
                <i class="fas fa-list-ol me-1"></i><?= count($rows) ?> registro(s) encontrado(s)
            </small>
        </div>
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
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-success">✔ Devuelto</span>
                                        <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
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
  // Packs eliminados: sin modal ni lógica de packs

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
  
  // Debug del formulario de búsqueda
  const searchForm = document.querySelector('form[method="get"]');
  if (searchForm) {
    searchForm.addEventListener('submit', function(e) {
      // Permitir submit normal
      console.log('Formulario enviado con:', {
        estado: this.querySelector('[name="estado"]')?.value,
        desde: this.querySelector('[name="desde"]')?.value,
        hasta: this.querySelector('[name="hasta"]')?.value,
        q: this.querySelector('[name="q"]')?.value
      });
    });
  }
});
// Bootstrap Bundle (modals)
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../Public/js/theme.js"></script>
</body>
</html>
