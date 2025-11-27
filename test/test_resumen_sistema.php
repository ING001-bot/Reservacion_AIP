<?php
require_once __DIR__ . '/../app/config/conexion.php';
require_once __DIR__ . '/../app/lib/AIService.php';

$ai = new AIService($conexion);

echo "========================================\n";
echo "TEST: RESUMEN DEL SISTEMA\n";
echo "========================================\n\n";

$response = $ai->generateResponse('resumen del sistema', 'Administrador', 1);
echo $response;
echo "\n\n========================================\n";
echo "✅ Test completado\n";
echo "========================================\n";
