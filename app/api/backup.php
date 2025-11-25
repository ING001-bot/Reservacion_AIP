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
require_once __DIR__ . '/../controllers/BackupController.php';

$controller = new BackupController();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'crear':
            $resultado = $controller->crearBackup();
            echo json_encode($resultado);
            break;
            
        case 'listar':
            $backups = $controller->listarBackups();
            echo json_encode(['error' => false, 'backups' => $backups]);
            break;
            
        case 'restaurar':
            $filename = $_POST['filename'] ?? '';
            if (empty($filename)) {
                echo json_encode(['error' => true, 'mensaje' => '⚠️ Archivo no especificado']);
                exit;
            }
            $resultado = $controller->restaurarBackup($filename);
            echo json_encode($resultado);
            break;
            
        case 'eliminar':
            $filename = $_POST['filename'] ?? '';
            if (empty($filename)) {
                echo json_encode(['error' => true, 'mensaje' => '⚠️ Archivo no especificado']);
                exit;
            }
            $resultado = $controller->eliminarBackup($filename);
            echo json_encode($resultado);
            break;
            
        case 'limpiar':
            $mantener = (int)($_POST['mantener'] ?? 10);
            $resultado = $controller->limpiarBackups($mantener);
            echo json_encode($resultado);
            break;
            
        case 'descargar':
            $filename = $_GET['filename'] ?? '';
            if (empty($filename)) {
                http_response_code(400);
                echo json_encode(['error' => true, 'mensaje' => '⚠️ Archivo no especificado']);
                exit;
            }
            $controller->descargarBackup($filename);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => true, 'mensaje' => '⚠️ Acción no válida']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'mensaje' => '❌ Error del servidor: ' . $e->getMessage()
    ]);
}
