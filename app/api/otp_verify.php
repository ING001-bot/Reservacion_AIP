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
  $code = trim($_POST['code'] ?? $_GET['code'] ?? '');
  $purpose = $_POST['purpose'] ?? $_GET['purpose'] ?? 'prestamo';
  $purpose = in_array($purpose, ['prestamo','phone_verify'], true) ? $purpose : 'prestamo';
  if ($code === '' || !preg_match('/^\d{6}$/', $code)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>'Código inválido']);
    exit;
  }
  // Obtener último OTP no expirado
  $stmt = $conexion->prepare("SELECT id, code_hash, expires_at, attempts FROM otp_tokens WHERE id_usuario = ? AND purpose = ? AND expires_at >= NOW() ORDER BY id DESC LIMIT 1");
  $stmt->execute([$id_usuario, $purpose]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>'No hay código vigente. Solicítalo nuevamente.']);
    exit;
  }
  if ((int)$row['attempts'] >= 5) {
    http_response_code(429);
    echo json_encode(['ok'=>false,'msg'=>'Se excedieron los intentos. Solicita un nuevo código.']);
    exit;
  }
  $ok = password_verify($code, $row['code_hash']);
  if (!$ok) {
    $conexion->prepare("UPDATE otp_tokens SET attempts = attempts + 1 WHERE id = ?")->execute([(int)$row['id']]);
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>'Código incorrecto']);
    exit;
  }
  // Éxito: marcar como usado (opcional: borrar todos los OTP activos) y setear ventana de validez
  $conexion->prepare("DELETE FROM otp_tokens WHERE id_usuario = ? AND purpose = ?")->execute([$id_usuario, $purpose]);
  if ($purpose === 'phone_verify') {
    // Teléfono verificado (aunque no se almacena estado en BD - solo flujo OTP)
    echo json_encode(['ok'=>true,'msg'=>'Teléfono verificado.']);
  } else {
    $_SESSION['otp_verified_until'] = time() + 10*60; // 10 minutos
    echo json_encode(['ok'=>true,'msg'=>'Código verificado. Tienes 10 minutos para confirmar.']);
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error interno','err'=>$e->getMessage()]);
}
