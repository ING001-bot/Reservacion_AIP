<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SESSION['tipo'] !== 'Administrador') {
    header('Location: Dashboard.php');
    exit;
}

require '../controllers/EquipoController.php';
$controller = new EquipoController();
$data = $controller->handleRequest();

$equipos = $data['equipos'];
$mensaje = $data['mensaje'];
$mensaje_tipo = $data['mensaje_tipo'];
$id_editar = $_GET['editar'] ?? null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>💻 Equipos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../Public/css/brand.css">
</head>
<body class="bg-light">

<main class="container py-4">

<h1 class="mb-4 text-brand">💻 Gestión de Equipos</h1>

<?php if (!empty($mensaje)): ?>
<div class="alert alert-<?= htmlspecialchars($mensaje_tipo) ?> alert-dismissible fade show shadow-sm" role="alert">
    <?= htmlspecialchars($mensaje) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>

<!-- Formulario registro equipo -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-brand text-white">Registrar Equipo</div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre del Equipo</label>
                <input type="text" name="nombre_equipo" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Tipo de Equipo</label>
                <input type="text" name="tipo_equipo" class="form-control" required>
            </div>
            <div class="col-12">
                <button type="submit" name="registrar_equipo" class="btn btn-brand">Registrar Equipo</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla equipos -->
<div class="card shadow-sm">
    <div class="card-header bg-brand text-white">Equipos Registrados</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-primary text-center">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($equipos as $eq): ?>
                    <?php if ($id_editar == $eq['id_equipo']): ?>
                        <form method="post">
                        <tr>
                            <td><?= $eq['id_equipo'] ?></td>
                            <td><input type="text" name="nombre_equipo" value="<?= htmlspecialchars($eq['nombre_equipo']) ?>" class="form-control" required></td>
                            <td><input type="text" name="tipo_equipo" value="<?= htmlspecialchars($eq['tipo_equipo']) ?>" class="form-control" required></td>
                            <td>
                                <input type="hidden" name="id_equipo" value="<?= $eq['id_equipo'] ?>">
                                <button type="submit" name="editar_equipo" class="btn btn-sm btn-success">💾 Guardar</button>
                                <a href="Registrar_Equipo.php" class="btn btn-sm btn-secondary">❌ Cancelar</a>
                            </td>
                        </tr>
                        </form>
                    <?php else: ?>
                        <tr>
                            <td><?= $eq['id_equipo'] ?></td>
                            <td><?= htmlspecialchars($eq['nombre_equipo']) ?></td>
                            <td><?= htmlspecialchars($eq['tipo_equipo']) ?></td>
                            <td class="text-center">
                                <a href="?editar=<?= $eq['id_equipo'] ?>" class="btn btn-sm btn-outline-primary">✏️ Editar</a>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id_equipo" value="<?= $eq['id_equipo'] ?>">
                                    <button type="submit" name="eliminar_equipo" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('¿Seguro que deseas eliminar este equipo?')">🗑️ Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-4">
    <a href="admin.php" class="btn btn-outline-brand">🔙 Volver al Panel</a>
</div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
