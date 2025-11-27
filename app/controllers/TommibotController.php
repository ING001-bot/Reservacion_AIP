<?php
require_once __DIR__ . '/../lib/AIService.php';

class TommibotController {
  private $db;
  private $kb;
  private $ai;
  private $userRole;
  private $userName;
  private $userId; // Nuevo campo para almacenar ID del usuario
  
  public function __construct($conexion){
    $this->db = $conexion;
    $this->kb = $this->loadKB();
    $this->ai = new AIService($this->db);
    $this->detectUserRole();
  }

  /**
   * Respuesta con payload de acciones para frontend
   */
  public function replyPayload($userId, $message, $mode = 'text'){
    $replyText = $this->reply($userId, $message, $mode);
    $lower = mb_strtolower(trim((string)$message), 'UTF-8');
    $intent = $this->ai->extractIntent($message) ?? $this->detectIntent($lower);
    $actions = $this->mapIntentToActions($intent, $lower);
    return [ 'reply' => $replyText, 'actions' => $actions ];
  }

  /**
   * Mapea intents a acciones ejecutables según rol
   */
  private function mapIntentToActions($intent, string $lower){
    $role = strtolower((string)$this->userRole);
    $actions = [];
    
    if (!$intent) {
      // Detectar VERBOS DE NAVEGACIÓN (ir, llevar, mostrar, ver, abrir, quiero)
      $hasNavigationVerb = preg_match('/(ir|llevame|llévame|llevame|mostrar|ver|abrir|quiero|necesito|dame|muestra|abre)/i', $lower);
      
      // Inferir por texto simple para comandos comunes
      if (preg_match('/(ir|llevame|llévame|mostrar|ver|abrir|quiero).*(reserv)/i', $lower)) {
        $intent = 'reservar';
      }
      elseif (preg_match('/(ir|llevame|llévame|mostrar|ver|abrir|quiero).*(préstamo|prestamo|prestar|equipo)/i', $lower)) {
        $intent = 'prestamo';
      }
      elseif (preg_match('/(ir|llevame|llévame|mostrar|ver|abrir|quiero).*(historial|mis reservas|mis préstamos|mis prestamos)/i', $lower)) {
        $intent = 'historial';
      }
      elseif (preg_match('/(ir|llevame|llévame|mostrar|ver|abrir|quiero).*(contraseña|password|clave|cambiar|seguridad)/i', $lower)) {
        $intent = 'cambiar_contrasena';
      }
      elseif (preg_match('/(ir|llevame|llévame|mostrar|ver|abrir|quiero).*(reporte|estadística|estadistica|gráfico|grafico)/i', $lower)) {
        $intent = 'reportes_estadisticas';
      }
      elseif (preg_match('/(ir|llevame|llévame|mostrar|ver|abrir|quiero).*(devoluc|validar|entregar)/i', $lower)) {
        $intent = 'devolucion';
      }
      elseif (preg_match('/(ir|llevame|llévame|mostrar|ver|abrir|quiero).*(usuario|profesor|admin|encargado|gestionar)/i', $lower)) {
        $intent = 'gestion_usuarios';
      }
      elseif (preg_match('/(ir|llevame|llévame|mostrar|ver|abrir|quiero).*(aula|sala|laboratorio)/i', $lower)) {
        $intent = 'gestion_aulas';
      }
      elseif (preg_match('/(ir|llevame|llévame|mostrar|ver|abrir|quiero).*(equipo|laptop|proyector|extensión|inventario)/i', $lower)) {
        $intent = 'gestion_equipos';
      }
      elseif (preg_match('/(ir|llevame|llévame|mostrar|ver|abrir|quiero).*(notificac|aviso|alerta)/i', $lower)) {
        $intent = 'notificaciones';
      }
      elseif (preg_match('/(ir|llevame|llévame|mostrar|ver|abrir|quiero).*(perfil|configurac|ajuste|cuenta)/i', $lower)) {
        $intent = 'perfil';
      }
      elseif (preg_match('/(ir|llevame|llévame|mostrar|ver|abrir|quiero).*(inicio|dashboard|principal|home)/i', $lower)) {
        $intent = 'inicio';
      }
      // Detección SIN verbo (formato antiguo - mantener compatibilidad)
      elseif (mb_strpos($lower, 'reserv') !== false) $intent = 'reservar';
      elseif (mb_strpos($lower, 'préstamo') !== false || mb_strpos($lower, 'prestamo') !== false) $intent = 'prestamo';
      elseif (mb_strpos($lower, 'historial') !== false || mb_strpos($lower, 'mis reservas') !== false || mb_strpos($lower, 'mis préstamos') !== false || mb_strpos($lower, 'mis prestamos') !== false) $intent = 'historial';
      elseif (mb_strpos($lower, 'contraseña') !== false || mb_strpos($lower, 'password') !== false) $intent = 'cambiar_contrasena';
      elseif (mb_strpos($lower, 'reporte') !== false || mb_strpos($lower, 'estadíst') !== false) $intent = 'reportes_estadisticas';
      elseif (mb_strpos($lower, 'devoluc') !== false || mb_strpos($lower, 'validar préstamo') !== false || mb_strpos($lower, 'validar prestamo') !== false) $intent = 'devolucion';
      elseif (mb_strpos($lower, 'descargar') !== false && (mb_strpos($lower, 'pdf') !== false)) $intent = 'descargar_pdf';
    }

    switch ($intent){
      case 'reservar':
        if ($role === 'profesor' || $role === 'administrador') {
          $actions[] = ['type'=>'offer','target'=>'reservas','message'=>'¿Quieres que te lleve al módulo de Reservas ahora?'];
        }
        break;
      case 'prestamo':
        if ($role === 'profesor' || $role === 'administrador') {
          $actions[] = ['type'=>'offer','target'=>'prestamo','message'=>'¿Quieres que te lleve al módulo de Préstamos ahora?'];
        }
        break;
      case 'historial':
      case 'historial_global':
        if ($role === 'administrador') $actions[] = ['type'=>'offer','target'=>'historial','message'=>'¿Quieres ver el Historial Global ahora?'];
        elseif ($role === 'encargado') $actions[] = ['type'=>'offer','target'=>'historial','message'=>'¿Quieres ver el Historial ahora?'];
        else $actions[] = ['type'=>'offer','target'=>'historial','message'=>'¿Quieres ver tu Historial ahora?'];
        break;
      case 'cambiar_contrasena':
        $actions[] = ['type'=>'offer','target'=>'password','message'=>'¿Quieres ir a Cambiar Contraseña ahora?'];
        break;
      case 'devolucion':
        if ($role === 'encargado') {
          $actions[] = ['type'=>'offer','target'=>'devolucion','message'=>'¿Quieres ir al módulo de Devoluciones ahora?'];
        }
        break;
      case 'gestion_usuarios':
        if ($role === 'administrador') {
          $actions[] = ['type'=>'offer','target'=>'usuarios','message'=>'¿Quieres ir a Gestión de Usuarios ahora?'];
        }
        break;
      case 'gestion_aulas':
        if ($role === 'administrador') {
          $actions[] = ['type'=>'offer','target'=>'aulas','message'=>'¿Quieres ir a Gestión de Aulas ahora?'];
        }
        break;
      case 'gestion_equipos':
        if ($role === 'administrador') {
          $actions[] = ['type'=>'offer','target'=>'equipos','message'=>'¿Quieres ir a Gestión de Equipos ahora?'];
        }
        break;
      case 'reportes_estadisticas':
        if ($role === 'administrador') {
          $actions[] = ['type'=>'navigate','target'=>'reportes'];
        }
        break;
      case 'validar_prestamo':
      case 'registrar_devolucion':
      case 'devolucion':
        if ($role === 'encargado' || $role === 'administrador') {
          $actions[] = ['type'=>'navigate','target'=>'devolucion'];
        }
        break;
      case 'notificaciones':
        $actions[] = ['type'=>'navigate','target'=>'notificaciones'];
        break;
      case 'perfil':
        $actions[] = ['type'=>'navigate','target'=>'perfil'];
        break;
      case 'inicio':
        $actions[] = ['type'=>'navigate','target'=>'inicio'];
        break;
      case 'descargar_pdf':
        $actions[] = ['type'=>'click','selector'=>'[data-action="download-pdf"]'];
        break;
      default:
        // No actions
        break;
    }

    return $actions;
  }
  
