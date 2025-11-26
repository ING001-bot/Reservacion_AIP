<?php
namespace App\Lib;

use App\Lib\Mailer;
use App\Lib\SmsService;

class NotificationService {
    private $mailer;
    private $smsService;
    private $appName;

    public function __construct() {
        $this->mailer = new Mailer();
        $this->smsService = new SmsService();
        $this->appName = 'Sistema de Reservas AIP';
    }

    /**
     * Envía una notificación por correo electrónico y/o SMS
     */
    public function sendNotification($to, $subject, $message, $options = []) {
        $defaults = [
            'sendEmail' => true,
            'sendSms' => true,
            'type' => 'info', // info, success, warning, error
            'url' => null,
            'userName' => null
        ];
        
        $options = array_merge($defaults, $options);
        
        // Enviar correo electrónico si está habilitado
        if ($options['sendEmail'] && !empty($to['email'])) {
            $this->sendEmail(
                $to['email'],
                $subject,
                $this->formatEmailMessage($message, $options),
                $options
            );
        }
        
        // Enviar SMS si está habilitado
        if ($options['sendSms'] && !empty($to['phone'])) {
            $this->sendSms(
                $to['phone'],
                $this->formatSmsMessage($subject, $message, $options)
            );
        }
    }

    private function sendEmail($to, $subject, $message, $options) {
        // Usamos el sistema de correo existente
        $this->mailer->send($to, $subject, $message);
    }

    private function sendSms($to, $message) {
        // Usamos el servicio de SMS que ya creamos
        $this->smsService->sendSms($to, $message);
    }

    private function formatEmailMessage($message, $options) {
        $userName = $options['userName'] ?? 'Usuario';
        $appName = $this->appName;
        $url = $options['url'] ?? '';
        
        $html = '<!DOCTYPE html>';
        $html .= '<html>';
        $html .= '<head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<title>' . htmlspecialchars($appName) . ' - Notificación</title>';
        $html .= '<style>';
        $html .= 'body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }';
        $html .= '.container { max-width: 600px; margin: 0 auto; padding: 20px; }';
        $html .= '.header { background-color: #4CAF50; color: white; padding: 10px 20px; text-align: center; }';
        $html .= '.content { padding: 20px; background-color: #f9f9f9; }';
        $html .= '.footer { margin-top: 20px; font-size: 12px; text-align: center; color: #777; }';
        $html .= '.button { ';
        $html .= '    display: inline-block; ';
        $html .= '    padding: 10px 20px; ';
        $html .= '    background-color: #4CAF50; ';
        $html .= '    color: white; ';
        $html .= '    text-decoration: none; ';
        $html .= '    border-radius: 4px; ';
        $html .= '    margin: 10px 0; ';
        $html .= '}';
        $html .= '</style>';
        $html .= '</head>';
        $html .= '<body>';
        $html .= '<div class="container">';
        $html .= '    <div class="header">';
        $html .= '        <h2>' . htmlspecialchars($appName) . '</h2>';
        $html .= '    </div>';
        $html .= '    <div class="content">';
        $html .= '        <p>Hola ' . htmlspecialchars($userName) . ',</p>';
        $html .= '        <div>' . $message . '</div>';
        
        if (!empty($url)) {
            $html .= '    <p style="margin-top: 20px;">';
            $html .= '        <a href="' . htmlspecialchars($url) . '" class="button">Ver detalles</a>';
            $html .= '    </p>';
        }
        
        $html .= '        <p>Gracias por usar nuestros servicios.</p>';
        $html .= '    </div>';
        $html .= '    <div class="footer">';
        $html .= '        <p>Este es un mensaje automático, por favor no responda a este correo.</p>';
        $html .= '    </div>';
        $html .= '</div>';
        $html .= '</body>';
        $html .= '</html>';
        
        return $html;
    }

    private function formatSmsMessage($subject, $message, $options) {
        $appName = $this->appName;
        $shortMessage = strip_tags($message);
        $shortMessage = str_replace(["\n", "\r", "\t"], ' ', $shortMessage);
        $shortMessage = preg_replace('/\s+/', ' ', $shortMessage);
        $shortMessage = trim($shortMessage);
        
        // Limitar a 160 caracteres
        $maxLength = 140; // Dejamos espacio para el prefijo
        if (strlen($shortMessage) > $maxLength) {
            $shortMessage = substr($shortMessage, 0, $maxLength - 3) . '...';
        }
        
        return "[{$appName}] {$shortMessage}";
    }

