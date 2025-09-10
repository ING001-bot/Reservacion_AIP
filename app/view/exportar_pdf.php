<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config/conexion.php';
require '../controllers/HistorialController.php';

// Ajusta la ruta a tu DomPDF descargado
require '../../vendor/autoload.php';
use Dompdf\Dompdf;

$start_week = $_POST['start_week'] ?? date('Y-m-d');
$turno = $_POST['turno'] ?? 'manana';

$controller = new HistorialController($conexion);
$monday = $controller->getMondayOfWeek($start_week);

// Obtener las dos aulas AIP (primera y segunda) usando el controller
$aulas = $controller->obtenerAulasPorTipo('AIP');
$aip_ids = []; $aip_names = [];
foreach ($aulas as $a) { $aip_ids[] = $a['id_aula']; $aip_names[] = $a['nombre_aula']; if (count($aip_ids) >= 2) break; }
while (count($aip_ids) < 2) { $aip_ids[] = null; $aip_names[] = ''; }

$aip1 = $aip_ids[0] ? $controller->obtenerReservasSemanaPorAula($aip_ids[0], $monday) : array_fill_keys($controller->getWeekDates($monday), []);
$aip2 = $aip_ids[1] ? $controller->obtenerReservasSemanaPorAula($aip_ids[1], $monday) : array_fill_keys($controller->getWeekDates($monday), []);
$prestamos = $controller->obtenerPrestamos();

// Datos de encabezado
$colegio = "Colegio Juan Tomis Stack";
$profesor = $_SESSION['nombre'] ?? '';
$logoPath = __DIR__ . '/../../Public/img/logo_colegio.png'; // ruta física para DomPDF
$fecha_descarga = date('Y-m-d H:i:s');

ob_start();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
body{font-family: DejaVu Sans, Arial, sans-serif; font-size:11px; color:#222;}
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
  <?php if (file_exists($logoPath)): ?>
    <img src="<?php echo $logoPath; ?>" alt="Logo">
  <?php endif; ?>
  <div class="h-main">
    <h1><?php echo htmlspecialchars($colegio); ?></h1>
    <p class="small">Profesor: <?php echo htmlspecialchars($profesor); ?> — Fecha de descarga: <?php echo $fecha_descarga; ?></p>
    <p class="small">Semana (lunes): <?php echo $monday; ?> — Turno: <?php echo ($turno==='manana')? 'Mañana (06:00-12:45)' : 'Tarde (13:00-19:00)'; ?></p>
  </div>
</div>

<?php
function printCalendarPdf($aip, $label, $turno){
    echo "<div class=\"title-section\">$label</div>";
    $dates = array_keys($aip); // 6 dias
    echo '<table class="table"><thead><tr><th>Hora</th>';
    foreach($dates as $d) {
        // Mostrar Lunes..Sabado en encabezado
        $weekday = ucfirst(strftime('%A', strtotime($d))); // requiere locales, pero mostrará en inglés si no está en es
        // preferimos usar una mapping sencilla en español:
        $diasMap = ['Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado','Sunday'=>'Domingo'];
        $key = strftime('%A', strtotime($d));
        $labelDay = $diasMap[$key] ?? $key;
        echo "<th>{$labelDay}<br><small>{$d}</small></th>";
    }
    echo '</tr></thead><tbody>';

    if ($turno === 'manana') { $start = '06:00'; $end = '12:45'; }
    else { $start = '13:00'; $end = '19:00'; }
    $sh = strtotime($start); $eh = strtotime($end);
    for ($ts = $sh; $ts <= $eh; $ts += 15*60) {
        $time = date('H:i', $ts);
        echo '<tr><td>'.$time.'</td>';
        foreach($dates as $d) {
            $cell = ''; $cls = '';
            foreach($aip[$d] as $r) {
                if ($time >= substr($r['hora_inicio'],0,5) && $time < substr($r['hora_fin'],0,5)) {
                    $cell = substr($r['hora_inicio'],0,5) . ' - ' . substr($r['hora_fin'],0,5) . ' ' . ($r['profesor'] ?? '');
                    $cls = 'reserved';
                    break;
                }
            }
            echo '<td class="'.$cls.'">'.($cell?:'').'</td>';
        }
        echo '</tr>';
    }

    echo '</tbody></table>';
}

printCalendarPdf($aip1, ($aip_names[0]?:'AIP 1'), $turno);
printCalendarPdf($aip2, ($aip_names[1]?:'AIP 2'), $turno);
?>

<?php if (!empty($prestamos)): ?>
<div class="title-section">Préstamos</div>
<table class="table">
<thead><tr><th>ID</th><th>Equipo</th><th>Profesor</th><th>Aula</th><th>Fecha</th><th>Hora inicio</th><th>Hora fin</th><th>Estado</th></tr></thead>
<tbody>
<?php foreach($prestamos as $p): ?>
<tr>
  <td><?php echo $p['id_prestamo']; ?></td>
  <td><?php echo $p['nombre_equipo']; ?></td>
  <td><?php echo $p['nombre']; ?></td>
  <td><?php echo $p['nombre_aula']; ?></td>
  <td><?php echo $p['fecha_prestamo']; ?></td>
  <td><?php echo $p['hora_inicio']; ?></td>
  <td><?php echo $p['hora_fin']; ?></td>
  <td><?php echo $p['estado']; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

</body>
</html>
<?php
$html = ob_get_clean();
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4','landscape');
$dompdf->render();
$filename = 'historial_aip_'.$monday.'.pdf';
$dompdf->stream($filename, ['Attachment'=>1]);
exit;
