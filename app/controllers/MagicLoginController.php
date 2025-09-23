<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../models/UsuarioModel.php';

$token = $_GET['token'] ?? '';
$mensaje = '';
$tipo = 'error';

try {
    if (!$token) {
        throw new Exception('Falta el token.');
    }
    $model = new UsuarioModel();
    $user = $model->obtenerPorLoginToken($token);
    if (!$user) {
        throw new Exception('Enlace inválido o expirado.');
    }
    // Consumir token (dejarlo inútil)
    $model->limpiarLoginToken($user['correo']);

    // Iniciar sesión
    session_regenerate_id(true);
    $_SESSION['id_usuario'] = $user['id_usuario'];
    $_SESSION['usuario']    = $user['nombre'];
    $_SESSION['correo']     = $user['correo'];
    $_SESSION['tipo']       = $user['tipo_usuario'] ?? '';

    // Mensaje amigable y sugerencia de cambio de contraseña
    $_SESSION['login_msg'] = '✅ Accediste con enlace temporal. Por seguridad te sugerimos cambiar tu contraseña ahora.';
    $_SESSION['login_msg_type'] = 'success';

    // Redirección según rol
    if (($_SESSION['tipo'] ?? '') === 'Administrador') {
        header('Location: ../view/Admin.php');
    } else {
        // Si tienes un dashboard específico para usuario/profesor, ajusta esta ruta
        header('Location: ../view/Dashboard.php');
    }
    exit;
} catch (Throwable $e) {
    $_SESSION['login_msg'] = '❌ ' . $e->getMessage();
    $_SESSION['login_msg_type'] = 'error';
    header('Location: ../../Public/index.php');
    exit;
}