    /**
     * Notificación de nueva reserva
     */
    public function sendReservationConfirmation($userEmail, $userPhone, $userName, $reservationDetails) {
        $subject = "Confirmación de reserva";
        $message = "Tu reserva ha sido registrada con éxito. " . 
                  "Detalles: {$reservationDetails}";
        
        $this->sendNotification(
            ['email' => $userEmail, 'phone' => $userPhone],
            $subject,
            $message,
            [
                'userName' => $userName,
                'type' => 'success',
                'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/Reservacion_AIP/Public/index.php?view=mis_reservas'
            ]
        );
    }

    /**
     * Notificación de nuevo préstamo
     */
    public function sendLoanConfirmation($userEmail, $userPhone, $userName, $loanDetails) {
        $subject = "Confirmación de préstamo";
        $message = "Tu préstamo ha sido registrado con éxito. " . 
                  "Detalles: {$loanDetails}";
        
        $this->sendNotification(
            ['email' => $userEmail, 'phone' => $userPhone],
            $subject,
            $message,
            [
                'userName' => $userName,
                'type' => 'success',
                'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/Reservacion_AIP/Public/index.php?view=mis_prestamos'
            ]
        );
    }

    /**
     * Notificación de recuperación de contraseña
     */
    public function sendPasswordReset($userEmail, $userPhone, $userName, $resetToken) {
        $resetUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/Reservacion_AIP/Public/restablecer.php?token=' . urlencode($resetToken);
        $subject = "Restablecimiento de contraseña";
        $message = "Para restablecer tu contraseña, haz clic en el siguiente enlace: " . 
                  "<a href='{$resetUrl}'>{$resetUrl}</a><br><br>" .
                  "Si no solicitaste este cambio, puedes ignorar este mensaje.";
        
        $this->sendNotification(
            ['email' => $userEmail, 'phone' => $userPhone],
            $subject,
            $message,
            [
                'userName' => $userName,
                'type' => 'warning',
                'sendSms' => false // No enviamos el enlace por SMS por seguridad
            ]
        );

        // Para SMS, enviamos un mensaje más corto sin enlace
        if ($userPhone) {
            $smsMessage = "Has solicitado restablecer tu contraseña. Por favor revisa tu correo electrónico para continuar.";
            $this->sendSms($userPhone, $smsMessage);
        }
    }

