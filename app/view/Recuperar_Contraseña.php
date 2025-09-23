<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="../../Public/css/estilo.css">
</head>
<body>
    <form method="post" class="formulario">
        <h2>🔑 Recuperar Contraseña</h2>
        <input type="email" name="correo" placeholder="Correo registrado" required>
        <button type="submit">Generar nueva contraseña</button>
        <div style="margin-top: 15px;">
            <a href="login.php">⬅ Volver al Login</a>
        </div>
        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?= htmlspecialchars($mensaje_tipo) ?>"><?= $mensaje ?></div>
        <?php endif; ?>
    </form>
</body>
</html>
