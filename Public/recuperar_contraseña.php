<?php
require_once __DIR__ . '/../app/controllers/OlvideContrasenaController.php';
$ctl = new OlvideContrasenaController();
$res = $ctl->handle();
$msg = $res['msg'] ?? '';
$type = $res['type'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar contraseña</title>
  <link rel="stylesheet" href="css/login.css">
  <link rel="stylesheet" href="css/brand.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <main class="login-container">
    <form method="post" class="login-form">
      <h2>📧 Recuperar contraseña</h2>
      <?php if (!empty($msg)): ?>
        <div class="mensaje <?= htmlspecialchars($type) ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>
      <p class="text-muted" style="margin:0 0 12px 0">Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>
      <input type="email" name="correo" placeholder="Correo electrónico" required>
      <button type="submit" name="solicitar_reset">Enviar enlace de restablecimiento</button>
      <div class="enlaces">
        <a href="index.php">Volver al inicio de sesión</a>
      </div>
    </form>
  </main>
</body>
</html>
