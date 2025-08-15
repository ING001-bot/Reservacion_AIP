<?php

require '../models/UsuarioModel.php';

$mensaje = '';
$mensaje_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $pass = $_POST['contraseña'];
    $tipo = $_POST['tipo_usuario'];

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "⚠️ Por favor, introduce un correo válido.";
        $mensaje_tipo = 'error';
    } elseif (strlen($pass) < 6) {
        $mensaje = "⚠️ La contraseña debe tener al menos 6 caracteres.";
        $mensaje_tipo = 'error';
    } elseif (empty($nombre) || empty($tipo)) {
        $mensaje = "⚠️ Todos los campos son obligatorios.";
        $mensaje_tipo = 'error';
    } else {
        $usuarioModel = new UsuarioModel();
        $existe = $usuarioModel->buscarPorCorreo($correo);

        if ($existe) {
            $mensaje = "❌ Ya existe un usuario con este correo.";
            $mensaje_tipo = 'error';
        } else {
            $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
            $registrado = $usuarioModel->registrar($nombre, $correo, $pass_hash, $tipo);

            if ($registrado) {
                $mensaje = "✅ Usuario registrado correctamente.";
                $mensaje_tipo = 'exito';
            } else {
                $mensaje = "❌ Error al registrar usuario.";
                $mensaje_tipo = 'error';
            }
        }
    }
}
require '../view/Registro.php';
