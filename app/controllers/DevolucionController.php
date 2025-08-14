<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['tipo'] !== 'Encargado') {
    echo "Acceso denegado";
    exit();
}

require 'app/models/PrestamoModel.php';

$model = new PrestamoModel($conexion);

$mensaje = "";

if (isset($_GET['devolver'])) {
    $id = $_GET['devolver'];

    if (!ctype_digit($id)) {
        $mensaje = "❌ ID de préstamo inválido.";
    } else {
        if ($model->devolverEquipo($id)) {
            $mensaje = "✅ Equipo devuelto correctamente.";
        } else {
            $mensaje = "❌ Error al devolver el equipo.";
        }
    }
}

$prestamos = $model->obtenerPrestamosActivos();
