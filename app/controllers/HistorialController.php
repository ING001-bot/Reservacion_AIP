<?php
// app/controllers/HistorialController.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../models/HistorialModel.php';

class HistorialController {
    private $model;

    // Recibe $conexion (PDO) y lo pasa al model
    public function __construct(PDO $conexion = null) {
        $this->model = new HistorialModel($conexion);
    }

    // Calcula rango lunes..sábado a partir de weekOffset (0 = semana actual)
    private function calcularRangoSemana(int $weekOffset = 0) : array {
        $monday = new DateTime();
        $monday->modify('monday this week');
        if ($weekOffset !== 0) {
            $monday->modify(($weekOffset * 7) . ' days');
        }
        $saturday = (clone $monday)->modify('+5 days');

        return [
            'fecha_inicio' => $monday->format('Y-m-d'),
            'fecha_fin' => $saturday->format('Y-m-d'),
            'label_inicio' => $monday->format('d/m/Y'),
            'label_fin' => $saturday->format('d/m/Y')
        ];
    }

    // Devuelve estructura preparada para la vista/JS
    public function obtenerReservasSemanaPorAula($id_usuario, int $weekOffset = 0) {
        $rango = $this->calcularRangoSemana($weekOffset);
        // Traer solo aulas AIP (AIP1, AIP2)
        $aulas = $this->model->obtenerAulas('AIP');

        $datos = [
            'aulas' => [],
            'fecha_inicio' => $rango['fecha_inicio'],
            'fecha_fin' => $rango['fecha_fin'],
            'rango_semana' => ['inicio' => $rango['label_inicio'], 'fin' => $rango['label_fin']]
        ];

        foreach ($aulas as $aula) {
            $reservas = $this->model->obtenerReservasSemana(
                $aula['id_aula'],
                $rango['fecha_inicio'],
                $rango['fecha_fin'],
                $id_usuario
            );

            // Agrupar por fecha YYYY-MM-DD
            $agr = [];
            foreach ($reservas as $r) {
                $f = $r['fecha'];
                if (!isset($agr[$f])) $agr[$f] = [];
                $agr[$f][] = $r;
            }

            $datos['aulas'][] = [
                'id_aula' => $aula['id_aula'],
                'nombre_aula' => $aula['nombre_aula'],
                'reservas' => $agr
            ];
        }

        return $datos;
    }

    // Historial de préstamos
    public function obtenerPrestamosPorProfesor($id_usuario) {
        return $this->model->obtenerPrestamosPorProfesor($id_usuario);
    }
}

/********************************************************
 * Handler AJAX / export (si se invoca este archivo directamente)
 ********************************************************/
if (php_sapi_name() !== 'cli' && (isset($_GET['action']) || isset($_POST['action']))) {
    // Intentar cargar la conexión desde varias rutas posibles
    if (file_exists(__DIR__ . '/../config/conexion.php')) {
        require_once __DIR__ . '/../config/conexion.php';
    } elseif (file_exists(__DIR__ . '/../../config/conexion.php')) {
        require_once __DIR__ . '/../../config/conexion.php';
    } elseif (!isset($conexion) && isset($GLOBALS['conexion'])) {
        $conexion = $GLOBALS['conexion'];
    }

    $action = $_GET['action'] ?? $_POST['action'];
    $ctrl = new HistorialController($conexion ?? null);
    $userId = $_SESSION['id_usuario'] ?? null;

    if ($action === 'reservasSemana') {
        $week = isset($_GET['semana']) ? intval($_GET['semana']) : 0;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($ctrl->obtenerReservasSemanaPorAula($userId, $week), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'exportPdf') {
        $week = isset($_GET['semana']) ? intval($_GET['semana']) : 0;
        $turno = isset($_GET['turno']) && in_array($_GET['turno'], ['manana','tarde']) ? $_GET['turno'] : 'manana';
        $datos = $ctrl->obtenerReservasSemanaPorAula($userId, $week);

        // Generar HTML básico para PDF
        $html = '<h2>Calendario - Semana: ' . htmlspecialchars($datos['rango_semana']['inicio']) . ' → ' . htmlspecialchars($datos['rango_semana']['fin']) . '</h2>';
        $html .= '<h3>Turno: ' . ($turno === 'manana' ? 'Mañana' : 'Tarde') . '</h3>';
        foreach ($datos['aulas'] as $aula) {
            $html .= '<h4>' . htmlspecialchars($aula['nombre_aula']) . '</h4>';
            $html .= '<ul>';
            foreach ($aula['reservas'] as $fecha => $rs) {
                $html .= '<li><strong>' . htmlspecialchars($fecha) . '</strong>: ';
                $parts = [];
                foreach ($rs as $r) {
                    $parts[] = htmlspecialchars(substr($r['hora_inicio'],0,5) . ' - ' . substr($r['hora_fin'],0,5) . ' (' . $r['profesor'] . ')');
                }
                $html .= implode(', ', $parts);
                $html .= '</li>';
            }
            $html .= '</ul>';
        }

        // Intentar usar Dompdf (buscar vendor/autoload.php)
        $autoload = null;
        if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
            $autoload = __DIR__ . '/../../vendor/autoload.php';
        } elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            $autoload = __DIR__ . '/../vendor/autoload.php';
        }

        if ($autoload) {
            require_once $autoload;
            if (class_exists('Dompdf\Dompdf')) {
                $dompdf = new Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $dompdf->stream("calendario_{$datos['fecha_inicio']}_{$datos['fecha_fin']}.pdf", ["Attachment" => 1]);
                exit;
            }
        }

        // Fallback: mostrar HTML para imprimir manualmente
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'action no válida']);
    exit;
}
