<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config/conexion.php';
require '../controllers/HistorialController.php';
require_once __DIR__ . '/../lib/Mailer.php';
use App\Lib\Mailer;

// DomPDF
require '../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(403);
    die("Acceso denegado");
}

$id_usuario = $_SESSION['id_usuario'];
$profesor_nombre = $_SESSION['usuario'] ?? '';
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

$prestamos_completos = $controller->obtenerPrestamos();
$prestamos = array_filter($prestamos_completos, function($p) use ($id_usuario) {
    return isset($p['id_usuario']) && $p['id_usuario'] == $id_usuario;
});

// Cancelaciones del profesor en la semana, mapeadas a las dos aulas AIP seleccionadas
$canceladas_semana = $controller->obtenerCanceladasSemana($monday, $id_usuario);
$cancel1 = array_fill_keys($controller->getWeekDates($monday), []);
$cancel2 = array_fill_keys($controller->getWeekDates($monday), []);
foreach ($canceladas_semana as $c) {
    $fecha = $c['fecha'] ?? null;
    if (!$fecha) continue;
    $item = [
        'hora_inicio' => substr($c['hora_inicio'] ?? '', 0, 8),
        'hora_fin'    => substr($c['hora_fin'] ?? '', 0, 8),
        'motivo'      => $c['motivo'] ?? ''
    ];
    if ($aip_ids[0] && intval($c['id_aula'] ?? 0) === intval($aip_ids[0])) {
        $cancel1[$fecha][] = $item;
    } elseif ($aip_ids[1] && intval($c['id_aula'] ?? 0) === intval($aip_ids[1])) {
        $cancel2[$fecha][] = $item;
    }
}

// Datos
$colegio = "Colegio Juan Tomis Stack";
$logoFile = '../../Public/img/logo_colegio.png'; // ruta relativa desde este archivo
$fecha_descarga = date('Y-m-d H:i:s');

// Helper para imprimir calendario (con cancelaciones)
function printCalendarPdf($aip, $label, $turno, $cancelPorFecha){
    echo "<div class=\"title-section\">" . htmlspecialchars($label) . "</div>";
    $dates = array_keys($aip);
    echo '<table class="table"><thead><tr><th>Hora</th>';
    $diasMap = ['Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado','Sunday'=>'Domingo'];
    foreach($dates as $d) {
        echo '<th>' . htmlspecialchars($diasMap[date('l', strtotime($d))] ?? $d) . '<br><small>' . htmlspecialchars($d) . '</small></th>';
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

    // Enviar por correo al usuario logueado (si tenemos correo en sesión)
    $toEmail = $_SESSION['correo'] ?? '';
    if ($toEmail) {
        $mailer = new Mailer();
        $subject = 'Tu PDF de historial AIP - ' . ($monday ?? 'Semana');
        $body = '<p>Hola,</p>' .
                '<p>Adjuntamos el PDF que acabas de generar.</p>' .
                '<p>Fecha de descarga: ' . htmlspecialchars($fecha_descarga) . '</p>' .
                '<p>Saludos,</p><p>Aulas de Innovación</p>';
        $pdfData = $dompdf->output();
        // Nota: Para adjuntos se recomienda SMTP (PHPMailer). Con mail() básico no se adjuntará.
        $mailer->send($toEmail, $subject, $body, [
            ['data' => $pdfData, 'name' => $filename, 'isString' => true]
        ]);
    }

    // Descargar en el navegador
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
