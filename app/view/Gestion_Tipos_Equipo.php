<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (($_SESSION['tipo'] ?? '') !== 'Administrador') { header('Location: Dashboard.php'); exit; }

require_once __DIR__ . '/../controllers/TipoEquipoController.php';
$ctrl = new TipoEquipoController();
$data = $ctrl->handleRequest();
$tipos = $data['tipos'];
$mensaje = $data['mensaje'];
$mensaje_tipo = $data['mensaje_tipo'];
?>
<?php if (!defined('EMBEDDED_VIEW')): ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>⚙ Tipos de Equipo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../Public/css/brand.css">
  <link rel="stylesheet" href="../../Public/css/admin_mobile.css?v=<?= time() ?>">
</head>
<body class="bg-light">
<main class="container py-4">
<?php endif; ?>

<h1 class="mb-4 text-brand">⚙ Gestión de Tipos de Equipo</h1>

<?php if (!empty($mensaje)): ?>
  <div class="alert alert-<?= htmlspecialchars($mensaje_tipo) ?> alert-dismissible fade show shadow-sm" role="alert">
    <?= htmlspecialchars($mensaje) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
  </div>
<?php endif; ?>

<div class="card card-brand shadow-sm mb-4">
  <div class="card-header bg-brand text-white">Agregar Tipo</div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Nombre del Tipo</label>
        <input type="text" name="nombre_tipo" class="form-control" placeholder="Ej: Laptop, Proyector, Micrófono" required>
      </div>
      <div class="col-12">
        <button type="submit" name="crear_tipo" class="btn btn-brand">Agregar Tipo</button>
      </div>
    </form>
  </div>
</div>

<div class="card card-brand shadow-sm">
  <div class="card-header bg-brand text-white">Tipos existentes</div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-primary">
          <tr>
            <th>#</th>
            <th>Nombre</th>
            <th class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($tipos)): $i=1; foreach($tipos as $t): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= htmlspecialchars($t['nombre']) ?></td>
              <td class="text-center">
                <form method="post" class="d-inline form-eliminar-tipo">
                  <input type="hidden" name="id_tipo" value="<?= (int)$t['id_tipo'] ?>">
                  <button type="submit" name="eliminar_tipo" class="btn btn-sm btn-outline-danger">🗑 Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr>
              <td colspan="3" class="text-center text-muted">Aún no hay tipos registrados.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="mt-4">
  <a href="Admin.php" class="btn btn-outline-brand hide-xs">🔙 Volver al Panel</a>
</div>

<?php if (!defined('EMBEDDED_VIEW')): ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../../Public/js/tipos_equipo.js?v=<?= time() ?>"></script>
</main>
</body>
</html>
<?php endif; ?>
