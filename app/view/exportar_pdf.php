<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config/conexion.php';
require '../controllers/HistorialController.php';

// DomPDF
require '../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(403);
    die("Acceso denegado");
}

$id_usuario = $_SESSION['id_usuario'];
$profesor_nombre = $_SESSION['nombre'] ?? '';
$start_week = $_POST['start_week'] ?? date('Y-m-d');
$turno = $_POST['turno'] ?? 'manana';

$controller = new HistorialController($conexion);
$monday = $controller->getMondayOfWeek($start_week);

// Obtener aulas AIP
$aulas = $controller->obtenerAulasPorTipo('AIP');
$aip_ids = []; $aip_names = [];
foreach ($aulas as $a) {
    $aip_ids[] = $a['id_aula'];
    $aip_names[] = $a['nombre_aula'];
    if (count($aip_ids) >= 2) break;
}
while (count($aip_ids) < 2) { $aip_ids[] = null; $aip_names[] = ''; }

// Solo las reservas del profesor logueado
$aip1 = $aip_ids[0] ? $controller->obtenerReservasSemanaPorAula($aip_ids[0], $monday, $id_usuario)
                    : array_fill_keys($controller->getWeekDates($monday), []);
$aip2 = $aip_ids[1] ? $controller->obtenerReservasSemanaPorAula($aip_ids[1], $monday, $id_usuario)
                    : array_fill_keys($controller->getWeekDates($monday), []);

// Solo los préstamos del profesor logueado
$prestamos_completos = $controller->obtenerPrestamos();
$prestamos = array_filter($prestamos_completos, function($p) use ($id_usuario) {
    return isset($p['id_usuario']) && $p['id_usuario'] == $id_usuario;
});

// Datos
$colegio = "Colegio Juan Tomis Stack";
$logoFile = '../../Public/img/logo_colegio.png'; // ruta relativa desde este archivo
$fecha_descarga = date('Y-m-d H:i:s');

// Preparar logo en base64 (si existe) para evitar problemas de rutas
$logoDataUri = '';
if (file_exists($logoFile) && is_readable($logoFile)) {
    $imgData = file_get_contents($logoFile);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_buffer($finfo, $imgData);
    finfo_close($finfo);
    $base64 = base64_encode($imgData);
    $logoDataUri = "data:$mime;base64,$base64";
}

// GENERAR HTML
ob_start();
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    body{font-family: DejaVu Sans, Arial, sans-serif; font-size:11px; color:#222; margin:10px;}
    .header{display:flex; align-items:center; gap:12px; margin-bottom:8px;}
    .header img{width:80px; height:auto;}
    .h-main{flex:1;}
    .h-main h1{margin:0; font-size:18px;}
    .h-main p{margin:2px 0 0 0; font-size:12px; color:#444;}
    .table{width:100%; border-collapse:collapse; margin-bottom:10px;}
    .table th, .table td{border:1px solid #ddd; padding:6px; font-size:10px;}
    .title-section{background:#f0f0f0; padding:6px; margin-top:8px; font-weight:600;}
    .reserved{background:#ffefc6;}
    .small{font-size:9px;}
  </style>
</head>
<body>
  <div class="header">
    <?php if ($logoDataUri): ?>
      <img src="<?php echo $logoDataUri; ?>" alt="Logo">
    <?php endif; ?>
    <div class="h-main">
      <h1><?php echo htmlspecialchars($colegio); ?></h1>
      <p class="small">Profesor: <?php echo htmlspecialchars($profesor_nombre); ?> — Fecha de descarga: <?php echo htmlspecialchars($fecha_descarga); ?></p>
      <p class="small">Semana (lunes): <?php echo htmlspecialchars($monday); ?> — Turno: <?php echo ($turno==='manana')? 'Mañana (06:00-12:45)' : 'Tarde (13:00-19:00)'; ?></p>
    </div>
  </div>

<?php
// Helper para imprimir calendario
function printCalendarPdf($aip, $label, $turno){
    echo "<div class=\"title-section\">" . htmlspecialchars($label) . "</div>";
    $dates = array_keys($aip);
    echo '<table class="table"><thead><tr><th>Hora</th>';
    $diasMap = ['Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado','Sunday'=>'Domingo'];
    foreach($dates as $d) {
        $key = date('l', strtotime($d));
        $labelDay = $diasMap[$key] ?? $key;
        echo "<th>" . htmlspecialchars($labelDay) . "<br><small>" . htmlspecialchars($d) . "</small></th>";
    }
    echo '</tr></thead><tbody>';

    if ($turno === 'manana') { $start = '06:00'; $end = '12:45'; }
    else { $start = '13:00'; $end = '19:00'; }
    $sh = strtotime($start); $eh = strtotime($end);
    for ($ts = $sh; $ts <= $eh; $ts += 15*60) {
        $time = date('H:i', $ts);
        echo '<tr><td>' . htmlspecialchars($time) . '</td>';
        foreach($dates as $d) {
            $cell = ''; $cls = '';
            foreach($aip[$d] as $r) {
                $hi = substr($r['hora_inicio'],0,5);
                $hf = substr($r['hora_fin'],0,5);
                if ($time >= $hi && $time < $hf) {
                    $cell = $hi . ' - ' . $hf . ' ' . ($r['profesor'] ?? '');
                    $cls = 'reserved';
                    break;
                }
            }
            echo '<td class="' . $cls . '">' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody></table>';
}

printCalendarPdf($aip1, ($aip_names[0]?:'AIP 1'), $turno);
printCalendarPdf($aip2, ($aip_names[1]?:'AIP 2'), $turno);
?>

<?php if (!empty($prestamos)): ?>
  <div class="title-section">Préstamos del profesor</div>
  <table class="table">
    <thead>
      <tr><th>ID</th><th>Equipo</th><th>Aula</th><th>Fecha</th><th>Hora inicio</th><th>Hora fin</th><th>Estado</th></tr>
    </thead>
    <tbody>
    <?php foreach($prestamos as $p): ?>
      <tr>
        <td><?php echo htmlspecialchars($p['id_prestamo']); ?></td>
        <td><?php echo htmlspecialchars($p['nombre_equipo']); ?></td>
        <td><?php echo htmlspecialchars($p['nombre_aula']); ?></td>
        <td><?php echo htmlspecialchars($p['fecha_prestamo']); ?></td>
        <td><?php echo htmlspecialchars($p['hora_inicio'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($p['hora_fin'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($p['estado'] ?? ''); ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p class="small">No hay préstamos registrados para este profesor en la semana seleccionada.</p>
<?php endif; ?>

</body>
</html>
<?php
$html = ob_get_clean();

// force encoding to HTML entities UTF-8 (mejor compatibilidad con Dompdf)
$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

// Opciones Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);

try {
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4','landscape');
    $dompdf->render();
    $filename = 'historial_aip_'.$monday.'.pdf';
    $dompdf->stream($filename, ['Attachment'=>1]);
    exit;
} catch (\Exception $e) {
    // Guardar HTML para depuración y mostrar mensaje amigable
    @file_put_contents(__DIR__ . '/debug_exportar_pdf.html', $html);
    error_log("Dompdf error: " . $e->getMessage());
    header('Content-Type: text/plain; charset=utf-8');
    echo "Ocurrió un error al generar el PDF.\n";
    echo "Revisa el archivo debug_exportar_pdf.html en el mismo directorio para ver el HTML que Dompdf intentó renderizar.\n";
    echo "Error: " . $e->getMessage();
    exit;
}
