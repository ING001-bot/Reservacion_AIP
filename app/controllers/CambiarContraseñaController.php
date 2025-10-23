<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../middleware/VerifyMiddleware.php';
require_once __DIR__ . '/../lib/VerificationService.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['correo'])) {
    header('Location: ../../Public/index.php');
    exit();
}

// Verificar si ya está verificado para cambio de clave (solo Profesores requieren OTP)
$rolActual = $_SESSION['tipo'] ?? '';
$esProfesor = ($rolActual === 'Profesor');
$necesitaVerificacion = $esProfesor && (!isset($_SESSION['verified_cambio_clave']) || $_SESSION['verified_cambio_clave'] !== true);

// Si necesita verificación y no es una petición de verificación, enviar código
if ($necesitaVerificacion && !isset($_POST['verificar_codigo']) && !isset($_GET['reenviar'])) {
    $usuarioModel = new UsuarioModel($conexion);
    $usuario = $usuarioModel->obtenerPorId($_SESSION['id_usuario']);
    
    if ($usuario && !empty($usuario['telefono'])) {
        $verificationService = new \App\Lib\VerificationService($conexion);
        $resultadoSMS = $verificationService->sendVerificationCode($_SESSION['id_usuario'], $usuario['telefono'], 'cambio_clave');
    }
}

// Reenviar código si se solicita
if (isset($_GET['reenviar']) && $necesitaVerificacion) {
    $usuarioModel = new UsuarioModel($conexion);
    $usuario = $usuarioModel->obtenerPorId($_SESSION['id_usuario']);
    
    if ($usuario && !empty($usuario['telefono'])) {
        $verificationService = new \App\Lib\VerificationService($conexion);
        $verificationService->sendVerificationCode($_SESSION['id_usuario'], $usuario['telefono'], 'cambio_clave');
        header('Location: Cambiar_Contraseña.php');
        exit;
    }
}

// Procesar verificación de código
if (isset($_POST['verificar_codigo'])) {
    $codigo = $_POST['codigo_verificacion'] ?? '';
    $verificationService = new \App\Lib\VerificationService($conexion);
    
    if ($verificationService->verifyCode($_SESSION['id_usuario'], $codigo, 'cambio_clave')) {
        $_SESSION['verified_cambio_clave'] = true;
        // Ventana de validez de 10 minutos (coherente con otros flujos)
        $_SESSION['otp_verified_until'] = time() + 10*60;
        $necesitaVerificacion = false;
        $mensajeVerificacion = '✅ Código verificado correctamente. Ahora puedes cambiar tu contraseña.';
    } else {
        $errorVerificacion = '❌ Código incorrecto o expirado. Intenta nuevamente.';
    }
}

// Inicializar variables
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actual']) && !$necesitaVerificacion) {
    try {
        // Exigir OTP verificado si es Profesor
        if (($_SESSION['tipo'] ?? '') === 'Profesor') {
            $until = (int)($_SESSION['otp_verified_until'] ?? 0);
            if ($until < time()) {
                throw new Exception('Debes verificar tu identidad con el código SMS antes de cambiar la contraseña.');
            }
        }
        $actual = trim($_POST['actual'] ?? '');
        $nueva = trim($_POST['nueva'] ?? '');
        $confirmar = trim($_POST['confirmar'] ?? '');

        // Validaciones
        if (empty($actual) || empty($nueva) || empty($confirmar)) {
            throw new Exception("Todos los campos son obligatorios.");
        }

        if (strlen($nueva) < 8) {
            throw new Exception("La nueva contraseña debe tener al menos 8 caracteres.");
        }

        if ($nueva !== $confirmar) {
            throw new Exception("La nueva contraseña no coincide con la confirmación.");
        }

        if ($nueva === $actual) {
            throw new Exception("La nueva contraseña debe ser diferente a la actual.");
        }

        // Verificar la contraseña actual y actualizarla
        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->obtenerPorCorreo($_SESSION['correo']);

        if (!$usuario) {
            throw new Exception("Usuario no encontrado.");
        }

        if (!password_verify($actual, $usuario['contraseña'])) {
            throw new Exception("La contraseña actual es incorrecta.");
        }

        // Verificar si la nueva contraseña cumple con los requisitos de seguridad
        if (!preg_match('/[A-Z]/', $nueva) || !preg_match('/[0-9]/', $nueva) || !preg_match('/[^A-Za-z0-9]/', $nueva)) {
            throw new Exception("La contraseña debe contener al menos una letra mayúscula, un número y un carácter especial.");
        }

        // Generar el hash de la nueva contraseña
        $hash = password_hash($nueva, PASSWORD_BCRYPT, ['cost' => 12]);
        
        if (!$usuarioModel->actualizarContraseña($hash, $_SESSION['correo'])) {
            throw new Exception("Error al actualizar la contraseña. Por favor, inténtelo de nuevo.");
        }

        // Éxito
        $exito = "✅ Contraseña actualizada correctamente. Se recomienda cerrar sesión y volver a iniciar.";
        
    } catch (Exception $e) {
        $error = "⚠️ " . $e->getMessage();
    }
}
