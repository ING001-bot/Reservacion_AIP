<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require '../controllers/PrestamoController.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitar Préstamo</title>
    <link rel="stylesheet" href="../../Public/css/estilo.css">
</head>
<body>
    <form method="post" class="formulario">
        <h2>Préstamo de Equipos</h2>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?= htmlspecialchars($mensaje_tipo) ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <label for="equipo">Seleccione un equipo disponible:</label>
        <select name="equipo" id="equipo" required>
            <option value="">-- Selecciona un equipo --</option>
            <?php foreach ($equipos as $eq): ?>
                <option value="<?= $eq['id_equipo'] ?>">
                    <?= htmlspecialchars($eq['nombre_equipo']) ?> (<?= htmlspecialchars($eq['tipo_equipo']) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-primary">Solicitar Préstamo</button>
        <a href="../view/Dashboard.php" class="btn btn-secondary">⬅ Volver</a>
    </form>
</body>
</html>
