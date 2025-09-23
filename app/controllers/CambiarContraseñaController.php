<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['correo'])) {
    header('Location: ../../Public/index.php');
    exit();
}

// Inicializar variables
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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
