<?php
require_once __DIR__ . '/../models/UsuarioModel.php';

$correo = $_GET['correo'] ?? '';
$token  = $_GET['token'] ?? '';

$mensaje = '';
$tipo = 'error';

if ($correo && $token) {
    $model = new UsuarioModel();
    if ($model->actualizarVerificacionPorToken($correo, $token)) {
        $mensaje = '✅ Tu correo ha sido verificado. Ya puedes iniciar sesión.';
        $tipo = 'success';
    } else {
        $mensaje = '❌ Enlace inválido o expirado.';
        $tipo = 'error';
    }
} else {
    $mensaje = '❌ Datos incompletos.';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verificación de correo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../Public/css/brand.css">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-body">
            <h1 class="h4 mb-3">Verificación de correo</h1>
            <div class="alert alert-<?php echo ($tipo==='success')?'success':'danger'; ?>">
              <?php echo htmlspecialchars($mensaje); ?>
            </div>
            <a href="../../Public/index.php" class="btn btn-brand">Ir al inicio de sesión</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
