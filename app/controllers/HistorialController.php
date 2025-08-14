<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

require 'app/models/HistorialModel.php';

$id_usuario = $_SESSION['id_usuario'] ?? 0;

$model = new HistorialModel();

$datos_usuario = $model->obtenerDatosUsuario($id_usuario);
if (!$datos_usuario) {
    die("❌ No se encontraron datos del usuario.");
}

$reservas = $model->obtenerReservas($id_usuario);
$prestamos = $model->obtenerPrestamos($id_usuario);

// Pasar $datos_usuario, $reservas, $prestamos a la vista (incluida abajo)
require 'app/views/historialView.php';
