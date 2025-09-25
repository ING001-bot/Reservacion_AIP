<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../controllers/HistorialGlobalController.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['tipo'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$rol = $_SESSION['tipo']; // 'Administrador' | 'Encargado' | 'Profesor' ...
$controller = new HistorialGlobalController($conexion);

// Filtros: solo permitidos para Administrador
$opts = [];
if ($rol === 'Administrador') {
    $opts['desde']    = isset($_GET['desde']) && $_GET['desde'] !== '' ? $_GET['desde'] : null;
    $opts['hasta']    = isset($_GET['hasta']) && $_GET['hasta'] !== '' ? $_GET['hasta'] : null;
    $opts['profesor'] = isset($_GET['profesor']) && $_GET['profesor'] !== '' ? $_GET['profesor'] : null;
    $opts['tipo']     = isset($_GET['tipo']) && $_GET['tipo'] !== '' ? $_GET['tipo'] : null; // reserva | prestamo
    $opts['estado']   = isset($_GET['estado']) && $_GET['estado'] !== '' ? $_GET['estado'] : null;
}

try {
    $data = $controller->listarHistorial($opts);
    echo json_encode([
        'ok'   => true,
        'rol'  => $rol,
        'data' => $data
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
