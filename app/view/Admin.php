<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="../../Public/css/admin.css">
</head>
<body>
    <h1>⚙ Panel de Administración</h1>

    <?php if ($mensaje): ?>
        <div class="mensaje <?= htmlspecialchars($mensaje_tipo) ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <div class="tarjeta">
        <h2>👤 Registrar Usuario</h2>
        <form method="post">
            <label>Nombre:</label>
            <input type="text" name="nombre" required>
            <label>Correo:</label>
            <input type="email" name="correo" required>
            <label>Contraseña:</label>
            <input type="password" name="contraseña" required minlength="6">
            <label>Tipo de Usuario:</label>
            <select name="tipo" required>
                <option value="">-- Selecciona un tipo --</option>
                <option value="Profesor">Profesor</option>
                <option value="Encargado">Encargado</option>
                <option value="Administrador">Administrador</option>
            </select>
            <button type="submit" name="registrar_usuario">Registrar Usuario</button>
        </form>
    </div>

    <div class="tarjeta">
        <h2>💻 Registrar Equipo</h2>
        <form method="post">
            <label>Nombre del Equipo:</label>
            <input type="text" name="nombre_equipo" required>
            <label>Tipo de Equipo:</label>
            <input type="text" name="tipo_equipo" required>
            <button type="submit" name="registrar_equipo">Registrar Equipo</button>
        </form>
    </div>

    <a href="dashboard.php" class="volver">🔙 Volver al Dashboard</a>
</body>
</html>
