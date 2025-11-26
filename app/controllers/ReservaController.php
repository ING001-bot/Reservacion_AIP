<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require '../models/ReservaModel.php';
require_once __DIR__ . '/../lib/NotificationService.php';
use App\Lib\NotificationService;

class ReservaController {
    private $model;
    public $mensaje = "";
    public $tipo = "info"; // 'success' | 'danger' | 'info'

    public function __construct($conexion) {
        $this->model = new ReservaModel($conexion);
    }

    public function reservarAula($id_aula, $fecha, $hora_inicio, $hora_fin, $id_usuario) {
        // Requerir OTP si es Profesor: válida por toda la sesión
        if (($_SESSION['tipo'] ?? '') === 'Profesor') {
            if (empty($_SESSION['otp_verified'])) {
                $this->mensaje = 'Debes verificar tu identidad con el código SMS antes de confirmar la reserva.';
                $this->tipo = 'danger';
                return false;
            }
        }
        // Validar campos obligatorios
        if (empty($id_aula) || empty($fecha) || empty($hora_inicio) || empty($hora_fin)) {
            $this->mensaje = "⚠️ Debes completar todos los campos de la reserva.";
            $this->tipo = "danger";
            return false;
        }

        // Forzar zona horaria (Perú)
        date_default_timezone_set('America/Lima');

        // Normalizar y validar formato de fecha recibido (esperamos 'Y-m-d')
        $fecha_reserva = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$fecha_reserva) {
            // intentar con otras entradas (por si el input trae otra estructura)
            try {
                $fecha_reserva = new DateTime($fecha);
            } catch (Exception $e) {
                $this->mensaje = "⚠️ Fecha inválida.";
                $this->tipo = "danger";
                return false;
            }
        }

        // Comparar solo fechas (sin hora). Permitir reservas a partir de mañana.
        $hoy = new DateTime('today', new DateTimeZone('America/Lima'));
        $minima = (clone $hoy)->modify('+1 day'); // mañana 00:00

        // Normalizamos ambas a formato Y-m-d para evitar problemas de hora/zona
        $fecha_reserva_str = $fecha_reserva->format('Y-m-d');
        $minima_str = $minima->format('Y-m-d');

        if ($fecha_reserva_str < $minima_str) {
            $this->mensaje = "⚠️ Solo puedes reservar a partir del día siguiente. Las reservas deben hacerse con anticipación, no el mismo día.";
            $this->tipo = "danger";
            return false;
        }

        // Validación de horarios (se asume formato HH:MM o HH:MM:SS)
        // Normalizar horas a HH:MM:SS
        if (strlen($hora_inicio) === 5) $hora_inicio .= ":00";
        if (strlen($hora_fin) === 5) $hora_fin .= ":00";

        if ($hora_inicio >= $hora_fin) {
            $this->mensaje = "⚠️ La hora de inicio debe ser menor a la hora de fin.";
            $this->tipo = "danger";
            return false;
        }

