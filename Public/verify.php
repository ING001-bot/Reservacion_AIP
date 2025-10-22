<?php
require_once __DIR__ . '/../app/init.php';

// Verificar si la sesión está activa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener la conexión a la base de datos
require_once __DIR__ . '/../config/conexion.php';

// Crear instancia del controlador
$verificationController = new VerificationController($conexion);

// Manejar la solicitud
$verificationController->handleRequest();
