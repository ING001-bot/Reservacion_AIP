<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reservar Aula</title>
<link rel="stylesheet" href="css/reserva.css">
</head>
<body>
<main class="contenedor">
    <h1>📅 Reservar Aula</h1>
    <a href="dashboard.php" class="btn-volver">⬅ Volver al Dashboard</a>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($exito): ?>
        <p class="exito"><?= htmlspecialchars($exito) ?></p>
    <?php endif; ?>

    <form method="POST" class="formulario">
        <label for="id_aula">Seleccione un Aula:</label>
        <select name="id_aula" id="id_aula" required>
            <option value="">-- Seleccione --</option>
            <?php foreach ($aulas as $fila): ?>
                <option value="<?= $fila['id_aula'] ?>">
                    <?= htmlspecialchars($fila['nombre_aula']) ?> (Capacidad: <?= $fila['capacidad'] ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <label for="fecha">Fecha:</label>
        <input type="date" name="fecha" id="fecha" required>

        <label for="hora_inicio">Hora de Inicio:</label>
        <input type="time" name="hora_inicio" id="hora_inicio" required>

        <label for="hora_fin">Hora de Fin:</label>
        <input type="time" name="hora_fin" id="hora_fin" required>

        <button type="submit">Reservar</button>
    </form>
</main>
</body>
</html>