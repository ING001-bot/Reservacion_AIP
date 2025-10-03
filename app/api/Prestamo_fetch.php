<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/conexion.php';
require_once '../controllers/PrestamoController.php';

$controller = new PrestamoController($conexion);
$data = $controller->obtenerTodosPrestamos();

header('Content-Type: application/json');
echo json_encode($data);
