<?php
session_start();
require 'app/models/UsuarioModel.php'; // El modelo donde se realiza la verificación y actualización de la contraseña

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual = $_POST['actual'] ?? '';
    $nueva = $_POST['nueva'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if (!$actual || !$nueva || !$confirmar) {
        $error = "⚠️ Complete todos los campos.";
    } elseif ($nueva !== $confirmar) {
        $error = "⚠️ La nueva contraseña no coincide con la confirmación.";
    } else {
        // Verificar la contraseña actual y actualizarla
        $usuarioModel = new UsuarioModel($conexion);
        $usuario = $usuarioModel->obtenerPorCorreo($_SESSION['usuario']); // Obtenemos los datos del usuario

        if ($usuario && password_verify($actual, $usuario['contraseña'])) {
            // Si la contraseña actual es correcta, actualizarla
            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            if ($usuarioModel->actualizarContraseña($hash, $_SESSION['usuario'])) {
                $exito = "✅ Contraseña actualizada con éxito.";
            } else {
                $error = "❌ Error al actualizar la contraseña.";
            }
        } else {
            $error = "❌ La contraseña actual es incorrecta.";
        }
    }
}
