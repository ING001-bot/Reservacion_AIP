<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require '../models/ReservaModel.php';

class ReservaController {
    private $model;
    public $mensaje = "";
    public $tipo = "info"; // 'success' | 'danger' | 'info'

    public function __construct($conexion) {
        $this->model = new ReservaModel($conexion);
    }

    public function reservarAula($id_aula, $fecha, $hora_inicio, $hora_fin, $id_usuario) {
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
            $this->mensaje = "⚠️ Solo puedes reservar a partir del día siguiente.";
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

    public function eliminarReserva($id_reserva, $id_usuario) {
        if (!$id_reserva) {
            $this->mensaje = "❌ ID de reserva inválido.";
            $this->tipo = "danger";
            return false;
        }

        $ok = $this->model->eliminarReserva($id_reserva, $id_usuario);
        if ($ok) {
            $this->mensaje = "✅ Reserva cancelada correctamente.";
            $this->tipo = "success";
            return true;
        } else {
            $this->mensaje = "⚠️ No se pudo cancelar la reserva (verifica que te pertenezca).";
            $this->tipo = "danger";
            return false;
        }
    }
}
