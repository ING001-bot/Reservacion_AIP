<?php
class TommibotController {
  private $db;
  private $kb;
  public function __construct($conexion){
    $this->db = $conexion;
    $this->kb = $this->loadKB();
  }

  public function reply($userId, $message, $mode = 'text'){
    $m = trim((string)$message);
    if ($m === '') return '¿En qué puedo ayudarte con reservas, préstamos, historial o contraseñas?';

    $lower = mb_strtolower($m, 'UTF-8');
    // saludo directo
    if ($this->isGreeting($lower)){
      $sent = $this->detectSentiment($lower);
      $greet = $this->greetingFor($sent);
      return $greet;
    }
    $intent = $this->detectIntent($lower);
    $sent = $this->detectSentiment($lower);

    // Responder solo temas del sistema; si nada coincide, fuera de alcance
    if (!$intent){
      return $this->formatFromTemplate($this->kb['out_of_scope']['response'] ?? null, $sent);
    }

    $tpl = $this->kb['intents'][$intent]['template'] ?? null;
    if (!$tpl) {
      return $this->formatFromTemplate($this->kb['out_of_scope']['response'] ?? null, $sent);
    }

    return $this->formatFromTemplate($tpl, $sent, $mode);
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
    switch ($sent){
      case 'frustrado': return 'Hola, profe 😔. Te noto con molestias; cuéntame qué pasó con tu reserva o préstamo y lo resolvemos juntos.';
      case 'urgente': return '¡Hola profe! ⚡ Dime rápido qué necesitas y te guío al instante.';
      case 'confundido': return '¡Hola! 😊 Te explico paso a paso lo que necesites del sistema. ¿Sobre qué necesitas ayuda?';
      case 'calma': return '¡Hola profe! 😊 Qué gusto verte por aquí. ¿En qué te ayudo hoy?';
      default: return '¡Hola! ¿En qué te ayudo con reservas, préstamos, historial o contraseñas?';
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
