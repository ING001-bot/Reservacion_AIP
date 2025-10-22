<?php
namespace App\Middleware;

class VerifyMiddleware {
    private $conexion;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }
    
    /**
     * Verifica si el usuario está autenticado y ha verificado su identidad para la acción
     */
    public function requireVerification($actionType) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar si el usuario está autenticado
        if (!isset($_SESSION['usuario_id'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: /Sistema_reserva_AIP/Public/login.php');
            exit;
        }
        
        // Verificar si ya está verificado para esta acción
        if (!isset($_SESSION['verified_' . $actionType]) || $_SESSION['verified_' . $actionType] !== true) {
            // Enviar código SMS automáticamente
            $this->sendVerificationCode($_SESSION['usuario_id'], $actionType);
            
            // Redirigir a la página de verificación
            $_SESSION['pending_action'] = $actionType;
            header('Location: /Sistema_reserva_AIP/Public/verificar.php?action=' . $actionType);
            exit;
        }
        
        return true;
    }
    
    /**
     * Envía el código de verificación SMS
     */
    private function sendVerificationCode($userId, $actionType) {
        require_once __DIR__ . '/../lib/VerificationService.php';
        require_once __DIR__ . '/../models/UsuarioModel.php';
        
        $usuarioModel = new UsuarioModel($this->conexion);
        $usuario = $usuarioModel->obtenerPorId($userId);
        
        if ($usuario && !empty($usuario['telefono'])) {
            $verificationService = new \App\Lib\VerificationService($this->conexion);
            $verificationService->sendVerificationCode($userId, $usuario['telefono'], $actionType);
        }
    }
    
    /**
     * Verifica si el usuario puede realizar una acción específica
     */
    public static function canPerformAction($actionType) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['verified_' . $actionType]) && 
               $_SESSION['verified_' . $actionType] === true;
    }
}