    /**
     * Crear notificación en BD (in-app) para reservas
     * @param PDO $db Conexión a la base de datos
     * @param int $idUsuario ID del usuario que recibirá la notificación
     * @param string $tipoUsuario Rol del usuario (Profesor, Administrador, Encargado)
     * @param array $datosReserva Datos de la reserva (aula, fecha, hora_inicio, hora_fin)
     * @return bool
     */
    public function crearNotificacionReserva($db, $idUsuario, $tipoUsuario, $datosReserva) {
        try {
            $aula = $datosReserva['aula'] ?? 'N/A';
            $fecha = $datosReserva['fecha'] ?? date('Y-m-d');
            $horaInicio = $datosReserva['hora_inicio'] ?? '';
            $horaFin = $datosReserva['hora_fin'] ?? '';
            
            // URL de redirección según el rol
            $url = '';
            if ($tipoUsuario === 'Profesor') {
                $url = 'Historial.php';
                $titulo = '✅ Reserva creada exitosamente';
                $mensaje = "Tu reserva ha sido registrada. Aula: {$aula}, Fecha: {$fecha}, Hora: {$horaInicio} - {$horaFin}";
            } elseif ($tipoUsuario === 'Administrador') {
                $url = 'HistorialGlobal.php';
                $titulo = '🔔 Nueva reserva registrada';
                $mensaje = "Se ha creado una reserva. Aula: {$aula}, Fecha: {$fecha}, Hora: {$horaInicio} - {$horaFin}";
            } else {
                return false; // No enviar notificación a otros roles
            }
            
            $stmt = $db->prepare("INSERT INTO notificaciones (id_usuario, titulo, mensaje, url) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$idUsuario, $titulo, $mensaje, $url]);
        } catch (\Exception $e) {
            error_log("Error al crear notificación de reserva: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear notificación para préstamos vencidos
     * @param PDO $db Conexión a la base de datos
     * @param int $idUsuario ID del usuario que recibirá la notificación
     * @param string $tipoUsuario Rol del usuario
     * @param array $datosPrestamo Datos del préstamo (id_prestamo, solicitante, equipos, hora_fin)
     * @return bool
     */
    public function crearNotificacionPrestamoVencido($db, $idUsuario, $tipoUsuario, $datosPrestamo) {
        try {
            $idPrestamo = $datosPrestamo['id_prestamo'] ?? 0;
            $solicitante = $datosPrestamo['solicitante'] ?? 'Usuario';
            $horaFin = $datosPrestamo['hora_fin'] ?? '';
            $minutosRetraso = $datosPrestamo['minutos_retraso'] ?? 0;
            
            // URL de redirección según el rol
            $url = '';
            if ($tipoUsuario === 'Encargado') {
                $url = 'Devolucion.php';
                $titulo = '⚠️ Préstamo sin devolver';
                $mensaje = "El préstamo #{$idPrestamo} venció hace {$minutosRetraso} minutos. Solicitado por: {$solicitante}. Hora fin: {$horaFin}";
            } elseif ($tipoUsuario === 'Administrador') {
                $url = 'HistorialGlobal.php';
                $titulo = '🔔 Alerta: Préstamo vencido sin devolución';
                $mensaje = "Préstamo #{$idPrestamo} sin devolución desde {$horaFin}. Solicitante: {$solicitante}. Retraso: {$minutosRetraso} min";
            } else {
                return false;
            }
            
            $stmt = $db->prepare("INSERT INTO notificaciones (id_usuario, titulo, mensaje, url) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$idUsuario, $titulo, $mensaje, $url]);
        } catch (\Exception $e) {
            error_log("Error al crear notificación de préstamo vencido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear notificación de devolución (agrupada si es pack)
     * @param PDO $db Conexión a la base de datos
     * @param int $idUsuario ID del usuario que recibirá la notificación
     * @param string $tipoUsuario Rol del usuario
     * @param array $datosDev Datos de la devolución (id_prestamo, equipos[], encargado, hora_confirmacion)
     * @return bool
     */
    public function crearNotificacionDevolucionPack($db, $idUsuario, $tipoUsuario, $datosDev) {
        try {
            $idPrestamo = $datosDev['id_prestamo'] ?? 0;
            $equipos = $datosDev['equipos'] ?? [];
            $encargado = $datosDev['encargado'] ?? 'Encargado';
            $horaConfirmacion = $datosDev['hora_confirmacion'] ?? date('H:i');
            
            $cantidadEquipos = count($equipos);
            $listaEquipos = implode(', ', array_map(function($eq) {
                return $eq['nombre'] ?? 'Equipo';
            }, $equipos));
            
            // URL de redirección según el rol
            $url = '';
            if ($tipoUsuario === 'Profesor') {
                $url = 'Historial.php';
                $titulo = '✅ Devolución confirmada';
                if ($cantidadEquipos > 1) {
                    $mensaje = "{$cantidadEquipos} equipos devueltos: {$listaEquipos}. Confirmado a las {$horaConfirmacion}";
                } else {
                    $mensaje = "Equipo devuelto: {$listaEquipos}. Confirmado a las {$horaConfirmacion}";
                }
            } elseif ($tipoUsuario === 'Administrador') {
                $url = 'HistorialGlobal.php';
                $titulo = '📦 Devolución registrada';
                if ($cantidadEquipos > 1) {
                    $mensaje = "Préstamo #{$idPrestamo} - {$cantidadEquipos} equipos devueltos ({$listaEquipos}) - Confirmado por {$encargado} a las {$horaConfirmacion}";
                } else {
                    $mensaje = "Préstamo #{$idPrestamo} - Equipo devuelto: {$listaEquipos} - Confirmado por {$encargado} a las {$horaConfirmacion}";
                }
            } else {
                return false;
            }
            
            $stmt = $db->prepare("INSERT INTO notificaciones (id_usuario, titulo, mensaje, url) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$idUsuario, $titulo, $mensaje, $url]);
        } catch (\Exception $e) {
            error_log("Error al crear notificación de devolución: " . $e->getMessage());
            return false;
        }
    }
}

