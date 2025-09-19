<?php
// Iniciar sesión para leer mensajes desde el controlador de login
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$mensaje = $_SESSION['login_msg'] ?? '';
$mensajeClase = $_SESSION['login_msg_type'] ?? 'error';
// limpiar para evitar que persista al refrescar
unset($_SESSION['login_msg'], $_SESSION['login_msg_type']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar Sesión - Aulas de Innovación</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/login.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<main class="login-container">
    <form method="post" class="login-form" action="../app/controllers/LoginController.php">
        <h2>🔑 Iniciar Sesión</h2>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?= htmlspecialchars($mensajeClase) ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <input type="email" name="correo" placeholder="Correo electrónico" required>
        <div class="password-field">
            <input type="password" name="contraseña" id="login-password" placeholder="Contraseña" required>
            <button type="button" class="toggle-password" aria-label="Mostrar/Ocultar contraseña" onclick="togglePassword('login-password')">
                <i class="far fa-eye"></i>
            </button>
        </div>
        <button type="submit">Ingresar</button>

        <div class="enlaces">
            <a href="../app/view/Registrar_Usuario.php">Crear cuenta</a> | 
            <a href="recuperar_contraseña.php">¿Olvidaste tu contraseña?</a>
        </div>
    </form>
</main>
<script src="js/login.js"></script>
</body>
</html>