  /**
   * Detecta el rol y nombre del usuario desde la sesión
   */
  private function detectUserRole() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $this->userRole = $_SESSION['tipo'] ?? 'Visitante';
    $this->userName = $_SESSION['usuario'] ?? 'Usuario';
    $this->userId = $_SESSION['usuario_id'] ?? null; // Capturar ID del usuario
  }

  public function reply($userId, $message, $mode = 'text'){
    $m = trim((string)$message);
    // Si el mensaje está vacío, no responder nada (el panel lateral tiene las preguntas)
    if ($m === '') return '';

    $lower = mb_strtolower($m, 'UTF-8');
    
    // Saludo directo
    if ($this->isGreeting($lower)){
      $sent = $this->detectSentiment($lower);
      return $this->greetingFor($sent);
    }
    
    // El flujo ahora es más simple: siempre se usa el AIService, que tiene la lógica local.
    $response = $this->ai->generateResponse($message, $this->userRole, $this->userId);
    
    return $this->addRoleContext($response);
  }
  
  /**
   * Maneja preguntas sobre el sistema - ESTA FUNCIÓN YA NO SE USA DIRECTAMENTE
   */
  private function handleSystemQuestion($message, $lower, $mode) {
    // Esta lógica ha sido movida y simplificada dentro de AIService.
    // Se mantiene por si se necesita en el futuro, pero no es llamada en el flujo actual.
    $response = $this->ai->generateResponse($message, $this->userRole, $this->userId);
    return $this->addRoleContext($response);
  }
  
  /**
   * Maneja preguntas generales (fuera del sistema) - ESTA FUNCIÓN YA NO SE USA
   */
  private function handleGeneralQuestion($message, $mode) {
     // Esta lógica ha sido movida y simplificada dentro de AIService.
    $response = $this->ai->generateResponse($message, $this->userRole, $this->userId);
    return $this->addRoleContext($response);
  }
  
  /**
   * Agrega contexto según el rol del usuario
   */
  private function addRoleContext($response) {
    // Función deshabilitada - Las sugerencias ahora se manejan en AIService
    return $response;
  }
  
  /**
   * Respuesta para mensaje vacío según rol
   */
  private function getEmptyMessageResponse() {
    $responses = [
      'Profesor' => '¡Hola! 👋 Soy Tommibot. Haz clic en las preguntas rápidas del panel lateral para comenzar.',
      'Administrador' => '¡Hola! 👋 Soy Tommibot. Haz clic en las preguntas rápidas del panel lateral para comenzar.',
      'Encargado' => '¡Hola! 👋 Soy Tommibot. Haz clic en las preguntas rápidas del panel lateral para comenzar.'
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
