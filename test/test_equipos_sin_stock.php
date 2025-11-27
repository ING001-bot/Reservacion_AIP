<?php
require_once __DIR__ . '/../app/config/conexion.php';
require_once __DIR__ . '/../app/lib/AIService.php';

$_SESSION['usuario_id'] = 1;
$_SESSION['rol'] = 'Administrador';

$ai = new AIService($conexion);

echo "Pregunta: ¿Qué equipos no tienen stock?\n";
echo "Respuesta: \n";
echo $ai->generateResponse('¿Qué equipos no tienen stock?', 'Administrador', 1);
echo "\n";
