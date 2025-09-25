<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config/conexion.php';
require '../controllers/PrestamoController.php';

$controller = new PrestamoController($conexion);

if (!isset($_SESSION['usuario']) || $_SESSION['tipo']!=='Encargado') {
    die("Acceso denegado");
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['devolver_id'])) {
    $id = intval($_POST['devolver_id']);
    $coment = isset($_POST['comentario']) ? trim($_POST['comentario']) : null;
    if ($controller->devolverEquipo($id, $coment)) {
        $mensaje = '✅ Devolución registrada correctamente.';
    } else {
        $mensaje = '❌ No se pudo registrar la devolución.';
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
</head>
<body>
    <main class="container py-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <h1 class="text-brand m-0">📦 Registrar Devolución</h1>
            <div class="text-muted">Encargado: <strong><?= $usuario ?></strong></div>
        </div>

        <form class="card p-3 shadow-sm mb-3" method="get" action="">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="Prestado" <?= $estado==='Prestado'?'selected':'' ?>>Prestado</option>
                        <option value="Devuelto" <?= $estado==='Devuelto'?'selected':'' ?>>Devuelto</option>
                    </select>
                </div>
                <div class="col-6 col-sm-3">
                    <label class="form-label">Desde</label>
                    <input type="date" class="form-control" name="desde" value="<?= htmlspecialchars($desde) ?>">
                </div>
                <div class="col-6 col-sm-3">
                    <label class="form-label">Hasta</label>
                    <input type="date" class="form-control" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
                </div>
                <div class="col-12 col-sm-3">
                    <label class="form-label">Buscar</label>
                    <input type="text" class="form-control" name="q" placeholder="Equipo, profesor o aula" value="<?= htmlspecialchars($q) ?>">
                </div>
                <div class="col-12 d-flex gap-2 justify-content-end">
                    <button class="btn btn-brand" type="submit">Aplicar filtros</button>
                    <a class="btn btn-outline-secondary" href="?view=devolucion">Limpiar</a>
                </div>
            </div>
        </form>

        <?php if($mensaje): ?>
            <div class="alert alert-info text-center"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="card shadow-lg mb-4">
            <div class="card-header text-white" style="background: linear-gradient(90deg,#25D366,#3a2edb);">
                <h5 class="mb-0 text-center">Todos los Préstamos</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    
                    <table class="table table-hover align-middle text-center">
                        <thead class="table-primary">        
                            <tr>
                                <th>Equipo</th>
                                <th>Responsable</th>
                                <th>Aula</th>
                                <th>Tipo Aula</th>
                                <th>Fecha Préstamo</th>
                                <th>Hora Inicio</th>
                                <th>Hora Fin</th>
                                <th>Fecha Devolución</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(!empty($prestamos)): ?>
                            <?php foreach($prestamos as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['nombre_equipo']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre_aula']) ?></td>
                                    <td><?= htmlspecialchars($row['tipo']) ?></td>
                                    <td><?= htmlspecialchars($row['fecha_prestamo']) ?></td>
                                    <td><?= htmlspecialchars($row['hora_inicio']) ?></td>
                                    <td><?= htmlspecialchars($row['hora_fin']) ?></td>
                                    <td><?= $row['fecha_devolucion'] ? htmlspecialchars($row['fecha_devolucion']) : '---' ?></td>
                                    <td>
                                        <?php if($row['estado']==='Prestado'): ?>
                                            <span class="badge bg-warning text-dark">Prestado</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Devuelto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($row['estado']==='Prestado'): ?>
                                            <button class="btn btn-sm btn-outline-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalDevolver" 
                                                    data-id="<?= (int)$row['id_prestamo'] ?>"
                                                    data-equipo="<?= htmlspecialchars($row['nombre_equipo']) ?>">
                                                Devolver
                                            </button>
                                        <?php else: ?>
                                            <span class="text-success fw-bold">✔</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">No hay préstamos registrados.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="dashboard.php" class="btn btn-outline-primary">⬅ Volver al Dashboard</a>
        </div>
    </main>

    <!-- Modal Devolver -->
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
              <label class="form-label">Comentario (opcional)</label>
              <textarea class="form-control" name="comentario" rows="3" placeholder="Observaciones de la devolución..."></textarea>
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
  if (!modal) return;
  modal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const id = button?.getAttribute('data-id') || '';
    const equipo = button?.getAttribute('data-equipo') || '';
    modal.querySelector('#devolver-id').value = id;
    modal.querySelector('#devolver-equipo').textContent = equipo;
  });
});
</script>
</body>
</html>
