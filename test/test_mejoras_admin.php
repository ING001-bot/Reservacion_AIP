<?php
/**
 * Test de las nuevas mejoras del chatbot de Administrador
 */

require_once __DIR__ . '/../app/config/conexion.php';
require_once __DIR__ . '/../app/lib/AIService.php';

echo "=== TEST DE MEJORAS DEL CHATBOT DE ADMINISTRADOR ===\n\n";

$_SESSION['usuario_id'] = 1;
$_SESSION['rol'] = 'Administrador';

$ai = new AIService($conexion);

$tests = [
    "ayuda",
    "¿qué puedo hacer?",
    "¿cómo registrar un usuario?",
    "¿cómo usar el sistema?",
    "dame información del sistema",
    "pregunta no reconocida xyz123"
];

foreach ($tests as $index => $question) {
    echo "--- Prueba #" . ($index + 1) . " ---\n";
    echo "👤 Usuario: $question\n\n";
    
    try {
        $response = $ai->generateResponse($question, 'Administrador', 1);
        echo "🤖 Tommibot:\n";
        echo $response . "\n\n";
        echo str_repeat("=", 80) . "\n\n";
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    }
}

echo "\n✅ Test completado!\n";
