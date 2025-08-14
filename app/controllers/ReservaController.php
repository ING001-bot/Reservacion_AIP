<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

require 'app/config/conexion.php';
require 'app/models/ReservaModel.php';
require 'app/models/AulaModel.php';

$error = '';
$exito = '';

$reservaModel = new ReservaModel($conexion);
$aulaModel = new AulaModel($conexion);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_aula = isset($_POST['id_aula']) ? intval($_POST['id_aula']) : 0;
    $fecha = $_POST['fecha'] ?? '';
    $hora_inicio = $_POST['hora_inicio'] ?? '';
    $hora_fin = $_POST['hora_fin'] ?? '';

    if ($id_aula === 0 || $fecha === '' || $hora_inicio === '' || $hora_fin === '') {
        $error = "⚠️ Por favor complete todos los campos.";
    } else {
        try {
            $reservaModel->crearReserva($_SESSION['id_usuario'], $id_aula, $fecha, $hora_inicio, $hora_fin);
            $exito = "✅ Reserva realizada con éxito.";
        } catch (PDOException $e) {
            $error = "❌ Error al reservar: " . $e->getMessage();
        }
    }
}

try {
    $aulas = $aulaModel->obtenerTodas();
} catch (PDOException $e) {
    $error = "❌ Error al cargar aulas: " . $e->getMessage();
}

require 'app/views/reservas/reservar_view.php';
