<?php
session_start();
require '../config/conexion.php';
require '../models/PrestamoModel.php';

class DevolucionController {
    private $model;
    public $mensaje = "";

    public function __construct($conexion) {
        $this->model = new PrestamoModel($conexion);
    }

    // Procesar devolución
    public function procesarDevolucion($id) {
        if (!ctype_digit($id)) {
            $this->mensaje = "❌ ID de préstamo inválido.";
            return false;
        }

        if ($this->model->devolverEquipo($id)) {
            $this->mensaje = "✅ Equipo devuelto correctamente.";
            return true;
        } else {
            $this->mensaje = "❌ Error al devolver el equipo.";
            return false;
        }
    }

    // Obtener todos los préstamos
    public function obtenerPrestamos() {
        return $this->model->obtenerTodosPrestamos();
    }
}

// --- Inicializar controlador ---
$controller = new DevolucionController($conexion);

// --- Verificar sesión y rol ---
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['tipo'] !== 'Encargado') {
    echo "Acceso denegado";
    exit();
}

// --- Procesar devolución si llega por GET ---
if (isset($_GET['devolver'])) {
    $controller->procesarDevolucion($_GET['devolver']);
}

// --- Obtener préstamos ---
$prestamos = $controller->obtenerPrestamos();
$mensaje = $controller->mensaje;

// --- Cargar la vista ---
require '../view/Devolucion.php';
