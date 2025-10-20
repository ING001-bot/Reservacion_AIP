<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';

try {
  if (!isset($_SESSION['id_usuario']) || ($_SESSION['tipo'] ?? '') !== 'Profesor') {
    http_response_code(403);
    echo json_encode(['ok'=>false,'msg'=>'No autorizado']);
    exit;
  }
  $id_usuario = (int)$_SESSION['id_usuario'];

  // Obtener teléfono del usuario
  $stmt = $conexion->prepare("SELECT telefono, telefono_verificado, nombre FROM usuarios WHERE id_usuario = ? AND activo = 1");
  $stmt->execute([$id_usuario]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row || empty($row['telefono'])) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>'No hay teléfono registrado. Actualiza tus datos con el Administrador.']);
    exit;
  }
  $telefono = (string)$row['telefono'];

  $purpose = $_POST['purpose'] ?? $_GET['purpose'] ?? 'prestamo';
  $purpose = in_array($purpose, ['prestamo','phone_verify'], true) ? $purpose : 'prestamo';

  if ($purpose === 'prestamo' && (int)($row['telefono_verificado'] ?? 0) !== 1) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>'Debes verificar tu teléfono antes de solicitar códigos para préstamos. Usa la opción Verificar teléfono.']);
    exit;
  }

  // Si existe un OTP vigente, reutilizarlo (no crear uno nuevo para evitar spam/limit)
  $stmt = $conexion->prepare("SELECT id, sent_at, expires_at FROM otp_tokens WHERE id_usuario = ? AND purpose = ? AND expires_at >= NOW() ORDER BY id DESC LIMIT 1");
  $stmt->execute([$id_usuario, $purpose]);
  $last = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($last) {
    echo json_encode(['ok'=>true,'msg'=>'Código vigente existente.']);
    exit;
  }

  // Rate limit: máximo 5 nuevos códigos en 10 minutos
  $stmt = $conexion->prepare("SELECT COUNT(*) FROM otp_tokens WHERE id_usuario = ? AND purpose = ? AND sent_at >= (NOW() - INTERVAL 10 MINUTE)");
  $stmt->execute([$id_usuario, $purpose]);
  $cnt10 = (int)$stmt->fetchColumn();
  if ($cnt10 >= 5) {
    http_response_code(429);
    echo json_encode(['ok'=>false,'msg'=>'Has alcanzado el límite de envíos. Intenta más tarde.']);
    exit;
  }

  // Generar OTP de 6 dígitos y guardar hash
  $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
  $hash = password_hash($code, PASSWORD_BCRYPT);
  $stmt = $conexion->prepare("INSERT INTO otp_tokens (id_usuario, purpose, code_hash, expires_at, attempts, sent_at) VALUES (?, ?, ?, (NOW() + INTERVAL 5 MINUTE), 0, NOW())");
  $stmt->execute([$id_usuario, $purpose, $hash]);

  // Enviar SMS: Integración pendiente. Por ahora, mock enviando a log.
  error_log('[OTP_SMS] To '.$telefono.' Code: '.$code);

  echo json_encode(['ok'=>true,'msg'=>'Código enviado al teléfono registrado.']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error interno','err'=>$e->getMessage()]);
}
