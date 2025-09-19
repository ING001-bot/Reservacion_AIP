<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo permitir a Administradores
$tipo = $_SESSION['tipo'] ?? '';
if ($tipo !== 'Administrador') {
    http_response_code(403);
    echo 'Acceso denegado';
    exit;
}

require_once __DIR__ . '/../controllers/UsuarioController.php';
$controller = new UsuarioController();
$mensaje = '';
$mensaje_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_usuario_admin'])) {
    $res = $controller->registrarUsuario($_POST['nombre'] ?? '', $_POST['correo'] ?? '', $_POST['contraseña'] ?? '', 'Administrador');
    $mensaje = $res['mensaje'];
    $mensaje_tipo = $res['error'] ? 'error' : 'success';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear Administrador</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../Public/css/brand.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
  <main class="container py-4">
    <h1 class="mb-4 text-brand">👑 Crear Administrador</h1>

    <?php if (!empty($mensaje)): ?>
    <div class="alert alert-<?php echo htmlspecialchars($mensaje_tipo); ?> alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($mensaje); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
    <?php endif; ?>

    <div class="card card-brand shadow-sm">
      <div class="card-header bg-brand text-white">Nuevo Administrador</div>
      <div class="card-body">
        <form method="post" class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Correo</label>
            <input type="email" name="correo" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Contraseña</label>
            <div class="password-field">
              <input type="password" name="contraseña" id="admin-pass" class="form-control" required minlength="6">
              <button type="button" class="toggle-password" onclick="(function(){var i=document.getElementById('admin-pass');i.type=i.type==='password'?'text':'password';})();return false;">
                <i class="far fa-eye"></i>
              </button>
            </div>
          </div>
          <div class="col-12">
            <button type="submit" name="registrar_usuario_admin" class="btn btn-brand">Crear Administrador</button>
            <a href="Admin.php" class="btn btn-outline-brand">Volver</a>
          </div>
        </form>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
