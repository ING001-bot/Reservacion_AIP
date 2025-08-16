<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require '../config/conexion.php';
require '../models/PrestamoModel.php';

$prestamoModel = new PrestamoModel($conexion);

// Inicializar mensajes
$mensaje = '';
$mensaje_tipo = '';

// Procesar POST al enviar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipo = $_POST['equipo'] ?? null;
    $usuario = $_SESSION['usuario'] ?? null;

    if ($equipo && $usuario) {
        $usuarioData = $prestamoModel->obtenerUsuarioPorNombre($usuario);

        if ($usuarioData) {
            $id_usuario = $usuarioData['id_usuario'];
            $fecha = date('Y-m-d');

            $prestamoModel->insertarPrestamo($id_usuario, $equipo, $fecha);
            $prestamoModel->actualizarEstadoEquipo($equipo, 'Prestado');

            $mensaje = "✅ Préstamo registrado correctamente.";
            $mensaje_tipo = 'exito';
        } else {
            $mensaje = "❌ Usuario no encontrado.";
            $mensaje_tipo = 'error';
        }
    } else {
        $mensaje = "⚠️ Debes seleccionar un equipo.";
        $mensaje_tipo = 'error';
    }
}

// Obtener equipos disponibles para mostrar en la vista
$equipos = $prestamoModel->obtenerEquiposDisponibles();
?>
