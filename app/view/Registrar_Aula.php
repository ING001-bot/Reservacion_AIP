<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config/conexion.php';
require '../controllers/AulaController.php';

$rol = $_SESSION['tipo'] ?? null;
if ($rol !== 'Administrador') { header('Location: Dashboard.php'); exit; }

// Prevenir caché del navegador (solo si no es vista embebida)
if (!defined('EMBEDDED_VIEW')) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
}

$controller = new AulaController($conexion);

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['registrar_aula'])) {
        $controller->registrarAula($_POST);
    } elseif (isset($_POST['editar_aula'])) {
        $controller->editarAula($_POST);
    } elseif (isset($_POST['eliminar_aula'])) {
        $controller->eliminarAula(intval($_POST['id_aula'] ?? 0));
    }
}

// Obtener aulas
$aulas = $controller->listarAulas();
$id_editar = $_GET['editar'] ?? null;
?>

<?php if (!defined('EMBEDDED_VIEW')): ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🏫 Gestión de Aulas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../Public/css/brand.css">
<link rel="stylesheet" href="../../Public/css/admin_mobile.css?v=<?= time() ?>">
</head>
<body class="bg-light">
<main class="container py-4">
<?php endif; ?>

<h1 class="mb-4 text-brand">🏫 Gestión de Aulas</h1>

<?php if($controller->mensaje): ?>
<div class="alert alert-<?= $controller->mensaje_tipo === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show shadow-sm" role="alert">
    <?= htmlspecialchars($controller->mensaje) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Formulario Registro Aula -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-brand text-white">Registrar Aula</div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre del Aula</label>
                <input type="text" name="nombre_aula" class="form-control" placeholder="" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Capacidad</label>
                <input type="number" name="capacidad" class="form-control" min="1" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select" required>
                    <option value="AIP">AIP</option>
                    <option value="Regular">Regular</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" name="registrar_aula" class="btn btn-brand">Registrar Aula</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Aulas -->
<div class="card shadow-sm">
    <div class="card-header bg-brand text-white">Aulas Registradas</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        <th>Nombre</th>
                        <th>Capacidad</th>
                        <th>Tipo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($aulas)): ?>
                        <?php foreach($aulas as $aula): ?>
                            <tr>
                                <td><?= htmlspecialchars($aula['nombre_aula']) ?></td>
                                <td><?= htmlspecialchars($aula['capacidad']) ?></td>
                                <td><?= htmlspecialchars($aula['tipo']) ?></td>
                                <td class="text-center text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-editar-aula"
                                        data-id="<?= $aula['id_aula'] ?>"
                                        data-nombre="<?= htmlspecialchars($aula['nombre_aula']) ?>"
                                        data-capacidad="<?= $aula['capacidad'] ?>"
                                        data-tipo="<?= htmlspecialchars($aula['tipo']) ?>">
                                        ✏️ Editar
                                    </button>
                                    <form method="post" class="d-inline form-eliminar-aula">
                                        <input type="hidden" name="id_aula" value="<?= $aula['id_aula'] ?>">
                                        <button type="submit" name="eliminar_aula" class="btn btn-sm btn-outline-danger">🗑️ Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No hay aulas registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Editar Aula -->
<div class="modal fade" id="editarAulaModal" tabindex="-1" aria-labelledby="editarAulaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-brand text-white">
                <h5 class="modal-title" id="editarAulaModalLabel">✏️ Editar Aula</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formEditarAula" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id_aula" id="edit_id_aula">
                    <div class="mb-3">
                        <label for="edit_nombre_aula" class="form-label">Nombre del Aula</label>
                        <input type="text" class="form-control" id="edit_nombre_aula" name="nombre_aula" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_capacidad" class="form-label">Capacidad</label>
                        <input type="number" class="form-control" id="edit_capacidad" name="capacidad" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_tipo" class="form-label">Tipo</label>
                        <select class="form-select" id="edit_tipo" name="tipo" required>
                            <option value="AIP">AIP</option>
                            <option value="Regular">Regular</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="editar_aula" class="btn btn-brand">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<a href="Admin.php" class="btn btn-outline-brand mt-3 hide-xs">🔙 Volver al Panel</a>

<?php if (!defined('EMBEDDED_VIEW')): ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../Public/js/aulas.js"></script>
</body>
</html>
<?php endif; ?>