        // Verificar disponibilidad
        if ($this->model->verificarDisponibilidad($id_aula, $fecha_reserva_str, $hora_inicio, $hora_fin)) {
            if ($this->model->crearReserva($id_aula, $id_usuario, $fecha_reserva_str, $hora_inicio, $hora_fin)) {
                $this->mensaje = "✅ Reserva realizada correctamente.";
                $this->tipo = "success";
                
                // Obtener el ID de la reserva recién creada
                $conexion = $this->model->getDb();
                $id_reserva = $conexion->lastInsertId();
                
                // Obtener datos del aula
                $aula = $this->model->obtenerAulaPorId($id_aula);
                $aulaNombre = $aula['nombre_aula'] ?? ('Aula #' . $id_aula);
                
                // NUEVO SISTEMA DE NOTIFICACIONES IN-APP
                try {
                    $notifService = new NotificationService();
                    
                    $datosReserva = [
                        'id_reserva' => $id_reserva,
                        'aula' => $aulaNombre,
                        'fecha' => $fecha_reserva_str,
                        'hora_inicio' => $hora_inicio,
                        'hora_fin' => $hora_fin
                    ];
                    
                    // Notificar al profesor que creó la reserva
                    $notifService->crearNotificacionReserva(
                        $conexion,
                        $id_usuario,
                        'Profesor',
                        $datosReserva
                    );
                    
                    // Notificar a todos los administradores
                    $admins = $this->model->listarUsuariosPorRol(['Administrador']);
                    foreach ($admins as $admin) {
                        $notifService->crearNotificacionReserva(
                            $conexion,
                            (int)$admin['id_usuario'],
                            'Administrador',
                            $datosReserva
                        );
                    }
                    
                    // Notificar a todos los encargados (campanita)
                    $encargados = $this->model->listarUsuariosPorRol(['Encargado']);
                    foreach ($encargados as $encargado) {
                        $notifService->crearNotificacionReserva(
                            $conexion,
                            (int)$encargado['id_usuario'],
                            'Encargado',
                            $datosReserva
                        );
                    }
                } catch (\Exception $e) {
                    error_log("Error al crear notificaciones in-app de reserva: " . $e->getMessage());
                    $encargados = []; // Inicializar vacío en caso de error
                }
                
                // Enviar notificación por email/SMS (mantener sistema existente)
                $userEmail = $_SESSION['correo'] ?? '';
                $userPhone = $_SESSION['telefono'] ?? '';
                $userName = $_SESSION['usuario'] ?? 'Usuario';
                
                if ($userEmail) {
                    register_shutdown_function(function() use ($userEmail, $userPhone, $userName, $aulaNombre, $fecha_reserva_str, $hora_inicio, $hora_fin) {
                        try {
                            $notificationService = new NotificationService();
                            $reservationDetails = "Aula: $aulaNombre, Fecha: $fecha_reserva_str, Hora: $hora_inicio - $hora_fin";
                            
                            $notificationService->sendNotification(
                                ['email' => $userEmail, 'phone' => $userPhone],
                                'Confirmación de reserva - ' . $aulaNombre,
                                'Has realizado una reserva con estos detalles:<br><br>' .
                                '<strong>Aula:</strong> ' . htmlspecialchars($aulaNombre) . '<br>' .
                                '<strong>Fecha:</strong> ' . htmlspecialchars($fecha_reserva_str) . '<br>' .
                                '<strong>Hora inicio:</strong> ' . htmlspecialchars($hora_inicio) . '<br>' .
                                '<strong>Hora fin:</strong> ' . htmlspecialchars($hora_fin) . '<br>' .
                                'Si no fuiste tú, por favor contacta al administrador.',
                                [
                                    'userName' => $userName,
                                    'type' => 'success',
                                    'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/Reservacion_AIP/app/view/Profesor.php?view=notificaciones',
                                    'sendSms' => !empty($userPhone)
                                ]
                            );
                        } catch (\Throwable $e) {
                            error_log('Error al enviar notificación de reserva: ' . $e->getMessage());
                        }
                    });
                }

                // Enviar correo a Encargados con detalles de la reserva
                try {
                    $notificationService = new NotificationService();
                    foreach ($encargados as $u) {
                        if (!empty($u['correo'])) {
                            $notificationService->sendNotification(
                                ['email' => $u['correo']],
                                'Nueva reserva registrada',
                                'Se registró una nueva reserva:<br><br>' .
                                '<strong>Profesor:</strong> ' . htmlspecialchars($_SESSION['usuario'] ?? 'Usuario') . '<br>' .
                                '<strong>Aula:</strong> ' . htmlspecialchars($aulaNombre) . '<br>' .
                                '<strong>Fecha:</strong> ' . htmlspecialchars($fecha_reserva_str) . '<br>' .
                                '<strong>Hora:</strong> ' . htmlspecialchars($hora_inicio) . ' - ' . htmlspecialchars($hora_fin),
                                [
                                    'userName' => ($u['nombre'] ?? 'Encargado'),
                                    'type' => 'info',
                                    'sendSms' => false,
                                    'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/Reservacion_AIP/app/view/Encargado.php?view=notificaciones'
                                ]
                            );
                        }
                    }
                } catch (\Throwable $e) { /* noop */ }
                return true;
            } else {
                $this->mensaje = "❌ Error al realizar la reserva.";
                $this->tipo = "danger";
                return false;
            }
        } else {
            $this->mensaje = "⚠️ Aula ocupada en el horario seleccionado o fuera de las horas permitidas.";
            $this->tipo = "danger";
            return false;
        }
    }

    public function obtenerAulas($tipo = null) {
        return $this->model->obtenerAulas($tipo);
    }

    public function obtenerReservas($id_usuario) {
        return $this->model->obtenerReservasPorProfesor($id_usuario);
    }

    public function obtenerReservasPorFecha($id_aula, $fecha) {
        return $this->model->obtenerReservasPorAulaYFecha($id_aula, $fecha);
    }

    // Nuevo: listar reservas canceladas del usuario
    public function obtenerCanceladas($id_usuario) {
        return $this->model->obtenerCanceladasPorUsuario($id_usuario);
    }

    public function eliminarReserva($id_reserva, $id_usuario) {
        if (!$id_reserva) {
            $this->mensaje = "❌ ID de reserva inválido.";
            $this->tipo = "danger";
            return false;
        }
        // Validar motivo recibido por POST
        $motivo = trim($_POST['motivo'] ?? '');
        if (strlen($motivo) < 10) { // mínimo 10 caracteres para evitar motivos triviales
            $this->mensaje = "⚠️ Debes ingresar un motivo válido (mínimo 10 caracteres).";
            $this->tipo = "danger";
            return false;
        }

        // Ventana de cancelación: hasta 1 hora antes del inicio. Si ya pasó la fecha/hora, no permitir.
        try {
            date_default_timezone_set('America/Lima');
            $reserva = $this->model->obtenerReservaDeUsuario((int)$id_reserva, (int)$id_usuario);
            if (!$reserva) {
                $this->mensaje = "⚠️ No se encontró la reserva o no te pertenece.";
                $this->tipo = "danger";
                return false;
            }
            // Construir DateTime de inicio
            $fecha = $reserva['fecha'];
            $hora_inicio = $reserva['hora_inicio'];
            if (strlen($hora_inicio) === 5) { $hora_inicio .= ':00'; }
            $inicio = new DateTime($fecha . ' ' . $hora_inicio, new \DateTimeZone('America/Lima'));
            $limite = (clone $inicio)->modify('-1 hour');
            $ahora = new DateTime('now', new \DateTimeZone('America/Lima'));

            if ($ahora > $limite) {
                $this->mensaje = "⛔ No puedes cancelar esta reserva. Las cancelaciones solo se permiten hasta 1 hora antes del inicio.";
                $this->tipo = "danger";
                return false;
            }
        } catch (\Throwable $e) {
            // Si algo falla al validar tiempo, por seguridad no permitir
            $this->mensaje = "⛔ No se pudo validar la ventana de cancelación. Inténtalo más tarde.";
            $this->tipo = "danger";
            return false;
        }

        $ok = $this->model->cancelarReserva($id_reserva, $id_usuario, $motivo);
        if ($ok) {
            $this->mensaje = "✅ Reserva cancelada correctamente.";
            $this->tipo = "success";
            
            // Notificaciones por cancelación:
            try {
                $notificationService = new NotificationService();
                $docente = $_SESSION['usuario'] ?? 'Docente';
                $correoDoc = $_SESSION['correo'] ?? '';
                // Al docente (correo)
                if ($correoDoc) {
                    $subjectD = 'Has cancelado una reserva';
                    $messageD = 'Se canceló tu reserva el ' . date('Y-m-d H:i:s') . '.<br>' .
                                '<strong>Motivo:</strong> ' . nl2br(htmlspecialchars($motivo));
                    $notificationService->sendNotification(
                        ['email' => $correoDoc],
                        $subjectD,
                        $messageD,
                        [ 'userName' => $docente, 'type' => 'warning', 'sendSms' => false ]
                    );
                }
                // Al docente (campanita)
                try {
                    $this->model->crearNotificacion((int)$id_usuario, 'Cancelación de reserva', 'Has cancelado una reserva. Motivo: ' . strip_tags($motivo), 'Public/index.php?view=mis_reservas');
                } catch (\Throwable $e) { /* noop */ }
                // A Encargado y Administrador: correo + campanita
                $destinatarios = $this->model->listarUsuariosPorRol(['Encargado','Administrador']);
                $subjectEA = 'Cancelación de reserva por ' . $docente;
                $messageEA = 'Un docente canceló una reserva:<br><br>' .
                             '<strong>Docente:</strong> ' . htmlspecialchars($docente) . ' (' . htmlspecialchars($correoDoc) . ')<br>' .
                             '<strong>Fecha de cancelación:</strong> ' . date('Y-m-d H:i:s') . '<br>' .
                             '<strong>Motivo:</strong> ' . nl2br(htmlspecialchars($motivo));
                foreach ($destinatarios as $u) {
                    // correo
                    if (!empty($u['correo'])) {
                        $notificationService->sendNotification(
                            ['email' => $u['correo']],
                            $subjectEA,
                            $messageEA,
                            [ 'userName' => ($u['nombre'] ?? 'Usuario'), 'type' => 'warning', 'sendSms' => false,
                              'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/Reservacion_AIP/Admin.php?view=historial_global' ]
                        );
                    }
                    // campanita
                    try {
                        $this->model->crearNotificacion((int)$u['id_usuario'], 'Cancelación de reserva', strip_tags($messageEA), '/Reservacion_AIP/Admin.php?view=historial_global');
                    } catch (\Throwable $e) { /*noop*/ }
                }
            } catch (\Throwable $e) {
                error_log('Error al notificar cancelación de reserva: ' . $e->getMessage());
            }
            
            return true;
        } else {
            $this->mensaje = "⚠️ No se pudo cancelar la reserva (verifica que te pertenezca).";
            $this->tipo = "danger";
            return false;
        }
    }
}
