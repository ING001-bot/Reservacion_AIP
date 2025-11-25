<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

// Solo administradores
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'Administrador') {
    http_response_code(403);
    echo json_encode(['error' => true, 'mensaje' => '⛔ Acceso denegado']);
    exit;
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../controllers/UsuarioController.php';

try {
    $controller = new UsuarioController();
    $estadisticas = $controller->obtenerEstadisticas();
    
    echo json_encode([
        'error' => false,
        'estadisticas' => $estadisticas
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'mensaje' => '❌ Error al obtener estadísticas: ' . $e->getMessage()
    ]);
}
