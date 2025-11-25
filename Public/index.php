<?php
// Iniciar sesión para leer mensajes desde el controlador de login
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Si el usuario YA está logueado, redirigir al Dashboard (evita ver login con botón atrás)
if (isset($_SESSION['usuario']) && isset($_SESSION['tipo'])) {
    header('Location: ../app/view/Dashboard.php');
    exit;
}

$mensaje = $_SESSION['login_msg'] ?? '';
$mensajeClase = $_SESSION['login_msg_type'] ?? 'error';
// limpiar para evitar que persista al refrescar
unset($_SESSION['login_msg'], $_SESSION['login_msg_type']);

// Leer flag para ocultar "Crear cuenta" si la instalación ya fue completada
$ocultarCrearCuenta = false;
require_once __DIR__ . '/../app/config/conexion.php';

// 1) Verificar existencia de usuarios: si 0 o falla (sin tablas), redirigir al registro inicial
try {
    if (isset($conexion)) {
        $stmtCount = $conexion->query("SELECT COUNT(*) FROM usuarios");
        $totalUsuarios = (int)$stmtCount->fetchColumn();
        if ($totalUsuarios === 0) {
            header('Location: ../app/view/Crear_Administrador.php');
            exit;
        }
    }
} catch (\Throwable $e) {
    header('Location: ../app/view/Crear_Administrador.php');
    exit;
}

// 2) Leer app_config opcionalmente para ocultar "Crear cuenta"; NO redirigir si falla
try {
    if (isset($conexion)) {
        $stmtCfg = $conexion->prepare("SELECT cfg_value FROM app_config WHERE cfg_key='setup_completed'");
        $stmtCfg->execute();
        $ocultarCrearCuenta = ((string)($stmtCfg->fetchColumn() ?: '0') === '1');
    }
} catch (\Throwable $e) {
    // Ignorar: dejar $ocultarCrearCuenta=false
}
?>
<?php
// Prevenir caché de la página de login para que no se muestre después de logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
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
<link rel="stylesheet" href="css/brand.css">
</head>
<body>
<main class="login-container">
    <form method="post" class="login-form" action="../app/controllers/LoginController.php">
        <div class="brand-header">
            <img src="img/logo_colegio.png" alt="Logo del colegio" class="brand-logo" onerror="this.style.display='none'">
            <div class="brand-text">
                <h1 class="title">Aulas de Innovación</h1>
                <p class="subtitle">Accede a tu cuenta para gestionar reservas y préstamos</p>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?= htmlspecialchars($mensajeClase) ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <label class="sr-only" for="login-email">Correo</label>
        <input id="login-email" type="email" name="correo" placeholder="Correo electrónico" required autocomplete="username">

        <div class="password-field">
            <label class="sr-only" for="login-password">Contraseña</label>
            <input type="password" name="contraseña" id="login-password" placeholder="Contraseña" required autocomplete="current-password">
            <button type="button" class="toggle-password" aria-label="Mostrar/Ocultar contraseña" onclick="togglePassword('login-password')">
                <i class="far fa-eye"></i>
            </button>
        </div>

        <div class="form-row">
            <label class="remember">
                <input type="checkbox" name="remember" value="1"> Recordarme
            </label>
            <a class="forgot" href="recuperar_contraseña.php">¿Olvidaste tu contraseña?</a>
        </div>

        <button type="submit" id="login-submit" class="btn-primary">Ingresar</button>

        <div class="enlaces">
            <?php if (!$ocultarCrearCuenta): ?>
                <a href="../app/view/Registrar_Usuario.php">Crear cuenta</a>
            <?php endif; ?>
        </div>
        <footer class="login-footer">
            <small>© <?= date('Y') ?> Colegio Monseñor Juan Tomis Stack</small>
        </footer>
    </form>
</main>
<script src="js/login.js"></script>
</body>
</html>
