<?php
session_start();
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
<link rel="stylesheet" href="../../Public/css/equipo.css">
</head>
<body>
<main class="contenedor">
<h1>💻 Gestión de Equipos</h1>

<?php if (!empty($mensaje)): ?>
    <div class="mensaje <?= htmlspecialchars($mensaje_tipo) ?>">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<div class="tarjeta">
    <form method="post">
        <label>Nombre del Equipo:</label>
        <input type="text" name="nombre_equipo" required>
        <label>Tipo de Equipo:</label>
        <input type="text" name="tipo_equipo" required>
        <button type="submit" name="registrar_equipo">Registrar Equipo</button>
    </form>
</div>

<div class="tabla-container">
    <table>
        <thead>
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
                        <td><input type="text" name="nombre_equipo" value="<?= htmlspecialchars($eq['nombre_equipo']) ?>" required></td>
                        <td><input type="text" name="tipo_equipo" value="<?= htmlspecialchars($eq['tipo_equipo']) ?>" required></td>
                        <td>
                            <input type="hidden" name="id_equipo" value="<?= $eq['id_equipo'] ?>">
                            <button type="submit" name="editar_equipo">💾 Guardar</button>
                            <a href="Registrar_Equipo.php">❌ Cancelar</a>
                        </td>
                    </tr>
                </form>
            <?php else: ?>
                <tr>
                    <td><?= $eq['id_equipo'] ?></td>
                    <td><?= htmlspecialchars($eq['nombre_equipo']) ?></td>
                    <td><?= htmlspecialchars($eq['tipo_equipo']) ?></td>
                    <td>
                        <a href="?editar=<?= $eq['id_equipo'] ?>">✏️ Editar</a> |
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="id_equipo" value="<?= $eq['id_equipo'] ?>">
                            <button type="submit" name="eliminar_equipo" onclick="return confirm('¿Seguro que deseas eliminar esta aula?')">🗑️ Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<a href="admin.php" class="volver">🔙 Volver al Panel</a>
</main>
</body>
</html>
