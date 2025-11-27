<?php
/**
 * Configuración de IA para Tommibot
 * API: Google Gemini Free Tier
 * 
 * Para obtener tu API Key gratuita:
 * 1. Ve a https://makersuite.google.com/app/apikey
 * 2. Crea una nueva API Key
 * 3. Reemplaza 'TU_API_KEY_AQUI' con tu clave
 * 
 * Límites gratuitos de Gemini:
 * - 60 peticiones por minuto
 * - 1500 peticiones por día
 */

return [
    // Google Gemini API Configuration
    'gemini' => [
        'enabled' => false, // Desactiva completamente el uso de la API de Gemini
        'api_key' => getenv('GEMINI_API_KEY') ?: 'TU_API_KEY_AQUI', // Reemplazar con tu API Key de Google AI Studio o definir GEMINI_API_KEY en entorno
        'api_url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent',
        'model' => 'gemini-pro',
        'enabled' => (getenv('GEMINI_API_KEY') && getenv('GEMINI_API_KEY') !== '') ? true : false, // Activar solo si hay API Key
        'timeout' => 10, // segundos
        'max_tokens' => 500,
        'temperature' => 0.7, // Creatividad (0.0 = precisa, 1.0 = creativa)
    ],
    
    // Configuración del comportamiento del bot
    'bot' => [
        'name' => 'Tommibot',
        'personality' => 'juvenil, amable, profesional',
        'tone' => 'adolescente', // para voz
        'use_ai_for_general_questions' => true, // Responder preguntas generales con IA
        'use_ai_for_system_questions' => true, // Mejorar respuestas del sistema con IA
        'fallback_to_kb' => true, // Si falla IA, usar KB local
    ],
    
    // Cache de respuestas (para optimizar uso de API)
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // tiempo de vida del cache en segundos (1 hora)
        'max_size' => 100, // máximo de respuestas en cache
    ]
];
