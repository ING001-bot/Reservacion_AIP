<?php
session_start();
require_once __DIR__ . '/../app/config/conexion.php';
require_once __DIR__ . '/../app/lib/AIService.php';
require_once __DIR__ . '/../app/controllers/TommibotController.php';

echo "=== TEST: MENSAJE DE BIENVENIDA DEL CHATBOT ===\n\n";

// Test 1: Admin abriendo chatbot (mensaje vacío)
echo "--- Test #1: ADMIN abriendo chatbot ---\n";
$_SESSION['tipo'] = 'Administrador';
$_SESSION['usuario'] = 'Admin Test';
$_SESSION['usuario_id'] = 1;

$controller = new TommibotController($conexion);
$response = $controller->reply(1, '', 'text');
echo $response;
echo "\n\n================================================================================\n\n";

// Test 2: Profesor abriendo chatbot (mensaje vacío)
echo "--- Test #2: PROFESOR abriendo chatbot ---\n";
$_SESSION['tipo'] = 'Profesor';
$_SESSION['usuario'] = 'Profesor Test';
$_SESSION['usuario_id'] = 2;

$controller = new TommibotController($conexion);
$response = $controller->reply(2, '', 'text');
echo $response;
echo "\n\n================================================================================\n\n";

// Test 3: Encargado abriendo chatbot (mensaje vacío)
echo "--- Test #3: ENCARGADO abriendo chatbot ---\n";
$_SESSION['tipo'] = 'Encargado';
$_SESSION['usuario'] = 'Encargado Test';
$_SESSION['usuario_id'] = 3;

$controller = new TommibotController($conexion);
$response = $controller->reply(3, '', 'text');
echo $response;
echo "\n\n================================================================================\n\n";

echo "✅ Test completado!\n";
