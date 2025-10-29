<?php
class TommibotController {
  private $db;
  public function __construct($conexion){ $this->db = $conexion; }

  public function reply($userId, $message){
    $m = trim(mb_strtolower($message,'UTF-8'));
    // Intents simples por palabras clave
    if ($this->has($m, ['reserv','aula','disponibilidad'])) return $this->faqReservas();
    if ($this->has($m, ['préstamo','prestamo','equipo','entrega','devolución','devolucion'])) return $this->faqPrestamos();
    if ($this->has($m, ['cambiar','contraseña','password'])) return $this->faqPassword();
    if ($this->has($m, ['horario','anticipación','anticipacion','día','dia'])) return $this->faqReglasTiempo();
    if ($this->has($m, ['historial','mis','estado'])) return $this->faqHistorial();
    if ($this->has($m, ['ayuda','manual','cómo','como','usar'])) return $this->faqAyuda();

    // Saludos y conversación ligera
    if ($this->has($m, ['hola','buenos días','buenas tardes','buenas noches','hey','qué tal','que tal'])) return $this->smallTalkGreeting();
    if ($this->has($m, ['cómo estás','como estas','cómo te va','que haces','qué haces'])) return $this->smallTalkMood();
    if ($this->has($m, ['quién eres','quien eres','tu nombre','cómo te llamas','como te llamas'])) return 'Soy Tommibot, tu asistente del sistema AIP. Puedo ayudarte con reservas, préstamos y dudas rápidas. 🙂';
    if ($this->has($m, ['qué hora es','que hora es','hora'])) return $this->tellTime();
    if ($this->has($m, ['qué fecha es','que fecha es','fecha','hoy'])) return $this->tellDate();

    // Preguntas generales: cultura, ciencia (respuestas breves)
    if ($this->has($m, ['quién fue','quien fue','quién es','quien es','qué es','que es','define','definición'])) return $this->generalShort($m);
    if ($this->has($m, ['dato curioso','sabías que','sabias que','curiosidad'])) return $this->funFact();

    // Fallback: sugerir chips
    return "No estoy seguro de haber entendido. Puedo ayudarte con: Reservas, Préstamos, Historial, Contraseña y Reglas de tiempo. Escribe por ejemplo: '¿Cómo reservo un aula?'";
  }

  private function has($text, $arr){ foreach($arr as $k){ if (mb_strpos($text, $k) !== false) return true; } return false; }

  private function faqReservas(){
    return "Reservas de aula: 1) Ve a Profesor > Reservas. 2) Elige fecha (mínimo 1 día de anticipación). 3) Selecciona aula y hora disponible. 4) Confirma. Recibirás un código SMS si aplica. Políticas: máximo según disponibilidad y validar conflictos.";
  }
  private function faqPrestamos(){
    return "Préstamos de equipos: 1) Ve a Profesor > Préstamos. 2) Selecciona equipo y horario (mínimo 1 día de anticipación). 3) Al devolver, el encargado registra la devolución y puedes dejar observación. Revisa tu historial para estados.";
  }
  private function faqPassword(){
    return "Cambio de contraseña: Menú > Cambiar contraseña. Debes ingresar la actual y la nueva. Si olvidaste tu clave, usa Recuperar Contraseña en el inicio.";
  }
  private function faqReglasTiempo(){
    return "Reglas de tiempo: Las reservas y préstamos se deben realizar con al menos 1 día de anticipación. Los horarios disponibles aparecen en el calendario y evitan conflictos automáticamente.";
  }
  private function faqHistorial(){
    return "Historial: Menú > Mis Reservas/Préstamos. Puedes filtrar por fecha y ver estados. Para más detalle, ingresa a cada registro.";
  }
  private function faqAyuda(){
    return "Ayuda rápida: • Reservar un aula • Pedir préstamo de laptop • Ver mi historial • Cambiar contraseña. Pregúntame algo específico, por ejemplo: '¿Qué documentos necesito para un préstamo?'";
  }

  private function smallTalkGreeting(){
    $hour = (int)date('H');
    if ($hour < 12) return '¡Buenos días! ¿En qué te ayudo hoy?';
    if ($hour < 19) return '¡Buenas tardes! ¿Qué necesitas?';
    return '¡Buenas noches! ¿Cómo puedo apoyarte?';
  }
  private function smallTalkMood(){
    return '¡Muy bien! Aquí listo para ayudarte con el sistema. ¿Qué deseas hacer: reservar, pedir préstamo o consultar algo?';
  }
  private function tellTime(){
    date_default_timezone_set('America/Lima');
    return 'La hora actual es ' . date('H:i') . ' (hora de Lima).';
  }
  private function tellDate(){
    date_default_timezone_set('America/Lima');
    $dias = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $d = $dias[(int)date('w')]; $day = (int)date('d'); $m = $meses[(int)date('n')-1]; $y = date('Y');
    return "Hoy es $d, $day de $m de $y.";
  }
  private function generalShort(string $m){
    // Mini glosario muy acotado
    $defs = [
      'fotosíntesis' => 'La fotosíntesis es el proceso por el cual las plantas convierten luz en energía química (glucosa).',
      'gravidad' => 'La gravedad es la fuerza de atracción entre cuerpos con masa; mantiene a los planetas en órbita.',
      'adn' => 'El ADN es el material genético que contiene la información para el desarrollo y funcionamiento de los seres vivos.',
      'internet' => 'Internet es una red global de computadoras que permite intercambiar información mediante protocolos comunes.',
      'mitosis' => 'La mitosis es el proceso de división celular que produce dos células hijas idénticas.'
    ];
    foreach ($defs as $k => $v){ if (mb_strpos($m, $k) !== false) return $v; }
    return 'Puedo responder dudas básicas de cultura y ciencia. Intenta con: ¿Qué es la fotosíntesis? o ¿Qué es la mitosis?';
  }
  private function funFact(){
    $facts = [
      '¿Sabías que las abejas pueden reconocer rostros humanos? Estudios muestran que aprenden patrones visuales complejos.',
      'El corazón de una ballena azul puede pesar más de 150 kg.',
      'El monte Everest crece unos milímetros cada año por el movimiento tectónico.',
      'Los pulpos tienen tres corazones y sangre azul.',
      'La Vía Láctea y Andrómeda colisionarán en unos 4 mil millones de años.'
    ];
    return $facts[array_rand($facts)];
  }
}
