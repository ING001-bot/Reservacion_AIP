<?php
/**
 * Servicio de Inteligencia Artificial para Tommibot
 * Utiliza Google Gemini API (tier gratuito)
 */

class AIService {
    private $config;
    private $cache = [];
    private $systemContext;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/ai_config.php';
        $this->initializeSystemContext();
    }
    
    /**
     * Inicializa el contexto del sistema para que la IA entienda el dominio
     */
    private function initializeSystemContext() {
        $this->systemContext = "Eres Tommibot, un asistente virtual juvenil y amable para el Sistema de Reservas y Préstamos del Colegio Juan Tomis Stack. " .
            "Tu tarea es ayudar a los usuarios (profesores, administradores y encargados) con sus consultas sobre el sistema. " .
            "El sistema permite: hacer reservas de aulas, solicitar préstamos de equipos (laptop, proyector, extensión), ver historial, cancelar reservas, cambiar contraseña. " .
            "REGLAS IMPORTANTES: " .
            "1. Todas las reservas y préstamos requieren mínimo 1 día de anticipación. " .
            "2. Se requiere verificación SMS antes de reservar, pedir préstamo o cambiar contraseña. " .
            "3. Las reservas solo se pueden cancelar el mismo día. " .
            "4. La devolución la registra el Encargado tras inspección física. " .
            "5. Los PDFs se envían automáticamente al correo del docente. " .
            "Responde de forma clara, concisa y con tono juvenil pero profesional. " .
            "Si te preguntan algo fuera del sistema (clima, noticias, curiosidades), responde brevemente y vuelve a ofrecer ayuda con el sistema.";
    }
    
    /**
     * Genera una respuesta usando IA (Google Gemini)
     */
    public function generateResponse($userMessage, $userRole = 'Profesor', $useSystemContext = true) {
        // Verificar si la IA está habilitada
        if (!$this->config['gemini']['enabled']) {
            return null;
        }
        
        // Verificar API Key
        if ($this->config['gemini']['api_key'] === 'TU_API_KEY_AQUI') {
            return null; // No configurada, fallback a KB
        }
        
        // Verificar cache
        $cacheKey = md5($userMessage . $userRole);
        if ($this->config['cache']['enabled'] && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        // Construir prompt según el contexto
        $prompt = $useSystemContext ? $this->systemContext . "\n\nUsuario ($userRole): " . $userMessage : $userMessage;
        
        // Llamar a la API de Gemini
        $response = $this->callGeminiAPI($prompt);
        
        // Guardar en cache
        if ($response && $this->config['cache']['enabled']) {
            $this->cache[$cacheKey] = $response;
            // Limitar tamaño del cache
            if (count($this->cache) > $this->config['cache']['max_size']) {
                array_shift($this->cache);
            }
        }
        
        return $response;
    }
    
    /**
     * Llama a la API de Google Gemini
     */
    private function callGeminiAPI($prompt) {
        try {
            $apiKey = $this->config['gemini']['api_key'];
            $apiUrl = $this->config['gemini']['api_url'] . '?key=' . $apiKey;
            
            $data = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => $this->config['gemini']['temperature'],
                    'maxOutputTokens' => $this->config['gemini']['max_tokens'],
                ]
            ];
            
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['gemini']['timeout']);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                error_log("Gemini API Error: HTTP $httpCode - $response");
                return null;
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                return trim($result['candidates'][0]['content']['parts'][0]['text']);
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("AIService Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifica si una pregunta es sobre el sistema o es general
     */
    public function isSystemQuestion($message) {
        // Palabras clave ampliadas para cubrir funciones de Profesor, Administrador y Encargado
        $systemKeywords = [
            // Profesor
            'reserv', 'aula', 'préstamo', 'prestamo', 'equipo', 'proyector', 'laptop',
            'historial', 'contraseña', 'password', 'cancelar', 'sms', 'código', 'codigo', 'verificar',
            'pdf', 'descargar', 'horario', 'turno', 'sala',
            // Administrador
            'administrador', 'usuarios', 'gestionar usuarios', 'gestión de usuarios', 'reportes', 'filtros',
            'estadísticas', 'estadistica', 'analytics', 'kpi', 'ranking', 'historial global',
            // Encargado
            'encargado', 'devolución', 'devolucion', 'validar préstamo', 'validar prestamo', 'inspección', 'inspeccion',
            // Generales del sistema
            'panel', 'módulo', 'modulo', 'sistema de reservas', 'sistema de préstamos'
        ];
        
        $lowerMessage = mb_strtolower($message, 'UTF-8');
        
        foreach ($systemKeywords as $keyword) {
            if (mb_strpos($lowerMessage, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Mejora una respuesta del KB con IA para hacerla más natural
     */
    public function enhanceKBResponse($kbResponse, $userMessage, $userRole = 'Profesor') {
        if (!$this->config['bot']['use_ai_for_system_questions']) {
            return $kbResponse;
        }
        
        $enhancePrompt = "Actúa según el rol: $userRole. Reescribe la siguiente respuesta de forma natural, juvenil y amable, manteniendo toda la información técnica. " .
            "Usuario ($userRole) preguntó: \"$userMessage\"\n" .
            "Respuesta original:\n$kbResponse\n\n" .
            "Respuesta mejorada:";
        
        $enhanced = $this->callGeminiAPI($enhancePrompt);
        
        return $enhanced ?: $kbResponse; // Si falla, devolver original
    }
    
    /**
     * Responde preguntas generales (fuera del sistema)
     */
    public function answerGeneralQuestion($message) {
        if (!$this->config['bot']['use_ai_for_general_questions']) {
            return null;
        }
        
        $generalPrompt = "Responde brevemente (máximo 3 líneas) esta pregunta con tono juvenil y amable: \"$message\"\n" .
            "Después de responder, menciona que también puedes ayudar con el sistema de reservas y préstamos.";
        
        return $this->callGeminiAPI($generalPrompt);
    }
    
    /**
     * Detecta el sentimiento mejorado con IA
     */
    public function detectSentimentAI($message) {
        $sentimentPrompt = "Analiza el sentimiento de este mensaje en una sola palabra (frustrado/urgente/confundido/calma/neutro): \"$message\"";
        
        $sentiment = $this->callGeminiAPI($sentimentPrompt);
        
        if ($sentiment) {
            $sentiment = strtolower(trim($sentiment));
            if (in_array($sentiment, ['frustrado', 'urgente', 'confundido', 'calma', 'neutro'])) {
                return $sentiment;
            }
        }
        
        return null;
    }
    
    /**
     * Extrae la intención del usuario usando IA
     */
    public function extractIntent($message) {
        $intentPrompt = "Identifica la intención principal en una sola etiqueta entre: " .
            "reservar, prestamo, historial, historial_global, cancelar, devolucion, cambiar_contrasena, " .
            "notificaciones, reenviar_codigo, gestion_usuarios, reportes_estadisticas, validar_prestamo, registrar_devolucion, perfil, anticipacion, general, otro.\n" .
            "Mensaje: \"$message\"";
        
        $intent = $this->callGeminiAPI($intentPrompt);
        
        if ($intent) {
            return strtolower(trim($intent));
        }
        
        return null;
    }
}
