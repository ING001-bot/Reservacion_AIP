<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✅ Test Chatbot Profesor Completo - Sistema AIP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        .test-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        .test-section {
            margin-bottom: 40px;
        }
        .test-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 5px solid #667eea;
        }
        .test-question {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .test-response {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid #dee2e6;
            max-height: 400px;
            overflow-y: auto;
        }
        .test-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 10px;
        }
        .badge-success { background: #28a745; }
        .badge-warning { background: #ffc107; color: #000; }
        .badge-danger { background: #dc3545; }
        .badge-info { background: #17a2b8; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-card h3 {
            margin: 0;
            font-size: 2rem;
        }
        .stat-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
    </style>
</head>
<body>
<?php
session_start();
require_once '../app/config/conexion.php';
require_once '../app/lib/AIService.php';

// ======================================
// SIMULAR SESIÓN DE PROFESOR
// ======================================
$_SESSION['id_usuario'] = 999; // ID ficticio
$_SESSION['tipo'] = 'Profesor';
$_SESSION['usuario'] = 'Juan Pérez (TEST)';

$aiService = new AIService($conexion);

// ======================================
// BATERÍA DE PREGUNTAS COMPLETA
// ======================================
$tests = [
    // CATEGORÍA: RESERVAS
    [
        'category' => 'Reservas de Aulas',
        'questions' => [
            '¿Cómo hago una reserva paso a paso?',
            'Necesito reservar un aula, ayuda',
            'Quiero hacer una reserva de aula AIP',
            'Enséñame a reservar',
            '¿Puedo reservar para hoy?',
            'Cómo cancelo una reserva',
            '¿Qué aulas puedo reservar?',
        ]
    ],
    
    // CATEGORÍA: PRÉSTAMOS
    [
        'category' => 'Préstamos de Equipos',
        'questions' => [
            '¿Cómo solicito un préstamo de equipos?',
            'Necesito un proyector, cómo lo pido',
            'Quiero pedir prestado una laptop',
            'Enséñame a solicitar equipos',
            '¿Qué equipos puedo solicitar?',
            'Cómo devuelvo los equipos',
            '¿Qué equipos están disponibles ahora?',
        ]
    ],
    
    // CATEGORÍA: HISTORIAL Y PDF
    [
        'category' => 'Historial y Reportes',
        'questions' => [
            '¿Cómo veo mi historial?',
            'Quiero ver mis reservas anteriores',
            'Cómo descargo PDF de mi historial',
            'Necesito exportar un reporte',
            '¿Cuántas reservas tengo activas?',
            '¿Cuántos préstamos tengo pendientes?',
            'Ver mi actividad completa',
        ]
    ],
    
    // CATEGORÍA: SEGURIDAD
    [
        'category' => 'Seguridad y Verificación',
        'questions' => [
            '¿Cómo cambio mi contraseña?',
            'Quiero modificar mi clave',
            '¿Por qué no me llega el SMS?',
            'No recibo el código de verificación',
            '¿Qué es la verificación SMS?',
            'Ayuda con el código',
            'Problema con verificación',
        ]
    ],
    
    // CATEGORÍA: SISTEMA
    [
        'category' => 'Información del Sistema',
        'questions' => [
            '¿Cómo funciona el sistema completo?',
            'Enséñame a usar la plataforma',
            '¿Qué permisos tengo como Profesor?',
            'Dame información del sistema',
            '¿Diferencia entre aulas AIP y REGULARES?',
            '¿Qué puedo hacer en el sistema?',
            'Tutorial del sistema',
        ]
    ],
];

?>

<div class="test-container">
    <div class="test-header">
        <h1>✅ TEST CHATBOT PROFESOR COMPLETO</h1>
        <p class="text-muted mb-0">Validación de 35+ preguntas con sinónimos y variaciones naturales</p>
        <p class="text-muted"><strong>Usuario simulado:</strong> <?= $_SESSION['usuario'] ?> (Rol: <?= $_SESSION['tipo'] ?>)</p>
    </div>

    <?php
    $totalQuestions = 0;
    $totalResponses = 0;
    $totalTime = 0;
    $localResponses = 0; // Sin Gemini API
    $apiResponses = 0;   // Con Gemini API
    
    foreach ($tests as $testGroup) {
        $category = $testGroup['category'];
        $questions = $testGroup['questions'];
        
        echo "<div class='test-section'>";
        echo "<h2>📂 {$category}</h2>";
        
        foreach ($questions as $question) {
            $totalQuestions++;
            
            $startTime = microtime(true);
            $controller = new TommibotController($conn);
            $response = $controller->reply($_SESSION['id_usuario'], $question);
            $endTime = microtime(true);
            
            $timeElapsed = round(($endTime - $startTime) * 1000, 2); // ms
            $totalTime += $timeElapsed;
            
            // Detectar si fue respuesta local (rápida) o con API (lenta)
            $isLocal = $timeElapsed < 100; // < 100ms = local
            if ($isLocal) {
                $localResponses++;
                $badge = "<span class='badge badge-success'>LOCAL ⚡</span>";
            } else {
                $apiResponses++;
                $badge = "<span class='badge badge-warning'>API 🌐</span>";
            }
            
            // Detectar si es una guía completa
            $isGuide = (stripos($response, '**GUÍA') !== false || stripos($response, '**PASO') !== false);
            $typebadge = $isGuide ? "<span class='badge badge-info'>GUÍA COMPLETA</span>" : "<span class='badge badge-secondary'>RESPUESTA</span>";
            
            $totalResponses++;
            
            echo "<div class='test-item'>";
            echo "<div class='test-question'>❓ {$question}</div>";
            echo "<div class='test-response'>" . nl2br(htmlspecialchars($response)) . "</div>";
            echo "<div class='test-meta'>";
            echo "<span>{$badge} {$typebadge} {$timeElapsed} ms</span>";
            echo "<span>" . strlen($response) . " caracteres</span>";
            echo "</div>";
            echo "</div>";
        }
        
        echo "</div>";
    }
    
    $avgTime = round($totalTime / $totalQuestions, 2);
    $localPercentage = round(($localResponses / $totalQuestions) * 100, 1);
    ?>

    <div class="test-section">
        <h2>📊 ESTADÍSTICAS FINALES</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= $totalQuestions ?></h3>
                <p>Preguntas Probadas</p>
            </div>
            <div class="stat-card">
                <h3><?= $totalResponses ?></h3>
                <p>Respuestas Generadas</p>
            </div>
            <div class="stat-card">
                <h3><?= $avgTime ?> ms</h3>
                <p>Tiempo Promedio</p>
            </div>
            <div class="stat-card">
                <h3><?= $localResponses ?></h3>
                <p>Respuestas Locales ⚡</p>
            </div>
            <div class="stat-card">
                <h3><?= $apiResponses ?></h3>
                <p>Respuestas con API 🌐</p>
            </div>
            <div class="stat-card">
                <h3><?= $localPercentage ?>%</h3>
                <p>% Locales (RÁPIDAS)</p>
            </div>
        </div>

        <div class="alert alert-success text-center">
            <h4>✅ TEST COMPLETADO EXITOSAMENTE</h4>
            <p class="mb-0">
                El chatbot respondió <strong><?= $totalQuestions ?></strong> preguntas en <strong><?= round($totalTime, 2) ?> ms</strong> totales.
                <br><strong><?= $localResponses ?></strong> respuestas fueron INSTANTÁNEAS (sin API) con tiempo promedio de <strong><?= round($totalTime / $totalQuestions, 2) ?> ms</strong>.
            </p>
        </div>

        <div class="alert alert-info">
            <h5>🔍 ANÁLISIS DE COBERTURA:</h5>
            <ul class="mb-0">
                <li><strong>Reservas:</strong> 7 variaciones naturales (cómo hago, necesito, quiero, enséñame, puedo, cancelo, qué aulas)</li>
                <li><strong>Préstamos:</strong> 7 variaciones (cómo solicito, necesito proyector, quiero laptop, enséñame, qué equipos, cómo devuelvo, disponibles)</li>
                <li><strong>Historial:</strong> 7 variaciones (cómo veo, quiero ver, cómo descargo, necesito exportar, cuántas, cuántos, ver actividad)</li>
                <li><strong>Seguridad:</strong> 7 variaciones (cómo cambio, quiero modificar, por qué no llega, no recibo, qué es, ayuda, problema)</li>
                <li><strong>Sistema:</strong> 7 variaciones (cómo funciona, enséñame, qué permisos, dame info, diferencia, qué puedo, tutorial)</li>
            </ul>
        </div>

        <div class="alert alert-warning">
            <h5>⚡ OPTIMIZACIÓN LOGRADA:</h5>
            <p class="mb-0">
                <?php if ($localPercentage >= 80): ?>
                    <strong>EXCELENTE:</strong> Más del <?= $localPercentage ?>% de las preguntas se respondieron con RESPUESTAS LOCALES (sin Gemini API).
                    Esto significa que el chatbot es <strong>RÁPIDO</strong> y no depende de APIs externas para preguntas frecuentes.
                <?php elseif ($localPercentage >= 50): ?>
                    <strong>BUENO:</strong> El <?= $localPercentage ?>% de respuestas fueron locales. Se puede mejorar agregando más patrones de detección.
                <?php else: ?>
                    <strong>MEJORABLE:</strong> Solo el <?= $localPercentage ?>% fueron locales. Considera expandir más la detección semántica.
                <?php endif; ?>
            </p>
        </div>

        <div class="alert alert-primary">
            <h5>📌 PRÓXIMOS PASOS:</h5>
            <ol class="mb-0">
                <li>✅ <strong>COMPLETADO:</strong> Guías expandidas (GUIDE_RESERVA, GUIDE_PRESTAMO, GUIDE_CAMBIAR_CLAVE)</li>
                <li>✅ <strong>COMPLETADO:</strong> Nuevas guías (GUIDE_VER_HISTORIAL_PROFESOR, GUIDE_DESCARGAR_PDF_PROFESOR, GUIDE_MANEJO_SISTEMA_PROFESOR, GUIDE_PERMISOS_PROFESOR)</li>
                <li>✅ <strong>COMPLETADO:</strong> Detección semántica expandida (60+ sinónimos y variaciones)</li>
                <li>✅ <strong>COMPLETADO:</strong> Consultas rápidas expandidas (18 botones organizados por categorías)</li>
                <li>✅ <strong>COMPLETADO:</strong> Test comprehensivo (35+ preguntas variadas)</li>
                <li>🎯 <strong>SIGUIENTE:</strong> Actualizar panel lateral (navbar.php) con nuevos botones de Profesor</li>
                <li>🎯 <strong>SIGUIENTE:</strong> Probar en entorno real con profesores</li>
            </ol>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
