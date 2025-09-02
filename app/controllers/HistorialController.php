<?php
// app/controllers/HistorialController.php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config/conexion.php';
require '../models/HistorialModel.php';

class HistorialController {
    private $model;

    public function __construct($conexion) {
        $this->model = new HistorialModel($conexion);
    }

    public function obtenerAulas() {
        return $this->model->obtenerAulasAIP();
    }

    // 👇 ahora recibe $id_usuario
    public function obtenerReservasSemana($id_aula, $fecha_inicio, $fecha_fin, $id_usuario) {
        return $this->model->obtenerReservasPorSemana($id_aula, $fecha_inicio, $fecha_fin, $id_usuario);
    }

    public function obtenerPrestamos($id_usuario) {
        return $this->model->obtenerPrestamosPorProfesor($id_usuario);
    }
}

/* Endpoint AJAX */
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['usuario']) || ($_SESSION['tipo'] ?? '') !== 'Profesor') {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        exit;
    }

    $ctrl = new HistorialController($conexion);
    $action = $_GET['action'] ?? '';

    if ($action === 'reservasSemana') {
        $semanaOffset = intval($_GET['semana'] ?? 0);

        $inicio = new DateTime();
        $inicio->modify($semanaOffset . ' week monday this week'); // <-- Corregido
        $fin = clone $inicio;
        $fin->modify('+5 days'); // lunes..sábado

        $fecha_inicio = $inicio->format('Y-m-d');
        $fecha_fin    = $fin->format('Y-m-d');

        $id_usuario = $_SESSION['id_usuario']; // 🔒 profesor logueado

        $aulas = $ctrl->obtenerAulas();
        $response = [
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'aulas' => []
        ];

        foreach ($aulas as $aula) {
            $reservas = $ctrl->obtenerReservasSemana($aula['id_aula'], $fecha_inicio, $fecha_fin, $id_usuario);
            $response['aulas'][] = [
                'id_aula' => $aula['id_aula'],
                'nombre_aula' => $aula['nombre_aula'],
                'reservas' => $reservas
            ];
        }

        echo json_encode($response);
        exit;
    }

    echo json_encode(['error' => 'Acción no válida']);
    exit;
}
