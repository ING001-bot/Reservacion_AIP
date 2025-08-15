<?php
session_start();

require '../models/UsuarioModel.php';
require '../models/EquipoModel.php';

$usuarioModel = new UsuarioModel();
$equipoModel = new EquipoModel();

$mensaje = '';
$mensaje_tipo = '';

$usuarioExterno = isset($_GET['modo']) && $_GET['modo'] === 'registro';
$rol = $_SESSION['tipo'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['registrar_usuario'])) {
        $nombre = trim($_POST['nombre']);
        $correo = filter_input(INPUT_POST, 'correo', FILTER_VALIDATE_EMAIL);
        $contraseña_raw = $_POST['contraseña'] ?? '';
        $tipo = ($rol === 'Administrador') ? $_POST['tipo'] : 'Profesor';
        $tipos_validos = ['Profesor', 'Encargado', 'Administrador'];

        if (!$correo || !in_array($tipo, $tipos_validos) || strlen($contraseña_raw) < 6) {
            $mensaje = "❌ Datos inválidos.";
            $mensaje_tipo = "error";
        } elseif ($usuarioModel->existeCorreo($correo)) {
            $mensaje = "⚠ El correo ya está registrado.";
            $mensaje_tipo = "error";
        } else {
            $hash = password_hash($contraseña_raw, PASSWORD_DEFAULT);
            $usuarioModel->registrar($nombre, $correo, $hash, $tipo);
            $mensaje = "✅ Usuario registrado correctamente.";
            $mensaje_tipo = "success";
        }
    }

    if (isset($_POST['registrar_equipo']) && $rol === 'Administrador') {
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
