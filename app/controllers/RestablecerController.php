<?php
require_once __DIR__ . '/../models/UsuarioModel.php';

$model = new UsuarioModel();
$token = $_GET['token'] ?? '';
$mensaje = '';
$tipo = '';
$user = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($token) {
        $user = $model->obtenerPorResetToken($token);
        if (!$user) {
            $mensaje = '❌ Enlace inválido o expirado.';
            $tipo = 'error';
        }
    } else {
        $mensaje = '❌ Token faltante.';
        $tipo = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $nueva = $_POST['nueva'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    $user = $token ? $model->obtenerPorResetToken($token) : null;
    if (!$user) {
        $mensaje = '❌ Enlace inválido o expirado.';
        $tipo = 'error';
    } else {
        if (strlen($nueva) < 8) {
            $mensaje = '⚠️ La contraseña debe tener al menos 8 caracteres.';
            $tipo = 'error';
        } elseif ($nueva !== $confirmar) {
            $mensaje = '⚠️ Las contraseñas no coinciden.';
            $tipo = 'error';
        } else {
            $hash = password_hash($nueva, PASSWORD_BCRYPT);
            if ($model->actualizarContraseñaPorId($user['id_usuario'], $hash)) {
                $model->limpiarResetToken($user['correo']);
                $mensaje = '✅ Contraseña actualizada. Ahora puedes iniciar sesión.';
                $tipo = 'success';
                $user = null; // para ocultar el formulario
            } else {
                $mensaje = '❌ No se pudo actualizar la contraseña.';
                $tipo = 'error';
            }
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../Public/css/brand.css">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-body">
            <h1 class="h4 mb-3">Restablecer contraseña</h1>
            <?php if ($mensaje): ?>
              <div class="alert alert-<?php echo ($tipo==='success')?'success':'danger'; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <?php if ($user): ?>
            <form method="POST">
              <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
              <div class="mb-3">
                <label class="form-label">Nueva contraseña</label>
                <input type="password" class="form-control" name="nueva" required minlength="8">
              </div>
              <div class="mb-3">
                <label class="form-label">Confirmar nueva contraseña</label>
                <input type="password" class="form-control" name="confirmar" required minlength="8">
              </div>
              <button class="btn btn-brand" type="submit">Guardar</button>
              <a href="../../Public/index.php" class="btn btn-outline-secondary">Cancelar</a>
            </form>
            <?php else: ?>
              <a href="../../Public/index.php" class="btn btn-brand">Ir al inicio de sesión</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
