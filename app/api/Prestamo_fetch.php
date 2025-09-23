<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config/conexion.php';
require '../controllers/PrestamoController.php';

$controller = new PrestamoController($conexion);
$data = $controller->obtenerTodosPrestamos();

header('Content-Type: application/json');
echo json_encode($data);
