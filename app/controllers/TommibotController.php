<?php
require_once __DIR__ . '/../lib/AIService.php';

class TommibotController {
  private $db;
  private $kb;
  private $ai;
  private $userRole;
  private $userName;
  
  public function __construct($conexion){
    $this->db = $conexion;
    $this->kb = $this->loadKB();
    $this->ai = new AIService();
    $this->detectUserRole();
  }
  
  /**
   * Detecta el rol y nombre del usuario desde la sesión
   */
  private function detectUserRole() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $this->userRole = $_SESSION['tipo'] ?? 'Visitante';
    $this->userName = $_SESSION['usuario'] ?? 'Usuario';
  }

  public function reply($userId, $message, $mode = 'text'){
    $m = trim((string)$message);
    if ($m === '') return $this->getEmptyMessageResponse();

    $lower = mb_strtolower($m, 'UTF-8');
    
    // Saludo directo
    if ($this->isGreeting($lower)){
      $sent = $this->detectSentiment($lower);
      return $this->greetingFor($sent);
    }
    
    // Verificar si es pregunta del sistema o general
    $isSystemQuestion = $this->ai->isSystemQuestion($m);
    
    if ($isSystemQuestion) {
      // Pregunta sobre el sistema - usar KB + IA para mejorar
      return $this->handleSystemQuestion($m, $lower, $mode);
    } else {
      // Pregunta general - usar IA directamente
      return $this->handleGeneralQuestion($m, $mode);
    }
  }
  
  /**
   * Maneja preguntas sobre el sistema
   */
  private function handleSystemQuestion($message, $lower, $mode) {
    // Detectar intención con IA primero, luego con KB
    $intent = $this->ai->extractIntent($message) ?? $this->detectIntent($lower);
    $sent = $this->ai->detectSentimentAI($message) ?? $this->detectSentiment($lower);
    
    // Buscar en KB
    $tpl = isset($this->kb['intents'][$intent]) ? $this->kb['intents'][$intent]['template'] : null;
    
    if ($tpl) {
      // Respuesta del KB
      $kbResponse = $this->formatFromTemplate($tpl, $sent, $mode);
      
      // Mejorar con IA si está en modo texto
      if ($mode === 'text') {
        $enhancedResponse = $this->ai->enhanceKBResponse($kbResponse, $message, $this->userRole);
        return $this->addRoleContext($enhancedResponse);
      }
      
      return $kbResponse;
    }
    
    // No encontrado en KB, usar IA pura
    $aiResponse = $this->ai->generateResponse($message, $this->userRole, true);
    
    if ($aiResponse) {
      return $this->addRoleContext($aiResponse);
    }
    
    // Fallback final
    return $this->formatFromTemplate($this->kb['out_of_scope']['response'] ?? null, $sent);
  }
  
  /**
   * Maneja preguntas generales (fuera del sistema)
   */
  private function handleGeneralQuestion($message, $mode) {
    $aiResponse = $this->ai->answerGeneralQuestion($message);
    
    if ($aiResponse) {
      // Añadir contexto por rol aunque sea pregunta general
      return $this->addRoleContext($aiResponse);
    }
    
    // Si la IA no está disponible, fallback específico por rol
    $fallbackByRole = [
      'Profesor' => 'Puedo responder preguntas generales breves, pero mi enfoque es el sistema. ¿Te ayudo con reservas, préstamos, historial o cambio de contraseña?',
      'Administrador' => 'Puedo responder preguntas generales breves, pero mi enfoque es el sistema. ¿Te ayudo con gestión de usuarios, reportes, estadísticas o historial global?',
      'Encargado' => 'Puedo responder preguntas generales breves, pero mi enfoque es el sistema. ¿Te ayudo con devoluciones, validación de préstamos o historial?'
    ];
    $base = $fallbackByRole[$this->userRole] ?? '¿En qué te ayudo dentro del sistema?';
    return $base;
  }
  
  /**
   * Agrega contexto según el rol del usuario
   */
  private function addRoleContext($response) {
    // No modificar si ya es muy largo
    if (strlen($response) > 800) return $response;
    
    $roleHints = [
      'Profesor' => '',
      'Administrador' => '\n\n💡 Como administrador, también puedes gestionar usuarios, ver reportes globales y estadísticas desde tu panel.',
      'Encargado' => '\n\n💡 Como encargado, recuerda que puedes gestionar devoluciones y validar préstamos desde tu panel.'
    ];
    
    $hint = $roleHints[$this->userRole] ?? '';
    return $response . $hint;
  }
  
  /**
   * Respuesta para mensaje vacío según rol
   */
  private function getEmptyMessageResponse() {
    $responses = [
      'Profesor' => '¿En qué puedo ayudarte hoy? Puedo guiarte con reservas, préstamos, historial o cambio de contraseña. 😊',
      'Administrador' => '¿En qué puedo ayudarte? Puedo asistirte con gestión de usuarios, reportes, estadísticas, y más.',
      'Encargado' => '¿Qué necesitas? Puedo ayudarte con devoluciones, validación de préstamos y control de aulas.'
    ];
    
    return $responses[$this->userRole] ?? '¿En qué puedo ayudarte?';
  }

  private function loadKB(){
    $file = realpath(__DIR__ . '/../../Public/kb/tommibot_kb.json');
    if ($file && is_file($file)){
      $json = @file_get_contents($file);
      $data = json_decode($json, true);
      if (is_array($data)) return $data;
    }
    // Fallback mínimo
    return [
      'intents' => [
        'reservar' => [ 'keywords' => ['reserv','aula'], 'template' => ['summary'=>'Te guío para reservar.','steps'=>['Ve a Reservas','Elige fecha y hora','Confirma con SMS']] ],
      ],
      'out_of_scope' => [ 'response' => ['summary'=>'Solo atiendo consultas del sistema.','steps'=>['Puedo ayudarte con reservas, préstamos, historial y contraseñas.','¿Deseas que te derive a soporte humano?']] ]
    ];
  }

  private function detectIntent(string $lower){
    if (!isset($this->kb['intents']) || !is_array($this->kb['intents'])) return null;
    $best = null; $bestScore = 0;
    foreach ($this->kb['intents'] as $name => $cfg){
      $score = 0;
      $keys = (array)($cfg['keywords'] ?? []);
      foreach ($keys as $k){ if ($k !== '' && mb_strpos($lower, $k) !== false) $score++; }
      if ($score > $bestScore){ $bestScore = $score; $best = $name; }
    }
    return $bestScore > 0 ? $best : null;
  }

  private function isGreeting(string $lower): bool {
    $g = ['hola','buenos días','buenas tardes','buenas noches','hey','buenas'];
    foreach ($g as $w){ if (mb_strpos($lower, $w) !== false) return true; }
    return false;
  }

  private function greetingFor(string $sent): string {
    $roleGreeting = [
      'Profesor' => 'profe',
      'Administrador' => 'admin',
      'Encargado' => 'jefe'
    ];
    
    $greeting = $roleGreeting[$this->userRole] ?? '';
    $name = $this->userName !== 'Usuario' ? $this->userName : $greeting;
    
    switch ($sent){
      case 'frustrado': 
        return "Hola, $name 😔. Te noto con molestias; cuéntame qué pasó y lo resolvemos juntos.";
      case 'urgente': 
        return "¡Hola $name! ⚡ Dime rápido qué necesitas y te guío al instante.";
      case 'confundido': 
        return "¡Hola $name! 😊 Te explico paso a paso lo que necesites. ¿Sobre qué necesitas ayuda?";
      case 'calma': 
        return "¡Hola $name! 😊 Qué gusto verte por aquí. ¿En qué te ayudo hoy?";
      default: 
        return "¡Hola $name! ¿En qué te ayudo hoy?";
    }
  }

  private function detectSentiment(string $lower){
    $maps = [
      'frustrado' => ['no funciona','error','molesto','frustrado','no puedo','ayuda por favor','urgente y no sale'],
      'urgente'   => ['apurado','rápido','urgente','de inmediato','ya mismo'],
      'confundido'=> ['no entiendo','cómo hago','como hago','no sé','no se','duda'],
      'calma'     => ['gracias','por favor','buenos','buenas','hola']
    ];
    foreach ($maps as $label => $arr){ foreach ($arr as $k){ if ($k && mb_strpos($lower, $k) !== false) return $label; } }
    return 'neutro';
  }

  private function tonePrefix(string $sent){
    switch ($sent){
      case 'frustrado': return 'Lamento el inconveniente — te ayudo paso a paso. ';
      case 'urgente': return 'Voy al grano para resolverlo rápido. ';
      case 'confundido': return 'Te explico con más detalle. ';
      case 'calma': return '';
      default: return '';
    }
  }

  private function formatFromTemplate($tpl, string $sent, string $mode = 'text'){
    if (!$tpl || !is_array($tpl)) return 'Puedo ayudarte con reservas, préstamos, historial y contraseñas.';
    $summary = (string)($tpl['summary'] ?? 'Resumen.');
    $steps = (array)($tpl['steps'] ?? []);
    $image = isset($tpl['image']) ? (string)$tpl['image'] : '';
    $stepsOnly = !empty($tpl['steps_only']);
    $pref = $this->tonePrefix($sent);

    if ($mode === 'voice'){
      // Respuesta breve para voz: solo resumen con tono y hasta 2 pasos clave
      $briefSteps = array_slice($steps, 0, 2);
      $text = $pref . $summary;
      if (!empty($briefSteps)){
        $text .= ' | Pasos: 1) ' . (string)$briefSteps[0];
        if (isset($briefSteps[1])) $text .= '; 2) ' . (string)$briefSteps[1];
      }
      return $text;
    }

    if ($stepsOnly) {
      // Solo listar pasos tal cual, sin encabezados ni imágenes
      $out = [];
      $i = 1; foreach ($steps as $s){ $out[] = $i . '. ' . (string)$s; $i++; }
      return implode("\n", $out);
    }

    $out = [];
    $out[] = '(A) Resumen: ' . $pref . $summary;
    if (!empty($steps)){
      $out[] = '';
      $out[] = '(B) Pasos:';
      $i = 1; foreach ($steps as $s){ $out[] = $i . '. ' . (string)$s; $i++; }
    }
    if ($image !== ''){ $out[] = 'Imagen: ' . $image . ' (ver más)'; }
    return implode("\n", $out);
  }
}
