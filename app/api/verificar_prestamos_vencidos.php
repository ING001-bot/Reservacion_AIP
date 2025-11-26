<?php
// app/api/verificar_prestamos_vencidos.php
// Endpoint para verificar préstamos vencidos (usado por AJAX polling)

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../lib/AlertService.php';

$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$tipo_usuario = $_SESSION['tipo'] ?? '';

if (!$id_usuario) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

// Solo Encargados y Administradores pueden consultar esto
if (!in_array($tipo_usuario, ['Encargado', 'Administrador'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado']);
    exit;
}

try {
    $alertService = new \App\Lib\AlertService($conexion);
    $vencidos = $alertService->verificarPrestamosVencidos();
    
    echo json_encode([
        'ok' => true,
        'total' => $vencidos['total'],
        'prestamos' => $vencidos['prestamos'],
        'packs' => $vencidos['packs']
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error al verificar préstamos vencidos',
        'detalle' => $e->getMessage()
    ]);
}
