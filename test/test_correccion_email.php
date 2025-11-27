<?php
require_once __DIR__ . '/../app/config/conexion.php';
require_once __DIR__ . '/../app/lib/AIService.php';

$ai = new AIService($conexion);

echo "=== TEST: VERIFICACIÓN DE CORRECCIONES ===\n\n";

echo "--- Test #1: ¿Qué roles existen? ---\n";
$response = $ai->generateResponse('¿Qué roles existen?', 'Administrador', 1);
echo $response;
echo "\n\n";

// Verificar que contenga "email" y NO "WhatsApp"
if (strpos($response, 'email') !== false || strpos($response, 'correo electrónico') !== false) {
    echo "✅ CORRECTO: Menciona 'email' o 'correo electrónico'\n";
} else {
    echo "❌ ERROR: No menciona 'email'\n";
}

if (strpos($response, 'WhatsApp') !== false) {
    echo "❌ ERROR: Todavía menciona 'WhatsApp'\n";
} else {
    echo "✅ CORRECTO: No menciona 'WhatsApp'\n";
}

echo "\n=== FIN DEL TEST ===\n";
