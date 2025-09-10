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

        // Validar que la fecha sea al menos mañana
        $fecha_actual = new DateTime('today');
        $fecha_reserva = new DateTime($fecha);

        if ($fecha_reserva <= $fecha_actual) {
            $this->mensaje = "⚠️ Solo puedes reservar a partir del día siguiente.";
            $this->tipo = "danger";
            return false;
        }

        // Validación de horarios
        if ($hora_inicio >= $hora_fin) {
            $this->mensaje = "⚠️ La hora de inicio debe ser menor a la hora de fin.";
            $this->tipo = "danger";
            return false;
        }

        // Verificar disponibilidad
        if ($this->model->verificarDisponibilidad($id_aula, $fecha, $hora_inicio, $hora_fin)) {
            if ($this->model->crearReserva($id_aula, $id_usuario, $fecha, $hora_inicio, $hora_fin)) {
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
