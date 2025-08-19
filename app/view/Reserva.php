<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensaje = $mensaje ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reservar Aula</title>
<link rel="stylesheet" href="../../Public/css/reserva.css">
<style>
    .aulas { margin: 20px 0; }
    .formulario { display: none; margin-top: 15px; padding: 15px; border: 1px solid #ccc; }
    .btn { padding: 10px 15px; margin-right: 10px; cursor: pointer; }
    .mensaje { margin: 10px 0; font-weight: bold; }
</style>
</head>
<body>
<main class="contenedor">
    <h1>📅 Reservar Aula</h1>

    <?php if (!empty($mensaje)): ?>
        <p class="mensaje"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <div class="aulas">
        <button class="btn" onclick="mostrarFormulario('aip1')">AIP 1</button>
        <button class="btn" onclick="mostrarFormulario('aip2')">AIP 2</button>
    </div>

    <!-- Formulario AIP 1 -->
    <form method="POST" class="formulario" id="aip1">
        <h2>Reserva AIP 1</h2>
        <input type="hidden" name="id_aula" value="1">
        <label>Fecha:</label>
        <input type="date" name="fecha" required>
        <label>Hora Inicio:</label>
        <input type="time" name="hora_inicio" required>
        <label>Hora Fin:</label>
        <input type="time" name="hora_fin" required>
        <button type="submit" name="reservar_aip1">Reservar AIP 1</button>
    </form>

    <!-- Formulario AIP 2 -->
    <form method="POST" class="formulario" id="aip2">
        <h2>Reserva AIP 2</h2>
        <input type="hidden" name="id_aula" value="2">
        <label>Fecha:</label>
        <input type="date" name="fecha" required>
        <label>Hora Inicio:</label>
        <input type="time" name="hora_inicio" required>
        <label>Hora Fin:</label>
        <input type="time" name="hora_fin" required>
        <button type="submit" name="reservar_aip2">Reservar AIP 2</button>
    </form>
    <a href="dashboard.php" class="btn-volver">⬅ Volver al Dashboard</a>

</main>

<script>
function mostrarFormulario(aula) {
    document.getElementById('aip1').style.display = 'none';
    document.getElementById('aip2').style.display = 'none';
    document.getElementById(aula).style.display = 'block';
}
</script>
</body>
</html>
