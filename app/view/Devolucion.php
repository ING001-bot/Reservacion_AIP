<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/conexion.php';
require_once '../controllers/PrestamoController.php';

$controller = new PrestamoController($conexion);

if (!isset($_SESSION['usuario']) || $_SESSION['tipo']!=='Encargado') {
    die("Acceso denegado");
}

// Definir usuario para cabecera
$usuario = $_SESSION['usuario'] ?? 'Encargado';

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


$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : 'Prestado';
$desde = isset($_GET['desde']) ? trim($_GET['desde']) : '';
$hasta = isset($_GET['hasta']) ? trim($_GET['hasta']) : '';

// Obtener préstamos filtrados para mostrar en la tabla
$prestamos = $controller->obtenerPrestamosFiltrados($estado, $desde, $hasta, $q);

// Si no hay resultados con filtros y no se especificó estado, intentar obtener todas las devoluciones
if (empty($prestamos) && empty($estado) && empty($q)) {
    $prestamos = $controller->obtenerTodasLasDevoluciones();
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Devolución individual
    if (isset($_POST['devolver_id'])) {
      $id = intval($_POST['devolver_id']);
      $coment = isset($_POST['comentario']) ? trim($_POST['comentario']) : null;
      $success = false;
      // Pre-chequeo idempotente para evitar notificación en recarga
      $pre = $controller->obtenerPrestamoPorId($id);
      if ($pre && ($pre['estado'] ?? '') === 'Devuelto') {
        $mensaje = '✔ Este préstamo ya estaba confirmado como devuelto.';
        $success = true;
      } elseif ($controller->devolverEquipo($id, $coment, false)) { // false = no enviar notificación automática
        // Crear notificación (1 para Admin, 1 para Encargado)
        try {
          $prestamo = $controller->obtenerPrestamoPorId($id);
          if ($prestamo) {
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
            // Notificar profesor (si existe)
            if (!empty($prestamo['id_usuario'])) {
              $notifService->crearNotificacionDevolucionPack(
                $db,
                (int)$prestamo['id_usuario'],
                'Profesor',
                $datosDev
              );
            }
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
        $success = true;
      } else {
        $mensaje = '❌ No se pudo registrar la devolución.';
      }
      // Redirigir para evitar reenvío del formulario (PRG)
      // Importante: mover header antes de cualquier salida
      if (!headers_sent()) {
        $redirUrl = $_SERVER['PHP_SELF'] . '?success=' . ($success ? '1' : '0') . '&msg=' . urlencode($mensaje);
        header('Location: ' . $redirUrl);
        exit;
      }
    }
    
    // Devolución grupal (única lógica)
    if (isset($_POST['devolver_grupo_ids'])) {
        $idsJson = $_POST['devolver_grupo_ids'] ?? '[]';
        $ids = json_decode($idsJson, true);
        $coment = isset($_POST['comentario_grupo']) ? trim($_POST['comentario_grupo']) : null;
        $exitosos = 0;
        $equiposDevueltos = [];
        $omitidos = 0;
        $idUsuarioProfesor = null;
        if (is_array($ids) && !empty($ids)) {
          // Filtrar préstamos ya devueltos para no reprocesarlos
          $idsProcesables = [];
          foreach ($ids as $cand) {
            $pre = $controller->obtenerPrestamoPorId((int)$cand);
            if ($pre && ($pre['estado'] ?? '') === 'Devuelto') { $omitidos++; continue; }
            $idsProcesables[] = (int)$cand;
          }
          foreach ($idsProcesables as $idg) {
            if ($controller->devolverEquipo(intval($idg), $coment)) {
              $exitosos++;
              try {
                $prestamoG = $controller->obtenerPrestamoPorId(intval($idg));
                if ($prestamoG && $prestamoG['nombre_equipo']) {
                  $equiposDevueltos[] = ['nombre' => $prestamoG['nombre_equipo']];
                  if (!$idUsuarioProfesor && !empty($prestamoG['id_usuario'])) {
                    $idUsuarioProfesor = (int)$prestamoG['id_usuario'];
                  }
                }
              } catch (\Exception $e) { error_log('Error datos prestamo grupal: '.$e->getMessage()); }
            }
          }
          if ($exitosos > 0 && !empty($equiposDevueltos)) {
            try {
              require_once __DIR__ . '/../lib/NotificationService.php';
              $notifService = new \App\Lib\NotificationService();
              $db = $controller->getDb();
              $datosDev = [
                'id_prestamo' => $idsProcesables[0] ?? 0,
                'equipos' => $equiposDevueltos,
                'encargado' => $_SESSION['usuario'] ?? 'Encargado',
                'hora_confirmacion' => date('H:i'),
                'comentario' => $coment
              ];
              // Notificar profesor (si se detectó)
              if (!empty($idUsuarioProfesor)) {
                $notifService->crearNotificacionDevolucionPack($db, $idUsuarioProfesor, 'Profesor', $datosDev);
              }
              // Notificar administradores
              $admins = $controller->listarUsuariosPorRol(['Administrador']);
              foreach ($admins as $admin) {
                $notifService->crearNotificacionDevolucionPack($db, (int)$admin['id_usuario'], 'Administrador', $datosDev);
              }
            } catch (\Exception $e) { error_log('Error notificación grupal: '.$e->getMessage()); }
          }
          if ($exitosos > 0 && $omitidos === 0) {
            $mensaje = '✅ Devolución de ' . $exitosos . ' equipo(s) registrada correctamente.';
          } else if ($exitosos > 0 && $omitidos > 0) {
            $mensaje = '✅ ' . $exitosos . ' confirmado(s). ✔ ' . $omitidos . ' ya estaban confirmados.';
          } else if ($exitosos === 0 && $omitidos > 0) {
            $mensaje = '✔ Estos préstamo(s) ya estaban confirmados como devueltos.';
          } else {
            $mensaje = '❌ No se pudo registrar ninguna devolución.';
          }
          // Redirección PRG para evitar reenvío
          if (!headers_sent()) {
            $redirUrl = $_SERVER['PHP_SELF'] . '?success=' . ($exitosos > 0 ? '1' : '0') . '&msg=' . urlencode($mensaje);
            header('Location: ' . $redirUrl);
            exit;
          }
        }
    }
  }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrar Devolución</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../../Public/css/brand.css">
  <script>
    // Limpiar campos de fecha cuando se carga sin parámetros de filtro
    document.addEventListener('DOMContentLoaded', function() {
      // Si no hay parámetros en la URL (carga inicial), limpiar campos de fecha
      if (window.location.search === '' || window.location.search === '?success=0' || window.location.search === '?success=1') {
        const inputDesde = document.querySelector('input[name="desde"]');
        const inputHasta = document.querySelector('input[name="hasta"]');
        if (inputDesde) inputDesde.value = '';
        if (inputHasta) inputHasta.value = '';
      }
    });
  </script>
</head>
<body>
<?php require __DIR__ . '/partials/navbar.php'; ?>
<style>
  .toast-container{ position: fixed; right: 360px; bottom: 24px; z-index:1080; }
  @media (max-width: 1200px){ .toast-container{ right: 24px; bottom: 24px; } }
  /* Posicionar ambos modales más abajo y centrados */
  #modalDevolverGrupo .modal-dialog,
  .custom-modal-pos{ display:flex; align-items:flex-start; justify-content:center; min-height:100vh; margin-top:180px !important; }
</style>

<main class="container py-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h1 class="text-brand m-0">📦 Registrar Devolución</h1>
        <div class="text-muted">Encargado: <strong><?= $usuario ?></strong></div>
    </div>

    <form id="filtrosForm" class="card p-3 shadow-sm mb-3" method="get" action="Devolucion.php">
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
                    <button id="btnBuscar" class="btn btn-sm btn-brand rounded-pill px-3 w-100 w-md-auto" type="button">
                        <i class="fas fa-search me-1"></i>Buscar
                    </button>
                    <button id="btnLimpiar" class="btn btn-sm btn-outline-secondary rounded-pill px-3 w-100 w-md-auto" type="button" title="Limpiar filtros">
                        <i class="fas fa-broom me-1"></i>Limpiar
                    </button>
                </div>
            </div>
            
            <?php if (!empty($q) || (isset($_GET['estado']) && !empty($_GET['estado'])) || !empty($desde) || !empty($hasta)): ?>
            <div class="mt-2">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Filtros activos:
                    <?php if (isset($_GET['estado']) && !empty($_GET['estado'])): ?><span class="badge bg-primary"><?= htmlspecialchars($_GET['estado']) ?></span><?php endif; ?>
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
              <div class="container-fluid">
                <div class="row">
                  <div class="col-12 d-flex justify-content-center mt-3">
                    <button type="submit" class="btn btn-success px-4 py-2">Marcar como Devuelto</button>
                  </div>
                  <div class="col-12 d-flex justify-content-center mt-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    

    <!-- Modal Devolver unitario -->
    <div class="modal fade" id="modalDevolver" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered custom-modal-pos">
        <style>
          /* Solo afecta el modal de devolución unitario */
          .custom-modal-pos {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            min-height: 100vh;
            margin-top: 180px !important;
          }
        </style>
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
  <?php 
    $msg = $mensaje;
    if (isset($_GET['msg'])) $msg = $_GET['msg'];
    if (!empty($msg)): ?>
    (function(){
      const toastEl = document.getElementById('toastFeedback');
      const body = document.getElementById('toastFeedbackBody');
      if (body) body.textContent = <?= json_encode($msg, JSON_UNESCAPED_UNICODE) ?>;
      const t = new bootstrap.Toast(toastEl, { delay: 3000 });
      t.show();
    })();
  <?php endif; ?>
  
  // Debug del formulario de búsqueda
  const searchForm = document.getElementById('filtrosForm');
  const btnBuscar = document.getElementById('btnBuscar');
  const btnLimpiar = document.getElementById('btnLimpiar');
  if (btnBuscar && searchForm) {
    btnBuscar.addEventListener('click', function(){
      const estado = encodeURIComponent(searchForm.querySelector('[name="estado"]').value || '');
      const desde  = encodeURIComponent(searchForm.querySelector('[name="desde"]').value || '');
      const hasta  = encodeURIComponent(searchForm.querySelector('[name="hasta"]').value || '');
      const q      = encodeURIComponent(searchForm.querySelector('[name="q"]').value || '');
      const params = new URLSearchParams();
      if (estado) params.set('estado', estado);
      if (desde)  params.set('desde', desde);
      if (hasta)  params.set('hasta', hasta);
      if (q)      params.set('q', q);
      const url = 'Devolucion.php' + (params.toString() ? ('?' + params.toString()) : '');
      window.location.href = url;
    });
  }
  if (btnLimpiar) {
    btnLimpiar.addEventListener('click', function(){
      window.location.href = 'Devolucion.php';
    });
  }
});
// Bootstrap Bundle (modals)
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../Public/js/theme.js"></script>
</body>
</html>
