<?php
require_once __DIR__ . '/../app/config/conexion.php';
require_once __DIR__ . '/../app/lib/AIService.php';

$ai = new AIService($conexion);

echo "=== TEST DE CONSULTAS RÁPIDAS PARA PROFESOR ===\n\n";

// Test 1: Ayuda
echo "--- Prueba #1 ---\n";
echo "👤 Usuario: ayuda\n\n";
echo "🤖 Tommibot:\n";
$response = $ai->generateResponse('ayuda', 'Profesor', 1);
echo $response;
echo "\n\n================================================================================\n\n";

// Test 2: ¿Qué puedo hacer?
echo "--- Prueba #2 ---\n";
echo "👤 Usuario: ¿qué puedo hacer?\n\n";
echo "🤖 Tommibot:\n";
$response = $ai->generateResponse('¿qué puedo hacer?', 'Profesor', 1);
echo $response;
echo "\n\n================================================================================\n\n";

// Test 3: ¿Cómo hago una reserva?
echo "--- Prueba #3 ---\n";
echo "👤 Usuario: ¿cómo hago una reserva?\n\n";
echo "🤖 Tommibot:\n";
$response = $ai->generateResponse('¿cómo hago una reserva?', 'Profesor', 1);
echo $response;
echo "\n\n================================================================================\n\n";

// Test 4: ¿Cómo solicito un préstamo?
echo "--- Prueba #4 ---\n";
echo "👤 Usuario: ¿cómo solicito un préstamo?\n\n";
echo "🤖 Tommibot:\n";
$response = $ai->generateResponse('¿cómo solicito un préstamo?', 'Profesor', 1);
echo $response;
echo "\n\n================================================================================\n\n";

// Test 5: Pregunta no reconocida
echo "--- Prueba #5 ---\n";
echo "👤 Usuario: xyz123 pregunta aleatoria\n\n";
echo "🤖 Tommibot:\n";
$response = $ai->generateResponse('xyz123 pregunta aleatoria', 'Profesor', 1);
echo $response;
echo "\n\n================================================================================\n\n";

echo "\n✅ Test completado!\n";
