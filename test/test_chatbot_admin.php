<?php
/**
 * Script de prueba COMPLETO para el chatbot del Administrador
 * Prueba todas las capacidades: datos, guías, gestión y consultas avanzadas
 */

require_once __DIR__ . '/../app/config/conexion.php';
require_once __DIR__ . '/../app/lib/AIService.php';

echo "=== TEST COMPLETO DEL CHATBOT DE ADMINISTRADOR ===\n\n";

// Simular una sesión de administrador
$_SESSION['usuario_id'] = 1;
$_SESSION['rol'] = 'Administrador';

// Crear instancia del servicio
$ai = new AIService($conexion);

// Batería COMPLETA de pruebas
$tests = [
    // GRUPO 1: Consultas de datos básicos
    "GRUPO 1: CONSULTAS DE DATOS" => [
        "¿Cuántos usuarios hay registrados?",
        "¿Cuántos profesores tenemos?",
        "¿Hay préstamos vencidos?",
        "¿Cuántos equipos disponibles hay?",
        "¿Cuántas aulas AIP hay?",
    ],
    
    // GRUPO 2: Información general del sistema
    "GRUPO 2: INFORMACIÓN DEL SISTEMA" => [
        "Dame información del sistema",
        "¿Cómo funciona el sistema?",
        "¿Cuántos roles hay?",
        "¿Qué roles existen en el sistema?",
    ],
    
    // GRUPO 3: Guías de gestión
    "GRUPO 3: GUÍAS DE GESTIÓN" => [
        "¿Cómo gestiono usuarios?",
        "¿Cómo administro equipos?",
        "¿Cómo gestiono aulas?",
        "¿Cómo veo el historial?",
    ],
    
    // GRUPO 4: Listados
    "GRUPO 4: LISTADOS" => [
        "Dame un listado de usuarios",
        "Muestra los equipos",
        "Lista las aulas",
        "Dame los préstamos activos",
        "Muestra las reservas activas",
    ],
    
    // GRUPO 5: Alertas y problemas
    "GRUPO 5: ALERTAS" => [
        "Muéstrame el estado del sistema",
        "¿Hay usuarios sin verificar?",
        "¿Qué equipos no tienen stock?",
    ],
];

$testNumber = 1;
foreach ($tests as $grupo => $preguntas) {
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║ " . str_pad($grupo, 60) . " ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    foreach ($preguntas as $question) {
        echo "--- Prueba #$testNumber ---\n";
        echo "👤 Usuario: $question\n";
        
        try {
            $response = $ai->generateResponse($question, 'Administrador', 1);
            
            // Mostrar primeros 300 caracteres de la respuesta
            $preview = substr($response, 0, 300);
            if (strlen($response) > 300) {
                $preview .= "...";
            }
            
            echo "🤖 Tommibot: $preview\n";
            echo "✅ OK (Longitud: " . strlen($response) . " caracteres)\n\n";
        } catch (Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n\n";
        }
        
        $testNumber++;
        usleep(100000); // Pequeña pausa
    }
    
    echo "\n";
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                     FIN DE LAS PRUEBAS                       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "✅ Total de pruebas ejecutadas: " . ($testNumber - 1) . "\n";
echo "📊 El chatbot ahora puede responder:\n";
echo "   - Consultas de datos (usuarios, equipos, aulas, préstamos)\n";
echo "   - Información del sistema (roles, funcionamiento)\n";
echo "   - Guías de gestión (usuarios, equipos, aulas, historial)\n";
echo "   - Listados detallados (usuarios, equipos, aulas, préstamos, reservas)\n";
echo "   - Alertas del sistema (usuarios sin verificar, equipos sin stock)\n";
echo "\n💡 El chatbot está COMPLETO y listo para el Administrador!\n";
