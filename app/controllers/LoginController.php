<?php

session_start();
require '../models/UsuarioModel.php';

$usuarioModel = new UsuarioModel();

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    $contraseña = isset($_POST['contraseña']) ? $_POST['contraseña'] : '';

    if ($correo === '' || $contraseña === '') {
        $mensaje = "⚠️ Por favor, completa todos los campos.";
    } else {
        $user = $usuarioModel->obtenerPorCorreo($correo);
        var_dump($user);

        if ($user && password_verify($contraseña, $user['contraseña'])) {
            session_regenerate_id(true);
            $_SESSION['id_usuario'] = $user['id_usuario'];
            $_SESSION['usuario'] = $user['nombre'];
            $_SESSION['tipo'] = $user['tipo_usuario'];

            header('Location: ../view/Dashboard.php');
            exit();
        } else {
            $mensaje = "❌ Credenciales incorrectas.";
        }
    }
}
?>
