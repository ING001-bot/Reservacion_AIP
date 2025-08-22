<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../controllers/ReservaController.php';

$nombreProfesor = $_SESSION['usuario'] ?? 'Invitado';

// Fecha mínima y valor predeterminado en formato YYYY-MM-DD
$fecha_min = date('Y-m-d'); // hoy
$fecha_default = $fecha_min;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reservar Aula</title>
<link rel="stylesheet" href="../../Public/css/reserva.css">
</head>
<body>
<main class="contenedor">
    <h1>📅 Reservar Aula</h1>

    <?php if (!empty($mensaje)): ?>
        <p class="mensaje"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <!-- Formulario único -->
    <form method="POST">
        <label>Profesor:</label>
        <input type="text" value="<?= htmlspecialchars($nombreProfesor) ?>" readonly><br><br>

        <label>Seleccionar Aula (Solo AIP):</label>
        <select name="id_aula" required>
            <?php foreach ($aulas as $aula): ?>
                <option value="<?= $aula['id_aula'] ?>">
                    <?= $aula['nombre_aula'] ?> (Cap: <?= $aula['capacidad'] ?>)
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Fecha:</label>
        <input type="date" name="fecha" required 
               min="<?= $fecha_min ?>" 
               value="<?= $fecha_default ?>"><br><br>

        <label>Hora Inicio:</label>
        <input type="time" name="hora_inicio" required><br><br>

        <label>Hora Fin:</label>
        <input type="time" name="hora_fin" required><br><br>

        <button type="submit" name="accion" value="guardar">Reservar</button>
    </form>

    <hr>

    <!-- Tabla de reservas -->
    <h2>Reservas Registradas</h2>
    <table border="1" cellpadding="5">
        <tr>
            <th>Profesor</th>
            <th>Aula</th>
            <th>Capacidad</th>
            <th>Fecha</th>
            <th>Hora Inicio</th>
            <th>Hora Fin</th>
        </tr>
        <?php foreach ($reservas as $reserva): ?>
            <tr>
                <td><?= htmlspecialchars($reserva['profesor']) ?></td>
                <td><?= htmlspecialchars($reserva['nombre_aula']) ?></td>
                <td><?= htmlspecialchars($reserva['capacidad']) ?></td>
                <td><?= htmlspecialchars($reserva['fecha']) ?></td>
                <td><?= htmlspecialchars($reserva['hora_inicio']) ?></td>
                <td><?= htmlspecialchars($reserva['hora_fin']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <br>
    <a href="dashboard.php" class="btn-volver">⬅ Volver al Dashboard</a>
</main>
</body>
</html>
