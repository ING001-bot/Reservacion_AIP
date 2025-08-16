<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


require '../models/UsuarioModel.php';
require '../models/EquipoModel.php';

$usuarioModel = new UsuarioModel();
$equipoModel = new EquipoModel();

$mensaje = '';
$mensaje_tipo = '';

$usuarioExterno = isset($_GET['modo']) && in_array($_GET['modo'], ['registro', 'externo']);
$rol = $_SESSION['tipo'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

   if (isset($_POST['registrar_usuario'])) {
        $nombre = trim($_POST['nombre']);
        $correo = filter_input(INPUT_POST, 'correo', FILTER_VALIDATE_EMAIL);
        $contraseña_raw = $_POST['contraseña'] ?? '';

        // CORRECCIÓN: Tomar el rol solo si se envía desde el formulario
        $tipo = 'Profesor'; // valor por defecto
        if (isset($_POST['tipo']) && in_array($_POST['tipo'], ['Profesor','Encargado','Administrador'])) {
            $tipo = $_POST['tipo'];
        }

        if (!$correo || strlen($contraseña_raw) < 6) {
            $mensaje = "❌ Datos inválidos.";
            $mensaje_tipo = "error";
        } elseif ($usuarioModel->existeCorreo($correo)) {
            $mensaje = "⚠ El correo ya está registrado.";
            $mensaje_tipo = "error";
        } else {
            $hash = password_hash($contraseña_raw, PASSWORD_DEFAULT);
            $usuarioModel->registrar($nombre, $correo, $hash, $tipo);
            $mensaje = "✅ Usuario registrado correctamente con rol $tipo.";
            $mensaje_tipo = "success";
        }
    }


    if (isset($_POST['registrar_equipo']) ) {
        
        $nombre_equipo = trim($_POST['nombre_equipo']);
        $tipo_equipo = trim($_POST['tipo_equipo']);

        if ($nombre_equipo && $tipo_equipo) {
            $equipoModel->registrarEquipo($nombre_equipo, $tipo_equipo);
            $mensaje = "✅ Equipo registrado correctamente.";
            $mensaje_tipo = "success";
        } else {
            $mensaje = "⚠ Todos los campos son obligatorios para el equipo.";
            $mensaje_tipo = "error";
        }
    }
}

// Cargar la vista con las variables ya definidas
require '../view/Admin.php';
