<?php
session_start();
require '../config/conexion.php';
require '../controllers/PrestamoController.php';

$controller = new PrestamoController($conexion);

if (!isset($_SESSION['usuario']) || $_SESSION['tipo']!=='Encargado') {
    die("Acceso denegado");
}

if (isset($_GET['devolver'])) {
    $controller->devolverEquipo($_GET['devolver']);
}

$prestamos = $controller->obtenerTodosPrestamos();
$mensaje = $_GET['mensaje'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Devolución</title>
    <link rel="stylesheet" href="../../Public/css/devolucion.css">
</head>
<body>
<h2>Todos los Préstamos</h2>

<?php if($mensaje): ?>
    <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<table>
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
            <td><?= htmlspecialchars($row['estado']) ?></td>
            <td>
                <?php if($row['estado']==='Prestado'): ?>
                    <a href="?devolver=<?= $row['id_prestamo'] ?>">✅ Devolver</a>
                <?php else: ?>
                    ✔ Devuelto
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="10" style="text-align:center;">No hay préstamos registrados.</td></tr>
    <?php endif; ?>
</table>

<a href="dashboard.php"><button>⬅ Volver al Dashboard</button></a>
</body>
</html>
