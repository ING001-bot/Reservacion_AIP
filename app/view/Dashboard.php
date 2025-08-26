<?php
// app/view/Dashboard.php
session_start();

// Si no está logueado, fuera
if (!isset($_SESSION['usuario']) || !isset($_SESSION['tipo'])) {
    header('Location: ../../Public/index.php');
    exit;
}

$rol = $_SESSION['tipo'];

switch ($rol) {
    case 'Administrador':
        header('Location: Admin.php');
        break;
    case 'Encargado':
        header('Location: Encargado.php');
        break;
    case 'Profesor':
    default:
        header('Location: Profesor.php');
        break;
}
exit;
