<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../models/UsuarioModel.php';

$model = new UsuarioModel();
$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$msg = '';
$type = '';
$valid = false;
$user = null;

if ($token) {
    $user = $model->obtenerPorResetToken($token);
    if ($user) {
        $valid = true;
    } else {
        $msg = '❌ Enlace inválido o expirado.';
        $type = 'danger';
    }
} else {
    $msg = '❌ Falta el token de restablecimiento.';
    $type = 'danger';
}

if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restablecer'])) {
    $pass = $_POST['nueva'] ?? '';
    $conf = $_POST['confirmar'] ?? '';
    if (strlen($pass) < 8) {
        $msg = '⚠️ La contraseña debe tener al menos 8 caracteres.';
        $type = 'danger';
    } elseif ($pass !== $conf) {
        $msg = '⚠️ Las contraseñas no coinciden.';
        $type = 'danger';
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $ok = $model->actualizarContraseñaPorId($user['id_usuario'], $hash);
        if ($ok) {
            $model->limpiarResetToken($user['correo']);
            $msg = '✅ Contraseña actualizada correctamente. Ya puedes iniciar sesión.';
            $type = 'success';
            // invalidar para no mostrar formulario otra vez
            $valid = false;
        } else {
            $msg = '❌ No se pudo actualizar la contraseña. Intenta más tarde.';
            $type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restablecer contraseña</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="../../Public/css/brand.css">
</head>
<body class="bg-light">
  <main class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card card-brand shadow-sm">
          <div class="card-header bg-brand text-white">Restablecer contraseña</div>
          <div class="card-body">
            <?php if (!empty($msg)): ?>
              <div class="alert alert-<?= htmlspecialchars($type) ?>" role="alert"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <?php if ($valid): ?>
              <form method="post" class="row g-3">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="col-12">
                  <label class="form-label">Nueva contraseña</label>
                  <div class="password-field">
                    <input type="password" class="form-control" name="nueva" id="reset-pass" required minlength="8" placeholder="Mínimo 8 caracteres">
                    <button type="button" class="toggle-password" onclick="(function(){var i=document.getElementById('reset-pass');i.type=i.type==='password'?'text':'password';})();return false;">
                      <i class="far fa-eye"></i>
                    </button>
                  </div>
                </div>
                <div class="col-12">
                  <label class="form-label">Confirmar contraseña</label>
                  <div class="password-field">
                    <input type="password" class="form-control" name="confirmar" id="reset-pass2" required minlength="8">
                    <button type="button" class="toggle-password" onclick="(function(){var i=document.getElementById('reset-pass2');i.type=i.type==='password'?'text':'password';})();return false;">
                      <i class="far fa-eye"></i>
                    </button>
                  </div>
                </div>
                <div class="col-12">
                  <button type="submit" name="restablecer" class="btn btn-brand">Actualizar contraseña</button>
                  <a href="../../Public/index.php" class="btn btn-outline-brand">Volver al inicio</a>
                </div>
              </form>
            <?php else: ?>
              <a href="../../Public/index.php" class="btn btn-brand">Ir al inicio de sesión</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
