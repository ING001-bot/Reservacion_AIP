<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require '../models/ReservaModel.php';

class ReservaController {
    private $model;
    public $mensaje = "";

    public function __construct($conexion) {
        $this->model = new ReservaModel($conexion);
    }

    public function reservarAula($id_aula, $fecha, $hora_inicio, $hora_fin, $id_usuario) {
        // Validación de horarios
        if ($hora_inicio >= $hora_fin) {
            $this->mensaje = "⚠️ La hora de inicio debe ser menor a la hora de fin.";
            return false;
        }

        // Se verifica disponibilidad directamente en el modelo con la lógica de horarios
        if ($this->model->verificarDisponibilidad($id_aula, $fecha, $hora_inicio, $hora_fin)) {
            // Se invoca al método del modelo con los parámetros en el orden correcto
            if ($this->model->crearReserva($id_aula, $id_usuario, $fecha, $hora_inicio, $hora_fin)) {
                $this->mensaje = "✅ Reserva realizada correctamente.";
                return true;
            } else {
                $this->mensaje = "❌ Error al realizar la reserva.";
                return false;
            }
        } else {
            $this->mensaje = "⚠️ Aula ocupada en el horario seleccionado o fuera de las horas permitidas.";
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
}
