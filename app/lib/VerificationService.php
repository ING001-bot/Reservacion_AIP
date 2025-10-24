<?php
namespace App\Lib;

require_once __DIR__ . '/SmsService.php';

class VerificationService {
    private $db;
    private $smsService;
    
    public function __construct($conexion) {
        $this->db = $conexion;
        $this->smsService = new SmsService();
    }
    
    /**
     * Genera y envía un código de verificación
     */
    public function sendVerificationCode($userId, $phone, $actionType) {
        // Eliminar expirados globales y códigos previos del mismo usuario/acción
        $this->cleanupExpiredCodes();
        $this->deletePreviousCodesForUserAction($userId, $actionType);

        // Generar código de 6 dígitos
        $code = str_pad((string)rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Guardar en la base de datos (PDO)
        $stmt = $this->db->prepare(
            'INSERT INTO verification_codes (user_id, code, action_type, expires_at) VALUES (?, ?, ?, ?)'
        );

        $ok = $stmt->execute([(int)$userId, $code, $actionType, $expiresAt]);
        if ($ok) {
            // Enviar SMS con el código
            $message = 'Tu código de verificación para ' . $this->getActionName($actionType) . ' es: ' . $code;
            $result = $this->smsService->sendSms($phone, $message);

            if (!empty($result['success'])) {
                return ['success' => true, 'code' => $code];
            }

            // Si falla el envío, eliminar el código recién creado y propagar detalle
            $del = $this->db->prepare('DELETE FROM verification_codes WHERE user_id = ? AND code = ? AND action_type = ?');
            $del->execute([(int)$userId, $code, $actionType]);
            $detail = '';
            if (is_array($result)) {
                $detail = trim(($result['error'] ?? '').' '.(isset($result['code']) ? ('(code: '.$result['code'].')') : ''));
            }
            return ['success' => false, 'error' => $detail !== '' ? $detail : 'Error al enviar el SMS'];
        }

        return ['success' => false, 'error' => 'Error al generar el código'];
    }
    
    /**
     * Verifica un código de verificación
     */
    public function verifyCode($userId, $code, $actionType) {
        // Solo limpiar expirados (no eliminar el código vigente sin usar)
        $this->cleanupExpiredCodes();

        $stmt = $this->db->prepare(
            'SELECT id FROM verification_codes WHERE user_id = ? AND code = ? AND action_type = ? AND used = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([(int)$userId, $code, $actionType]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row && isset($row['id'])) {
            // Marcar el código como usado
            $upd = $this->db->prepare('UPDATE verification_codes SET used = 1 WHERE id = ?');
            $upd->execute([(int)$row['id']]);
            return true;
        }

        return false;
    }
    
    /**
     * Elimina códigos expirados
     */
    private function cleanupExpiredCodes() {
        $delExp = $this->db->prepare('DELETE FROM verification_codes WHERE expires_at <= NOW() OR (used = 1 AND created_at < (NOW() - INTERVAL 1 DAY))');
        $delExp->execute();
    }

    private function deletePreviousCodesForUserAction($userId, $actionType) {
        $delUser = $this->db->prepare('DELETE FROM verification_codes WHERE user_id = ? AND action_type = ? AND used = 0');
        $delUser->execute([(int)$userId, $actionType]);
    }
    
    /**
     * Obtiene el nombre de la acción para el mensaje
     */
    private function getActionName($actionType) {
        $actions = [
            'reserva' => 'realizar reservas',
            'prestamo' => 'solicitar préstamos',
            'cambio_clave' => 'cambiar tu contraseña'
        ];
        
        return $actions[$actionType] ?? 'continuar';
    }
}
