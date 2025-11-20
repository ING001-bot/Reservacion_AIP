<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../lib/VerificationService.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class VerificationController {
    private $verificationService;
    private $db;
    
    public function __construct($conexion) {
        $this->db = $conexion;
        $this->verificationService = new \App\Lib\VerificationService($conexion);
    }
    
    /**
     * Maneja las solicitudes al controlador
     */
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        // Verificar si es una solicitud AJAX/JSON
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        try {
            switch ($action) {
                case 'verify':
                    $result = $this->verifyCode();
                    break;
                    
                case 'resend':
                    $result = $this->resendCode();
                    break;
                    
                default:
                    $result = ['success' => false, 'error' => 'Acción no válida'];
                    break;
            }
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
            } else {
                // Redireccionar según el resultado
                if ($result['success']) {
                    $this->redirectAfterVerification($result['action_type']);
                } else {
                    $error = urlencode($result['error'] ?? 'Error desconocido');
                    header("Location: /Reservacion_AIP/Public/verificar.php?action={$result['action_type']}&error=$error");
                    exit;
                }
            }
            
        } catch (Exception $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            } else {
                $error = urlencode($e->getMessage());
                $actionType = $_POST['action_type'] ?? $_GET['action_type'] ?? 'reserva';
                header("Location: /Reservacion_AIP/Public/verificar.php?action=$actionType&error=$error");
            }
            exit;
        }
    }
    
    /**
     * Redirige al usuario después de una verificación exitosa
     */
    private function redirectAfterVerification($actionType) {
        switch ($actionType) {
            case 'reserva':
                if (isset($_SESSION['pending_reservation'])) {
                    // Redirigir al formulario de reserva con los datos guardados
                    $reserva = $_SESSION['pending_reservation'];
                    unset($_SESSION['pending_reservation']);
                    
                    $params = http_build_query([
                        'id_aula' => $reserva['id_aula'],
                        'fecha' => $reserva['fecha'],
                        'hora_inicio' => $reserva['hora_inicio'],
                        'hora_fin' => $reserva['hora_fin']
                    ]);
                    
                    header("Location: /Reservacion_AIP/Public/reservar.php?$params");
                } else {
                    header('Location: /Reservacion_AIP/Public/reservar.php');
                }
                break;
                
            case 'prestamo':
                header('Location: /Reservacion_AIP/Public/prestamos.php');
                break;
                
            case 'cambio_clave':
                header('Location: /Reservacion_AIP/Public/cambiar_contrasena.php');
                break;
                
            default:
                header('Location: /Reservacion_AIP/Public/index.php');
                break;
        }
        exit;
    }
    
    /**
     * Muestra el formulario de verificación
     */
    public function showVerificationForm($actionType) {
        if (!isset($_SESSION['usuario_id']) || !in_array($actionType, ['reserva', 'prestamo', 'cambio_clave'])) {
            header('Location: /Sistema_reserva_AIP/Public/index.php');
            exit;
        }
        
        // Guardar la acción en sesión
        $_SESSION['pending_action'] = $actionType;
        
        // Redirigir al formulario de verificación
        header("Location: /Reservacion_AIP/Public/verificar.php?action=$actionType");
        exit;
    }
    
    /**
     * Procesa el código de verificación
     */
    public function verifyCode() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id']) || !isset($_SESSION['pending_action'])) {
            return ['success' => false, 'error' => 'Solicitud inválida', 'action_type' => $_SESSION['pending_action'] ?? 'reserva'];
        }
        
        $actionType = $_SESSION['pending_action'];
        $code = $_POST['verification_code'] ?? '';
        
        if (empty($code)) {
            return ['success' => false, 'error' => 'Por favor ingresa el código de verificación', 'action_type' => $actionType];
        }
        
        if ($this->verificationService->verifyCode($_SESSION['usuario_id'], $code, $actionType)) {
            // Código válido, marcar como verificado
            $_SESSION['verified_' . $actionType] = true;
            unset($_SESSION['pending_action']);
            
            return ['success' => true, 'action_type' => $actionType];
        } else {
            return ['success' => false, 'error' => 'Código inválido o expirado', 'action_type' => $actionType];
        }
    }
    
    /**
     * Envía un nuevo código de verificación
     */
    public function resendCode() {
        if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['pending_action'])) {
            return ['success' => false, 'error' => 'Sesión inválida'];
        }
        
        $actionType = $_SESSION['pending_action'];
        
        // Obtener el número de teléfono del usuario
        $usuarioModel = new UsuarioModel($this->db);
        $usuario = $usuarioModel->obtenerPorId($_SESSION['usuario_id']);
        
        if (!$usuario || empty($usuario['telefono'])) {
            return ['success' => false, 'error' => 'No se encontró un número de teléfono válido'];
        }
        
        // Enviar nuevo código
        return $this->verificationService->sendVerificationCode(
            $_SESSION['usuario_id'],
            $usuario['telefono'],
            $actionType
        );
    }
}
