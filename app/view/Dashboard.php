<?php
session_start();

// Si no está logueado, fuera
if (!isset($_SESSION['usuario']) || !isset($_SESSION['tipo'])) {
    header('Location: ../../Public/index.php');
    exit;
}

// Prevenir caché del navegador
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

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
