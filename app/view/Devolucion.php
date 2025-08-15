<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Devolución</title>
    <link rel="stylesheet" href="../../Public/css/devolucion.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <h2>Equipos Prestados</h2>

    <?php if (!empty($mensaje)) : ?>
        <div class="mensaje"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <table>
        <tr>
            <th>Equipo</th>
            <th>Responsable</th>
            <th>Fecha Préstamo</th>
            <th>Acción</th>
        </tr>
        <?php foreach ($prestamos as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['nombre_equipo'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['fecha_prestamo'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><a class="devolver" href="DevolucionController.php?devolver=<?= urlencode($row['id_prestamo']) ?>">✅ Devolver</a></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="boton-volver">
        <a href="dashboard.php"><button>⬅️ Volver al Dashboard</button></a>
    </div>
</body>
</html>
