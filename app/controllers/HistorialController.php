<?php
// app/controllers/HistorialController.php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config/conexion.php'; // asegúrate que $conexion está disponible
require '../models/HistorialModel.php';

class HistorialController {
    private $model;

    public function __construct($conexion) {
        $this->model = new HistorialModel($conexion);
    }

    public function obtenerAulas() {
        return $this->model->obtenerAulasAIP();
    }

    public function obtenerReservasSemana($id_aula, $fecha_inicio, $fecha_fin) {
        return $this->model->obtenerReservasPorSemana($id_aula, $fecha_inicio, $fecha_fin);
    }

    public function obtenerPrestamos($id_usuario) {
        return $this->model->obtenerPrestamosPorProfesor($id_usuario);
    }
}

/*
  Si se llama directamente a este archivo por AJAX (desde la vista),
  responde JSON para la acción `reservasSemana`.
  Asegúrate de que la ruta usada por fetch en la vista apunte acá.
*/
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json; charset=utf-8');

    // seguridad: solo profesores
    if (!isset($_SESSION['usuario']) || ($_SESSION['tipo'] ?? '') !== 'Profesor') {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        exit;
    }

    // Preparar controlador
    $ctrl = new HistorialController($conexion);

    $action = $_GET['action'] ?? '';
    if ($action === 'reservasSemana') {
        $semanaOffset = intval($_GET['semana'] ?? 0);

        // calcular lunes y sábado de la semana segun offset
        $inicio = new DateTime();
        if ($semanaOffset !== 0) $inicio->modify($semanaOffset . ' week');
        $inicio->modify('monday this week');
        $fin = clone $inicio;
        $fin->modify('+5 days'); // termina el sábado (lunes + 5 días)

        $fecha_inicio = $inicio->format('Y-m-d');
        $fecha_fin = $fin->format('Y-m-d');

        $aulas = $ctrl->obtenerAulas();
        $response = [
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'aulas' => []
        ];

        foreach ($aulas as $aula) {
            $reservas = $ctrl->obtenerReservasSemana($aula['id_aula'], $fecha_inicio, $fecha_fin);
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
