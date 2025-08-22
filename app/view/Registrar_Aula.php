<?php
if (session_status() == PHP_SESSION_NONE) session_start();
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
<title>🏫 Gestionar Aulas</title>
<link rel="stylesheet" href="../../Public/css/aula.css">
</head>
<body>
<main class="contenedor">
<h1>🏫 Gestionar Aulas</h1>

<?php if($controller->mensaje): ?>
    <div class="mensaje <?= htmlspecialchars($controller->mensaje_tipo) ?>">
        <?= htmlspecialchars($controller->mensaje) ?>
    </div>
<?php endif; ?>

<div class="tarjeta">
    <form method="post">
        <label>Nombre del Aula:</label>
        <input type="text" name="nombre_aula" placeholder="Ej: AIP1" required>

        <label>Capacidad:</label>
        <input type="number" name="capacidad" min="1" required>

        <label>Tipo:</label>
        <select name="tipo" required>
            <option value="AIP">AIP</option>
            <option value="Regular">Regular</option>
        </select>

        <button type="submit" name="registrar_aula">Registrar Aula</button>
    </form>
</div>

<div class="tabla-container">
    <table>
        <tr>
            <th>Nombre</th>
            <th>Capacidad</th>
            <th>Tipo</th>
            <th>Acciones</th>
        </tr>
        <?php if(!empty($aulas)): ?>
            <?php foreach($aulas as $aula): ?>
                <?php if($id_editar == $aula['id_aula']): ?>
                    <form method="post">
                    <tr>
                        <td><input type="text" name="nombre_aula" value="<?= htmlspecialchars($aula['nombre_aula']) ?>" required></td>
                        <td><input type="number" name="capacidad" value="<?= htmlspecialchars($aula['capacidad']) ?>" min="1" required></td>
                        <td>
                            <select name="tipo" required>
                                <option value="AIP" <?= $aula['tipo']=='AIP'?'selected':'' ?>>AIP</option>
                                <option value="Regular" <?= $aula['tipo']=='Regular'?'selected':'' ?>>Regular</option>
                            </select>
                        </td>
                        <td>
                            <input type="hidden" name="id_aula" value="<?= $aula['id_aula'] ?>">
                            <button type="submit" name="editar_aula">💾 Guardar</button>
                            <a href="Registrar_Aula.php">❌ Cancelar</a>
                        </td>
                    </tr>
                    </form>
                <?php else: ?>
                    <tr>
                        <td><?= htmlspecialchars($aula['nombre_aula']) ?></td>
                        <td><?= htmlspecialchars($aula['capacidad']) ?></td>
                        <td><?= htmlspecialchars($aula['tipo']) ?></td>
                        <td>
                            <a href="?editar=<?= $aula['id_aula'] ?>">✏️ Editar</a> |
                            <a href="?eliminar=<?= $aula['id_aula'] ?>" class="btn-eliminar" onclick="return confirm('¿Seguro que deseas eliminar esta aula?')">🗑️ Eliminar</a>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4" style="text-align:center;">No hay aulas registradas.</td></tr>
        <?php endif; ?>
    </table>
</div>

<a href="admin.php" class="volver">🔙 Volver al Panel</a>
</main>
</body>
</html>
