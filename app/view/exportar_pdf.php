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
$tipo_usuario = $_SESSION['tipo'] ?? '';
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
if ($tipo_usuario === 'Administrador' || $tipo_usuario === 'Encargado') {
    // Admin/Encargado: ver todos los préstamos
    $prestamos = $prestamos_completos;
} else {
    // Profesor: solo los suyos
    $prestamos = array_values(array_filter($prestamos_completos, function($p) use ($id_usuario) {
        return isset($p['id_usuario']) && (int)$p['id_usuario'] === (int)$id_usuario;
    }));
}

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
$logoFile = realpath(__DIR__ . '/../../Public/img/logo_colegio.png') ?: '../../Public/img/logo_colegio.png';
$fecha_descarga = date('Y-m-d H:i:s');
// Generar HTML
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
    header { display:flex; align-items:center; gap:12px; margin-bottom: 12px; }
    header img { height: 40px; }
    h1 { font-size: 18px; margin: 0; }
    .meta { font-size: 11px; color: #555; margin-bottom: 10px; }
    table { width:100%; border-collapse: collapse; }
    th, td { border: 1px solid #999; padding: 6px 8px; }
    th { background:#f0f0f0; }
    .small { color:#777; font-size: 11px; }
  </style>
  <title>Historial de Préstamos AIP</title>
  </head>
<body>
  <header>
    <?php if (is_file($logoFile)): ?>
      <img src="<?= 'file:///' . str_replace('\\', '/', htmlspecialchars($logoFile)) ?>" alt="Logo" />
    <?php endif; ?>
    <div>
      <h1><?= htmlspecialchars($colegio) ?></h1>
      <div class="meta">
        <?= $tipo_usuario === 'Administrador' || $tipo_usuario === 'Encargado' ? 'Rol: ' . htmlspecialchars($tipo_usuario) : 'Profesor: ' . htmlspecialchars($profesor_nombre) ?> · Semana que inicia: <?= htmlspecialchars($monday) ?> · Turno: <?= htmlspecialchars($turno) ?><br/>
        Generado: <?= htmlspecialchars($fecha_descarga) ?>
      </div>
    </div>
  </header>

  <?php if (!empty($prestamos)): ?>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Profesor</th>
          <th>Equipo</th>
          <th>Aula</th>
          <th>Fecha</th>
          <th>Hora inicio</th>
          <th>Hora fin</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($prestamos as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['id_prestamo']) ?></td>
          <td><?= htmlspecialchars($p['nombre'] ?? $profesor_nombre) ?></td>
          <td><?= htmlspecialchars($p['nombre_equipo'] ?? '') ?></td>
          <td><?= htmlspecialchars($p['nombre_aula'] ?? '') ?></td>
          <td><?= htmlspecialchars($p['fecha_prestamo'] ?? '') ?></td>
          <td><?= htmlspecialchars($p['hora_inicio'] ?? '') ?></td>
          <td><?= htmlspecialchars($p['hora_fin'] ?? '') ?></td>
          <td><?= htmlspecialchars($p['estado'] ?? '') ?></td>
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

// Forzar codificación para Dompdf
$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

// Opciones Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);

try {
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $filename = 'historial_aip_' . $monday . '.pdf';

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
        $mailer->send($toEmail, $subject, $body, [
            ['data' => $pdfData, 'name' => $filename, 'isString' => true]
        ]);
    }

    // Descargar en el navegador
    $dompdf->stream($filename, ['Attachment' => 1]);
    exit;
} catch (\Exception $e) {
    // Guardar HTML para depuración y mostrar mensaje amigable
    @file_put_contents(__DIR__ . '/debug_exportar_pdf.html', $html);
    error_log('Dompdf error: ' . $e->getMessage());
    header('Content-Type: text/plain; charset=utf-8');
    echo "Ocurrió un error al generar el PDF.\n";
    echo "Revisa el archivo debug_exportar_pdf.html en el mismo directorio para ver el HTML que Dompdf intentó renderizar.\n";
    echo 'Error: ' . $e->getMessage();
    exit;
}
