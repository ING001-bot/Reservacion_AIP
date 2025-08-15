<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar Sesión - Aulas de Innovación</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/login.css">
</head>
<body>
<main class="login-container">
    <form method="post" class="login-form" action="../app/controllers/LoginController.php">
        <h2>🔑 Iniciar Sesión</h2>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje error"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <input type="email" name="correo" placeholder="Correo electrónico" required>
        <input type="password" name="contraseña" placeholder="Contraseña" required>
        <button type="submit">Ingresar</button>

        <div class="enlaces">
            <a href="../app/controllers/AdminController.php?modo=externo">Crear cuenta</a> | 
            <a href="recuperar_contraseña.php">¿Olvidaste tu contraseña?</a>
        </div>
    </form>
</main>
</body>
</html>
