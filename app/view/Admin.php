<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['tipo'] ?? null;
$usuarioExterno = isset($_GET['modo']) && $_GET['modo'] === 'registro';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>
        <?= $rol === 'Administrador' ? "⚙ Panel de Administración" : ($usuarioExterno ? "👤 Registro de Profesor" : "") ?>
    </title>
    <link rel="stylesheet" href="../../Public/css/admin.css">
</head>
<body>
    <h1>
        <?= $rol === 'Administrador' ? "⚙ Panel de Administración" : ($usuarioExterno ? "👤 Registro de Profesor" : "") ?>
    </h1>

    <?php if (!empty($mensaje)): ?>
        <div class="mensaje <?= htmlspecialchars($mensaje_tipo) ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <!-- Formulario de Usuario -->
    <?php if ($usuarioExterno || $rol === 'Administrador'): ?>
    <div class="tarjeta">
        <h2>👤 Registrar Usuario</h2>
        <form method="post" action="../controllers/AdminController.php">
            <label>Nombre:</label>
            <input type="text" name="nombre" required>
            <label>Correo:</label>
            <input type="email" name="correo" required>
            <label>Contraseña:</label>
            <input type="password" name="contraseña" required minlength="6">

            <?php if ($rol === 'Administrador'): ?>
                <label>Tipo de Usuario:</label>
                <select name="tipo" required>
                    <option value="">-- Selecciona un tipo --</option>
                    <option value="Profesor">Profesor</option>
                    <option value="Encargado">Encargado</option>
                    <option value="Administrador">Administrador</option>
                </select>
            <?php endif; ?>

            <button type="submit" name="registrar_usuario">Registrar Usuario</button>
        </form>
    </div>
    <?php endif; ?>
                
    <!-- Formulario de Equipo (solo admin) -->
    <?php if ($rol === 'Administrador'): ?>
    <div class="tarjeta">
        <h2>💻 Registrar Equipo</h2>
        <form method="post" action="../controllers/AdminController.php">
            <label>Nombre del Equipo:</label>
            <input type="text" name="nombre_equipo" required>
            <label>Tipo de Equipo:</label>
            <input type="text" name="tipo_equipo" required>
            <button type="submit" name="registrar_equipo">Registrar Equipo</button>
        </form>
    </div>
    <?php endif; ?>

    <a href="../view/Dashboard.php" class="volver">🔙 Volver</a>
</body>
</html>
