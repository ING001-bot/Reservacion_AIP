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

// Si ya hay admin, esta página deja de estar disponible para todos.
if ($hayAdmin) {
    if ($tipo === 'Administrador') {
        header('Location: Admin.php');
    } else {
        header('Location: ../../Public/index.php');
    }
    exit;
}

$mensaje = '';
$mensaje_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_usuario_admin'])) {
    $nombre = $_POST['nombre'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $pass   = $_POST['contraseña'] ?? '';
    $telefono = $_POST['telefono'] ?? null;

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
                        // Guardar teléfono si fue proporcionado
                        try { $usuarioModel->actualizarTelefonoPorCorreo($correo, $telefono); } catch (\Throwable $e) { /* ignore */ }
                        // Marcar instalación completada
                        try {
                            $conexion->exec("CREATE TABLE IF NOT EXISTS app_config (cfg_key VARCHAR(64) PRIMARY KEY, cfg_value VARCHAR(255) NULL)");
                            $stmtCfg = $conexion->prepare("SELECT cfg_value FROM app_config WHERE cfg_key='setup_completed'");
                            $stmtCfg->execute();
                            $val = $stmtCfg->fetchColumn();
                            if ($val === false) {
                                $conexion->prepare("INSERT INTO app_config (cfg_key, cfg_value) VALUES ('setup_completed','1')")->execute();
                            } else {
                                $conexion->prepare("UPDATE app_config SET cfg_value='1' WHERE cfg_key='setup_completed'")->execute();
                            }
                        } catch (\Throwable $e) {
                            error_log('No se pudo marcar setup_completed: ' . $e->getMessage());
                        }
                        // Redirigir al login
                        $_SESSION['login_msg'] = '✅ Primer administrador creado. Ahora puedes iniciar sesión.';
                        $_SESSION['login_msg_type'] = 'success';
                        header('Location: ../../Public/index.php');
                        exit;
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
  <title>Crear Administrador • Colegio Monseñor Juan Tomis Stack</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../../Public/css/brand.css">
  <link rel="stylesheet" href="../../Public/css/crear_admin.css">
</head>
<body class="crear-admin">
  <div class="hero-wrap">
    <section class="brand-panel">
      <img class="brand-logo" src="../../Public/img/logo_colegio.png" alt="Logo del colegio" onerror="this.style.display='none'">
      <h1 class="brand-title">Colegio Monseñor Juan Tomis Stack</h1>
      <p class="brand-sub">Configuración inicial del sistema • Crear Administrador</p>
      <div class="kpi-badges">
        <span class="badge"><i class="fa-solid fa-shield"></i> Seguro y confiable</span>
        <span class="badge"><i class="fa-solid fa-sparkles"></i> UI 2025</span>
        <span class="badge"><i class="fa-solid fa-chalkboard"></i> Aulas de Innovación</span>
      </div>
    </section>

    <section class="card-glass form-card">
      <div class="form-head">
        <h2>Crear Administrador</h2>
        <span class="year"><?= date('Y') ?></span>
      </div>
      <div class="form-brand">
        <img class="form-logo" src="../../Public/img/logo_colegio.png" alt="Logo del colegio" onerror="this.style.display='none'">
        <h3 class="form-title">Colegio Monseñor Juan Tomis Stack</h3>
      </div>

      <?php if (!empty($mensaje)): ?>
        <div class="alert <?= htmlspecialchars($mensaje_tipo) ?>">
          <?= htmlspecialchars($mensaje) ?>
        </div>
      <?php endif; ?>

      <form method="post" class="form-grid">
        <div>
          <div class="label">Nombre</div>
          <input type="text" name="nombre" class="input" required>
        </div>
        <div>
          <div class="label">Correo</div>
          <input type="email" name="correo" class="input" required>
        </div>
        <div>
          <div class="label">Teléfono (con código de país)</div>
          <input type="tel" name="telefono" class="input" placeholder="+51987654321">
        </div>
        <div class="col-span-2">
          <div class="label">Contraseña</div>
          <div class="password-field">
            <input type="password" name="contraseña" id="admin-pass" class="input" required minlength="6" autocomplete="new-password">
            <button id="toggle-pass" class="toggle" aria-label="Mostrar/Ocultar contraseña"><i class="fa-regular fa-eye"></i></button>
          </div>
          <div id="strength" class="strength"><span id="strength-bar"></span></div>
        </div>

        <div class="gap col-span-2"></div>
        <div class="actions col-span-2">
          <button type="submit" name="registrar_usuario_admin" class="btn"><i class="fa-solid fa-crown"></i> Crear Administrador</button>
          <a href="Admin.php" class="btn-outline"><i class="fa-solid fa-arrow-left"></i> Volver</a>
        </div>
      </form>

      <div class="footer-note">Este será el primer usuario con rol Administrador. Usaremos su correo para notificaciones del sistema.</div>
    </section>
  </div>

  <script src="../../Public/js/crear_admin.js"></script>
  <script src="../../Public/js/theme.js"></script>
</body>
</html>
