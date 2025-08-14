<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cambiar Contraseña - Aulas de Innovación</title>
<link rel="stylesheet" href="css/login.css">
</head>
<body>
<main class="login-container">
    <h2>🔑 Cambiar Contraseña</h2>

    <?php if ($error): ?>
        <div class="mensaje error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($exito): ?>
        <div class="mensaje exito"><?= htmlspecialchars($exito) ?></div>
    <?php endif; ?>

    <form method="POST" class="login-form">
        <input type="password" name="actual" placeholder="Contraseña actual" required>
        <input type="password" name="nueva" placeholder="Nueva contraseña" required>
        <input type="password" name="confirmar" placeholder="Confirmar nueva contraseña" required>
        <button type="submit">Actualizar</button>
    </form>

    <a href="dashboard.php" class="btn-volver">⬅ Volver al Dashboard</a>
</main>
</body>
</html>
