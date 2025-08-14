<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="public/css/estilo_r.css">
</head>
<body>
    <form method="post" class="formulario">
        <h2>📝 Registro de Usuario</h2>
        <input type="text" name="nombre" placeholder="Nombre completo" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
        <input type="email" name="correo" placeholder="Correo electrónico" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" required>
        <input type="password" name="contraseña" placeholder="Contraseña (mínimo 6 caracteres)" required>
        <select name="tipo_usuario" required>
            <option value="">-- Selecciona tipo de usuario --</option>
            <option value="Administrador" <?= (($_POST['tipo_usuario'] ?? '') === 'Administrador') ? 'selected' : '' ?>>Administrador</option>
            <option value="Usuario" <?= (($_POST['tipo_usuario'] ?? '') === 'Usuario') ? 'selected' : '' ?>>Usuario</option>
        </select>
        <button type="submit">Registrar</button>
        <div style="margin-top: 15px;">
            <a href="index.php">⬅ Volver al Login</a>
        </div>
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?= htmlspecialchars($mensaje_tipo) ?>">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>
    </form>
</body>
</html>
