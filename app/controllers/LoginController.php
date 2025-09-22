<?php
session_start();
require '../models/UsuarioModel.php';

$usuarioModel = new UsuarioModel();
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    $contraseña = isset($_POST['contraseña']) ? $_POST['contraseña'] : '';

    if ($correo === '' || $contraseña === '') {
        // Guardar mensaje en sesión y redirigir de vuelta al login
        $_SESSION['login_msg'] = "⚠️ Por favor, completa todos los campos.";
        $_SESSION['login_msg_type'] = 'error';
        header('Location: ../../Public/index.php');
        exit();
    } else {
        $user = $usuarioModel->obtenerPorCorreo($correo);

        if ($user && password_verify($contraseña, $user['contraseña'])) {
            if (isset($user['activo']) && (int)$user['activo'] !== 1) {
                $_SESSION['login_msg'] = "⛔ Tu cuenta está dada de baja. Contacta al administrador.";
                $_SESSION['login_msg_type'] = 'error';
                header('Location: ../../Public/index.php');
                exit();
            }
            if (isset($user['verificado']) && (int)$user['verificado'] !== 1 && ($user['tipo_usuario'] ?? '') !== 'Administrador') {
                $_SESSION['login_msg'] = "✉️ Debes verificar tu correo antes de iniciar sesión. Revisa tu bandeja o solicita reenviar verificación.";
                $_SESSION['login_msg_type'] = 'error';
                header('Location: ../../Public/index.php');
                exit();
            }
            session_regenerate_id(true);
            $_SESSION['id_usuario'] = $user['id_usuario'];
            $_SESSION['usuario'] = $user['nombre'];
            $_SESSION['correo'] = $user['correo'];
            $_SESSION['tipo'] = $user['tipo_usuario'];

            header('Location: ../view/Dashboard.php');
            exit();
        } else {
            // Mensaje de error y retorno al login
            $_SESSION['login_msg'] = "❌ Credenciales incorrectas. Verifica tu correo y contraseña.";
            $_SESSION['login_msg_type'] = 'error';
            header('Location: ../../Public/index.php');
            exit();
        }
    }
}
// Si llega por GET u otra vía, volver al login
header('Location: ../../Public/index.php');
exit();
