<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if ($_SESSION['tipo'] !== 'Administrador') { header('Location: Dashboard.php'); exit; }

require_once '../controllers/EquipoController.php';
require_once '../models/TipoEquipoModel.php';
$controller = new EquipoController();
$data = $controller->handleRequest();
$equipos = $data['equipos'];
$mensaje = $data['mensaje'];
$mensaje_tipo = $data['mensaje_tipo'];
$tipoModel = new TipoEquipoModel();
$tipos = $tipoModel->listar();
?>
<?php if (!defined('EMBEDDED_VIEW')): ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>💻 Equipos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../Public/css/brand.css">
    <link rel="stylesheet" href="../../Public/css/admin_mobile.css?v=<?= time() ?>">
</head>
<body class="bg-light">
<main class="container py-4">
<?php endif; ?>
    <h1 class="mb-4 text-brand">💻 Gestión de Equipos</h1>

    <?php if (!empty($mensaje)): ?>
    <div class="alert alert-<?= $mensaje_tipo === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show shadow-sm" role="alert">
        <?= htmlspecialchars($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
    <?php endif; ?>

    <!-- Formulario registro -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-brand text-white">Registrar Equipo</div>
        <div class="card-body">
            <?php if (empty($tipos)): ?>
            <div class="alert alert-warning d-flex justify-content-between align-items-center">
              <div>Antes de registrar equipos, <strong>debes crear al menos un Tipo de Equipo</strong>.</div>
              <a href="Admin.php?view=tipos_equipo" class="btn btn-sm btn-brand">➕ Crear Tipo</a>
            </div>
            <?php endif; ?>
            <form method="post" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nombre del Equipo</label>
                    <input type="text" name="nombre_equipo" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipo de Equipo</label>
                    <select name="tipo_equipo" class="form-select" required <?= empty($tipos) ? 'disabled' : '' ?>>
                        <option value="" disabled selected>-- Selecciona un tipo --</option>
                        <?php foreach ($tipos as $t): ?>
                          <option value="<?= htmlspecialchars($t['nombre']) ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" class="form-control" min="0" required>
                </div>
                <div class="col-12">
                    <button type="submit" name="registrar_equipo" class="btn btn-brand" <?= empty($tipos) ? 'disabled' : '' ?>>Registrar Equipo</button>
                    <?php if (empty($tipos)): ?>
                      <a href="Admin.php?view=tipos_equipo" class="btn btn-outline-brand ms-2">Ir a Tipos de Equipo</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla equipos -->
    <div class="card shadow-sm">
        <div class="card-header bg-brand text-white">Equipos Registrados</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center">
                    <thead class="table-primary">
                        <tr>
                            <th>N°</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Stock</th>
                            <th>Activo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1; foreach ($equipos as $eq): ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= htmlspecialchars($eq['nombre_equipo']) ?></td>
                            <td><?= htmlspecialchars($eq['tipo_equipo']) ?></td>
                            <td><?= htmlspecialchars($eq['stock']) ?></td>
                            <td><?= $eq['activo'] ? "✅" : "❌" ?></td>
                            <td>
                                <?php if ($eq['activo']): ?>
                                    <form method="post" class="d-inline form-baja">
                                        <input type="hidden" name="id_equipo" value="<?= $eq['id_equipo'] ?>">
                                        <button type="submit" name="dar_baja_equipo" class="btn btn-sm btn-outline-warning">⬇ Dar de Baja</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" class="d-inline form-restaurar">
                                        <input type="hidden" name="id_equipo" value="<?= $eq['id_equipo'] ?>">
                                        <button type="submit" name="restaurar_equipo" class="btn btn-sm btn-outline-success">♻ Restaurar</button>
                                    </form>
                                    <form method="post" class="d-inline form-eliminar">
                                        <input type="hidden" name="id_equipo" value="<?= $eq['id_equipo'] ?>">
                                        <button type="submit" name="eliminar_equipo_def" class="btn btn-sm btn-outline-danger">🗑 Eliminar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php $i++; endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="Admin.php" class="btn btn-outline-brand hide-xs">🔙 Volver al Panel</a>
    </div>
<?php if (!defined('EMBEDDED_VIEW')): ?>
</main>

<!-- Bootstrap y SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../Public/js/equipo.js"></script>

<?php if (!empty($mensaje)): ?>
<script>
Swal.fire({
    icon: '<?= $mensaje_tipo === "success" ? "success" : "error" ?>',
    title: '<?= $mensaje_tipo === "success" ? "Éxito" : "Error" ?>',
    text: '<?= $mensaje ?>',
    confirmButtonColor: '#3085d6'
});
</script>
<?php endif; ?>
</body>
</html>
<?php endif; ?>

<?php if (defined('EMBEDDED_VIEW')): ?>
  <?php if (!empty($mensaje)): ?>
    <script>
      // Si SweetAlert está disponible via Admin.php, lo usamos para notificar también en vista embebida
      if (window.Swal) {
        Swal.fire({
          icon: '<?= $mensaje_tipo === "success" ? "success" : "error" ?>',
          title: '<?= $mensaje_tipo === "success" ? "Éxito" : "Error" ?>',
          text: '<?= $mensaje ?>',
          confirmButtonColor: '#3085d6'
        });
      }
    </script>
  <?php endif; ?>
<?php endif; ?>
