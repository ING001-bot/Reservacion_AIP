<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config/conexion.php';
require '../controllers/AulaController.php';

$rol = $_SESSION['tipo'] ?? null;
if ($rol !== 'Administrador') { header('Location: Dashboard.php'); exit; }

$controller = new AulaController($conexion);

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['registrar_aula'])) $controller->registrarAula($_POST);
    elseif (isset($_POST['editar_aula'])) $controller->editarAula($_POST);
}

// Procesar GET para eliminar
if (isset($_GET['eliminar'])) $controller->eliminarAula(intval($_GET['eliminar']));

// Obtener aulas
$aulas = $controller->listarAulas();
$id_editar = $_GET['editar'] ?? null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🏫 Gestión de Aulas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../Public/css/brand.css">
</head>
<body class="bg-light">

<main class="container py-4">

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
                <input type="text" name="nombre_aula" class="form-control" placeholder="Ej: AIP1" required>
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
    <div class="card-header bg-brand text-white">Aulas Registrados</div>
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
                            <?php if($id_editar == $aula['id_aula']): ?>
                                <form method="post">
                                    <tr>
                                        <td><input type="text" name="nombre_aula" value="<?= htmlspecialchars($aula['nombre_aula']) ?>" class="form-control" required></td>
                                        <td><input type="number" name="capacidad" value="<?= htmlspecialchars($aula['capacidad']) ?>" class="form-control" min="1" required></td>
                                        <td>
                                            <select name="tipo" class="form-select" required>
                                                <option value="AIP" <?= $aula['tipo']=='AIP'?'selected':'' ?>>AIP</option>
                                                <option value="Regular" <?= $aula['tipo']=='Regular'?'selected':'' ?>>Regular</option>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <input type="hidden" name="id_aula" value="<?= $aula['id_aula'] ?>">
                                            <button type="submit" name="editar_aula" class="btn btn-sm btn-success">💾 Guardar</button>
                                            <a href="Registrar_Aula.php" class="btn btn-sm btn-secondary">❌ Cancelar</a>
                                        </td>
                                    </tr>
                                </form>
                            <?php else: ?>
                                <tr>
                                    <td><?= htmlspecialchars($aula['nombre_aula']) ?></td>
                                    <td><?= htmlspecialchars($aula['capacidad']) ?></td>
                                    <td><?= htmlspecialchars($aula['tipo']) ?></td>
                                    <td class="text-center">
                                        <a href="?editar=<?= $aula['id_aula'] ?>" class="btn btn-sm btn-outline-primary">✏️ Editar</a>
                                        <a href="?eliminar=<?= $aula['id_aula'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Seguro que deseas eliminar esta aula?')">🗑️ Eliminar</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
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

<a href="admin.php" class="btn btn-outline-brand mt-3">🔙 Volver al Panel</a>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
