<?php
// app/exportar_pdf.php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../view/login.php');
    exit;
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../controllers/HistorialController.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Asegúrate de tener vendor/autoload.php en project_root/vendor/autoload.php
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

$id_usuario = $_SESSION['id_usuario'] ?? 0;
$controller = new HistorialController($conexion ?? $GLOBALS['conexion'] ?? null);

$semana = intval($_GET['semana'] ?? 0);
$turno = ($_GET['turno'] ?? 'manana') === 'tarde' ? 'tarde' : 'manana';
$datos = $controller->obtenerReservasSemanaPorAula($id_usuario, $semana);

// Construir HTML (similar al handler)
$html = '<html><head><meta charset="utf-8"><style>body{font-family: DejaVu Sans, sans-serif;}</style></head><body>';
$html .= '<h2>Calendario: ' . htmlspecialchars($datos['rango_semana']['inicio']) . ' → ' . htmlspecialchars($datos['rango_semana']['fin']) . '</h2>';
$html .= '<h3>Turno: ' . ($turno === 'manana' ? 'Mañana' : 'Tarde') . '</h3>';

foreach ($datos['aulas'] as $aula) {
    $html .= '<h4>' . htmlspecialchars($aula['nombre_aula']) . '</h4>';
    $html .= '<table width="100%" border="1" cellpadding="4" cellspacing="0">';
    $html .= '<tr><th>Fecha</th><th>Hora Inicio</th><th>Hora Fin</th><th>Profesor</th></tr>';
    foreach ($aula['reservas'] as $fecha => $rs) {
        foreach ($rs as $r) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($r['fecha']) . '</td>';
            $html .= '<td>' . htmlspecialchars(substr($r['hora_inicio'],0,5)) . '</td>';
            $html .= '<td>' . htmlspecialchars(substr($r['hora_fin'],0,5)) . '</td>';
            $html .= '<td>' . htmlspecialchars($r['profesor']) . '</td>';
            $html .= '</tr>';
        }
    }
    $html .= '</table>';
}
$html .= '</body></html>';

if (class_exists('Dompdf\Dompdf')) {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("Historial_{$datos['fecha_inicio']}_{$datos['fecha_fin']}.pdf", ["Attachment" => false]);
    exit;
} else {
    // fallback: mostrar HTML
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}
