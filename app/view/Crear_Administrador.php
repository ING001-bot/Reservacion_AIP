<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../lib/Mailer.php';

// Permitir acceso si NO existe ningún administrador aún (bootstrap inicial)
$tipo = $_SESSION['tipo'] ?? '';
$usuarioModel = new UsuarioModel();

// Verificar si ya existe al menos un administrador
$hayAdmin = false;
try {
    $stmt = $conexion->prepare("SELECT COUNT(*) AS c FROM usuarios WHERE tipo_usuario = 'Administrador'");
    $stmt->execute();
    $hayAdmin = ((int)$stmt->fetchColumn()) > 0;
} catch (Throwable $e) {
    // Si falla la consulta (BD recién creada pero sin tablas), asumimos que no hay admin todavía
    $hayAdmin = false;
}

// Si ya hay admin y el usuario en sesión no es admin, bloquear
if ($hayAdmin && $tipo !== 'Administrador') {
    http_response_code(403);
    echo 'Acceso denegado';
    exit;
}

$mensaje = '';
$mensaje_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_usuario_admin'])) {
    $nombre = $_POST['nombre'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $pass   = $_POST['contraseña'] ?? '';

    if (!$hayAdmin) {
        // Bootstrap inicial: SOLO crear si el correo es válido y EXISTENTE (envío SMTP exitoso)
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $mensaje = '⚠️ Ingresa un correo válido.';
            $mensaje_tipo = 'error';
        } elseif (strlen($pass) < 6) {
            $mensaje = '⚠️ La contraseña debe tener al menos 6 caracteres.';
            $mensaje_tipo = 'error';
        } else {
            // Validación MX del dominio
            $dominio = substr(strrchr($correo, '@'), 1) ?: '';
            if ($dominio && function_exists('checkdnsrr') && !checkdnsrr($dominio, 'MX')) {
                $mensaje = '⚠️ El dominio del correo no tiene registros MX válidos.';
                $mensaje_tipo = 'error';
            } else {
                // Probar envío SMTP al correo indicado; si no se puede enviar, NO crear
                $mailer = new \App\Lib\Mailer();
                $subject = 'Alta de Administrador - Aulas de Innovación';
                $html = '<p>Hola ' . htmlspecialchars($nombre) . ',</p>' .
                        '<p>Se está configurando tu cuenta de administrador en el sistema Aulas de Innovación.</p>' .
                        '<p>Si recibiste este correo, tu buzón está listo para notificaciones del sistema.</p>';
                $sent = $mailer->send($correo, $subject, $html);

                if (!$sent) {
                    $mensaje = '❌ No se pudo enviar correo a esa dirección. Usa un correo existente y accesible.';
                    $mensaje_tipo = 'error';
                } else {
                    $hash = password_hash($pass, PASSWORD_BCRYPT);
                    $ok = $usuarioModel->registrarVerificado($nombre, $correo, $hash, 'Administrador');
                    if ($ok) {
                        $mensaje = '✅ Primer administrador creado. Ahora puedes iniciar sesión.';
                        $mensaje_tipo = 'success';
                    } else {
                        $mensaje = '❌ No se pudo crear el administrador.';
                        $mensaje_tipo = 'error';
                    }
                }
            }
        }
    } else {
        // Si ya hay admin, usar el flujo normal (con notificación por correo)
        require_once __DIR__ . '/../controllers/UsuarioController.php';
        $controller = new UsuarioController();
        $res = $controller->registrarUsuario($nombre, $correo, $pass, 'Administrador');
        $mensaje = $res['mensaje'];
        $mensaje_tipo = $res['error'] ? 'error' : 'success';
    }
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
  <script src="../../Public/js/theme.js"></script>
</body>
</html>
