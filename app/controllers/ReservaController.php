<?php
session_start();
require '../models/ReservaModel.php';

class ReservaController {
    private $model;
    public $mensaje = "";

    public function __construct($conexion) {
        $this->model = new ReservaModel($conexion);
    }

    public function reservarAula($id_aula, $fecha, $hora_inicio, $hora_fin, $id_usuario) {
        if ($this->model->crearReserva($id_aula, $id_usuario, $fecha, $hora_inicio, $hora_fin)) {
            $this->mensaje = "✅ Reserva realizada correctamente.";
            return true;
        } else {
            $this->mensaje = "❌ Error al realizar la reserva.";
            return false;
        }
    }
}

// Inicializar
$controller = new ReservaController($conexion);

// Verificar sesión
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'Profesor') {
    echo "Acceso denegado";
    exit();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['id_usuario'];
    $fecha = $_POST['fecha'] ?? null;
    $hora_inicio = $_POST['hora_inicio'] ?? null;
    $hora_fin = $_POST['hora_fin'] ?? null;

    // Determinar aula según el botón presionado
    if (isset($_POST['reservar_aip1'])) {
        $controller->reservarAula(1, $fecha, $hora_inicio, $hora_fin, $id_usuario);
    } elseif (isset($_POST['reservar_aip2'])) {
        $controller->reservarAula(2, $fecha, $hora_inicio, $hora_fin, $id_usuario);
    }
}

$mensaje = $controller->mensaje;
