<?php
require '../models/UsuarioModel.php';

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
            $nueva_contraseña = substr(bin2hex(random_bytes(8)), 0, 8);
            $hash = password_hash($nueva_contraseña, PASSWORD_DEFAULT);

            $actualizado = $usuarioModel->actualizarContraseña($correo, $hash);

            if ($actualizado) {
                // Aquí se simula el envío de correo
                // mail($correo, "Recuperación de contraseña", "Tu nueva contraseña temporal es: $nueva_contraseña");

                $mensaje = "✅ Tu nueva contraseña temporal es: <b>" . htmlspecialchars($nueva_contraseña) . "</b><br>Por favor, cámbiala después de iniciar sesión.";
                $mensaje_tipo = 'exito';
            } else {
                $mensaje = "❌ Error al actualizar la contraseña.";
                $mensaje_tipo = 'error';
            }
        } else {
            $mensaje = "❌ No se encontró ninguna cuenta con ese correo.";
            $mensaje_tipo = 'error';
        }
    }
}

require 'app/views/recuperar_contrasena.view.php';
