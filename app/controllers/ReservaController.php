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
        // Validar límite horario (usar formato HH:MM:SS para consistencia)
        if ($hora_fin > "18:35" && $hora_fin > "18:35:00") {
            $this->mensaje = "⚠️ El horario excede la hora límite permitida (18:35).";
            return false;
        }

        if ($hora_inicio >= $hora_fin) {
            $this->mensaje = "⚠️ La hora de inicio debe ser menor a la hora de fin.";
            return false;
        }

        if ($this->model->verificarDisponibilidad($id_aula, $fecha, $hora_inicio, $hora_fin)) {
            if ($this->model->crearReserva($id_aula, $id_usuario, $fecha, $hora_inicio, $hora_fin)) {
                $this->mensaje = "✅ Reserva realizada correctamente.";
                return true;
            } else {
                $this->mensaje = "❌ Error al realizar la reserva.";
                return false;
            }
        } else {
            $this->mensaje = "⚠️ Aula ocupada en el horario seleccionado. Por favor elige otro horario.";
            return false;
        }
    }

    public function obtenerAulas($tipo = null) {
        return $this->model->obtenerAulas($tipo);
    }

    public function obtenerReservas($id_usuario) {
        return $this->model->obtenerReservasPorProfesor($id_usuario);
    }

    /*  NUEVO: para el cuadro de horas (no rompe nada) */
    public function obtenerReservasPorFecha($id_aula, $fecha) {
        return $this->model->obtenerReservasPorAulaYFecha($id_aula, $fecha);
    }
    /*  NUEVO: obtener reservas semanales del profesor */

}

// Inicializar controlador
$controller = new ReservaController($conexion);

// Verificar sesión
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'Profesor') {
    echo "Acceso denegado";
    exit();
}

// Procesar formulario (solo guarda si viene 'accion' = guardar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar') {
    $id_usuario  = $_SESSION['id_usuario'];
    $id_aula     = $_POST['id_aula'];
    $fecha       = $_POST['fecha'] ?? null;
    $hora_inicio = $_POST['hora_inicio'] ?? null;
    $hora_fin    = $_POST['hora_fin'] ?? null;

    $controller->reservarAula($id_aula, $fecha, $hora_inicio, $hora_fin, $id_usuario);
}

$mensaje  = $controller->mensaje;
// Solo traer aulas tipo AIP
$aulas    = $controller->obtenerAulas('AIP');
$reservas = $controller->obtenerReservas($_SESSION['id_usuario']);
