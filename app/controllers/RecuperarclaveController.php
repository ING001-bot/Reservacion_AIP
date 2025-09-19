<?php
require '../models/UsuarioModel.php';
require_once __DIR__ . '/../lib/Mailer.php';
use App\Lib\Mailer;

$mensaje = '';
$mensaje_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo']);

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "⚠️ Por favor, introduce un correo válido.";
        $mensaje_tipo = 'error';
    } else {
        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->buscarPorCorreo($correo);

        if ($usuario) {
            // Generar token de restablecimiento
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', time() + 60*60); // 1 hora
            $usuarioModel->guardarResetToken($correo, $token, $expira);

            // Enviar correo con enlace de restablecimiento
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
            $path = $base . '/RestablecerController.php';
            $link = sprintf('%s://%s%s?token=%s', $scheme, $host, $path, urlencode($token));

            $mailer = new Mailer();
            $subject = 'Restablece tu contraseña - Aulas de Innovación';
            $html = '<p>Hola,</p>' .
                    '<p>Recibimos una solicitud para restablecer tu contraseña.</p>' .
                    '<p>Puedes crear una nueva contraseña haciendo clic en el siguiente enlace:</p>' .
                    '<p><a href="' . htmlspecialchars($link) . '">Restablecer contraseña</a></p>' .
                    '<p>Este enlace expirará en 1 hora. Si no solicitaste este cambio, ignora este mensaje.</p>';
            $mailer->send($correo, $subject, $html);

            $mensaje = "✅ Te enviamos un enlace para restablecer tu contraseña. Revisa tu correo.";
            $mensaje_tipo = 'exito';
        } else {
            $mensaje = "❌ No se encontró ninguna cuenta con ese correo.";
            $mensaje_tipo = 'error';
        }
    }
}

// Puedes redirigir a una vista o mostrar un mensaje básico
header('Content-Type: text/html; charset=UTF-8');
echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Recuperar contraseña</title>' .
     '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4">';
echo '<div class="container"><div class="alert alert-' . ($mensaje_tipo==='exito'?'success':'danger') . '">' . $mensaje . '</div>';
echo '<a class="btn btn-brand" href="../../Public/index.php">Volver al inicio</a></div></body></html>';
