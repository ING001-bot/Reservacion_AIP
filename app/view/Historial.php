<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historial de Uso</title>
<link rel="stylesheet" href="../../Public/css/historial.css">
</head>
<body>
<main class="contenedor">
    <h1>📄 Historial de Uso</h1>

    <div class="info">
        <p><strong>👤 Nombre:</strong> <?= htmlspecialchars($datos_usuario['nombre']) ?></p>
        <p><strong>📧 Correo:</strong> <?= htmlspecialchars($datos_usuario['correo']) ?></p>
        <p><strong>📅 Fecha y Hora:</strong> <?= date("Y-m-d H:i:s") ?></p>
    </div>

    <h2>📚 Reservas de Aulas</h2>
    <table>
        <tr>
            <th>Aula</th>
            <th>Fecha</th>
            <th>Hora Inicio</th>
            <th>Hora Fin</th>
        </tr>
        <?php if ($reservas): ?>
            <?php foreach ($reservas as $res): ?>
                <tr>
                    <td><?= htmlspecialchars($res['nombre_aula']) ?></td>
                    <td><?= htmlspecialchars($res['fecha']) ?></td>
                    <td><?= htmlspecialchars($res['hora_inicio']) ?></td>
                    <td><?= htmlspecialchars($res['hora_fin']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">❌ No hay reservas registradas.</td></tr>
        <?php endif; ?>
    </table>

    <h2>💻 Préstamos de Equipos</h2>
    <table>
        <tr>
            <th>Equipo</th>
            <th>Fecha Préstamo</th>
            <th>Devolución</th>
            <th>Estado</th>
        </tr>
        <?php if ($prestamos): ?>
            <?php foreach ($prestamos as $pre): ?>
                <tr>
                    <td><?= htmlspecialchars($pre['nombre_equipo']) ?></td>
                    <td><?= htmlspecialchars($pre['fecha_prestamo']) ?></td>
                    <td><?= $pre['fecha_devolucion'] ? htmlspecialchars($pre['fecha_devolucion']) : 'Pendiente' ?></td>
                    <td><?= htmlspecialchars($pre['estado']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">❌ No hay préstamos registrados.</td></tr>
        <?php endif; ?>
    </table>

    <p class="centrar">
        <a href="exportar_pdf.php" class="btn">📥 Descargar PDF</a>
        <a href="dashboard.php" class="btn-volver">⬅ Volver al Dashboard</a>
    </p>
</main>
</body>
</html>

