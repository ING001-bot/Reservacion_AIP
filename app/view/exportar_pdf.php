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
// Forzar ambos turnos en el PDF (Mañana y Tarde)
$turno = 'todos';
$prof_like = isset($_POST['profesor']) && $_POST['profesor'] !== '' ? $_POST['profesor'] : null;

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

// Reservas de la semana por aula según rol
if ($tipo_usuario === 'Administrador' || $tipo_usuario === 'Encargado') {
    // Admin/Encargado: ver todos, opcionalmente filtrar por profesor
    $aip1 = $aip_ids[0] ? $controller->obtenerReservasSemanaPorAulaTodos($aip_ids[0], $monday, $prof_like)
                        : array_fill_keys($controller->getWeekDates($monday), []);
    $aip2 = $aip_ids[1] ? $controller->obtenerReservasSemanaPorAulaTodos($aip_ids[1], $monday, $prof_like)
                        : array_fill_keys($controller->getWeekDates($monday), []);
} else {
    // Profesor: solo las suyas
    $aip1 = $aip_ids[0] ? $controller->obtenerReservasSemanaPorAula($aip_ids[0], $monday, $id_usuario)
                        : array_fill_keys($controller->getWeekDates($monday), []);
    $aip2 = $aip_ids[1] ? $controller->obtenerReservasSemanaPorAula($aip_ids[1], $monday, $id_usuario)
                        : array_fill_keys($controller->getWeekDates($monday), []);
}

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

// Zona horaria Perú y datos comunes
date_default_timezone_set('America/Lima');
$colegio = "Colegio Juan Tomis Stack";
$logoFile = realpath(__DIR__ . '/../../Public/img/logo_colegio.png') ?: '../../Public/img/logo_colegio.png';
// Preparar data URI para evitar problemas de file:// y chroot
$logoDataUri = null;
$logoAbs = realpath(__DIR__ . '/../../Public/img/logo_colegio.png');
if ($logoAbs && is_file($logoAbs)) {
    // Asumimos PNG; si cambias el formato, ajusta el mime
    $mime = 'image/png';
    $data = @file_get_contents($logoAbs);
    if ($data !== false) { $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode($data); }
}
// Fallback por URL absoluta si data URI fallara
$logoUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/Sistema_reserva_AIP/Public/img/logo_colegio.png';
$fecha_descarga = date('Y-m-d H:i:s');
// Generar HTML
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <style>
    /* Paleta institucional */
    /* Azul primario */
    .brand-bg { background:#1E6BD6; color:#fff; }
    .brand-text { color:#1E6BD6; }
    .brand-light { background:#EAF2FF; }
    .brand-border { border-color:#C7DAFF !important; }

    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color:#222; }
    header { display:flex; align-items:center; gap:12px; margin-bottom: 12px; padding:10px; border:1px solid #C7DAFF; background:#F7FAFF; border-radius:6px; }
    header img { height: 42px; }
    h1 { font-size: 18px; margin: 0; color:#0F3E91; }
    .meta { font-size: 11px; color: #445; margin-top: 4px; }

    table { width:100%; border-collapse: collapse; }
    th, td { border: 1px solid #C7DAFF; padding: 6px 8px; }
    th { background:#EAF2FF; color:#0F3E91; font-weight:700; }
    tbody tr:nth-child(odd) { background:#FCFDFF; }

    h2 { color:#0F3E91; }
    .small { color:#4a5568; font-size: 11px; }
    .small strong { background:#EAF2FF; color:#0F3E91; padding:2px 6px; border-radius:10px; display:inline-block; }
  </style>
  <title>Historial de Préstamos AIP</title>
  </head>
<body>
  <header>
    <?php if (!empty($logoDataUri)): ?>
      <img src="<?= htmlspecialchars($logoDataUri) ?>" alt="Logo" style="height:60px; width:auto;" />
    <?php else: ?>
      <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" style="height:60px; width:auto;" />
    <?php endif; ?>
    <div>
      <h1><?= htmlspecialchars($colegio) ?></h1>
      <div class="meta">
        <?= $tipo_usuario === 'Administrador' || $tipo_usuario === 'Encargado' ? 'Rol: ' . htmlspecialchars($tipo_usuario) : 'Profesor: ' . htmlspecialchars($profesor_nombre) ?> · Semana que inicia: <?= htmlspecialchars($monday) ?> · Turnos: <?= ($turno==='manana'?'Mañana':($turno==='tarde'?'Tarde':'Mañana y Tarde')) ?><br/>
        Generado: <?= htmlspecialchars($fecha_descarga) ?>
      </div>
    </div>
  </header>

  <!-- Calendarios AIP -->
  <table style="width:100%; margin-bottom: 10px; border:0;">
    <tr>
      <td style="width:50%; vertical-align:top; padding-right:6px;">
        <h2 style="font-size:14px; margin: 8px 0;">AIP 1 <?= $aip_names[0] ? '(' . htmlspecialchars($aip_names[0]) . ')' : '' ?> · Turnos: <?= ($turno==='manana'?'Mañana':($turno==='tarde'?'Tarde':'Mañana y Tarde')) ?></h2>
        <?php $fechas = $controller->getWeekDates($monday); ?>
        <table>
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Reservas</th>
              <th>Cancelaciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($fechas as $f): ?>
            <?php $resv = $aip1[$f] ?? []; $canc = $cancel1[$f] ?? []; ?>
            <tr>
              <td class="nowrap"><?= htmlspecialchars($f) ?></td>
              <td>
                <?php 
                  $resvM = []; $resvT = [];
                  foreach (($resv ?? []) as $r) {
                    $hi = substr($r['hora_inicio'] ?? '', 0, 8);
                    if ($hi !== '' && $hi < '13:00:00') { $resvM[] = $r; } else { $resvT[] = $r; }
                  }
                ?>
                <?php if (!empty($resvM)): ?>
                  <div class="small"><strong>Mañana:</strong></div>
                  <?php foreach ($resvM as $r): ?>
                    <div><?= htmlspecialchars(($r['hora_inicio'] ?? '')) ?> - <?= htmlspecialchars(($r['hora_fin'] ?? '')) ?><?= isset($r['profesor']) && $r['profesor'] !== '' ? (' · ' . htmlspecialchars($r['profesor'])) : '' ?></div>
                  <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($resvT)): ?>
                  <div class="small" style="margin-top:4px;"><strong>Tarde:</strong></div>
                  <?php foreach ($resvT as $r): ?>
                    <div><?= htmlspecialchars(($r['hora_inicio'] ?? '')) ?> - <?= htmlspecialchars(($r['hora_fin'] ?? '')) ?><?= isset($r['profesor']) && $r['profesor'] !== '' ? (' · ' . htmlspecialchars($r['profesor'])) : '' ?></div>
                  <?php endforeach; ?>
                <?php endif; ?>
                <?php if (empty($resvM) && empty($resvT)): ?>
                  <span class="small">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php 
                  $cancM = []; $cancT = [];
                  foreach (($canc ?? []) as $c) {
                    $hi = substr($c['hora_inicio'] ?? '', 0, 8);
                    if ($hi !== '' && $hi < '13:00:00') { $cancM[] = $c; } else { $cancT[] = $c; }
                  }
                ?>
                <?php if (!empty($cancM)): ?>
                  <div class="small"><strong>Mañana:</strong></div>
                  <?php foreach ($cancM as $c): ?>
                    <div><?= htmlspecialchars(($c['hora_inicio'] ?? '')) ?> - <?= htmlspecialchars(($c['hora_fin'] ?? '')) ?> · <?= htmlspecialchars(($c['motivo'] ?? '')) ?></div>
                  <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($cancT)): ?>
                  <div class="small" style="margin-top:4px;"><strong>Tarde:</strong></div>
                  <?php foreach ($cancT as $c): ?>
                    <div><?= htmlspecialchars(($c['hora_inicio'] ?? '')) ?> - <?= htmlspecialchars(($c['hora_fin'] ?? '')) ?> · <?= htmlspecialchars(($c['motivo'] ?? '')) ?></div>
                  <?php endforeach; ?>
                <?php endif; ?>
                <?php if (empty($cancM) && empty($cancT)): ?>
                  <span class="small">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </td>
      <td style="width:50%; vertical-align:top; padding-left:6px;">
        <h2 style="font-size:14px; margin: 8px 0;">AIP 2 <?= $aip_names[1] ? '(' . htmlspecialchars($aip_names[1]) . ')' : '' ?> · Turnos: <?= ($turno==='manana'?'Mañana':($turno==='tarde'?'Tarde':'Mañana y Tarde')) ?></h2>
        <?php $fechas = $controller->getWeekDates($monday); ?>
        <table>
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Reservas</th>
              <th>Cancelaciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($fechas as $f): ?>
            <?php $resv = $aip2[$f] ?? []; $canc = $cancel2[$f] ?? []; ?>
            <tr>
              <td class="nowrap"><?= htmlspecialchars($f) ?></td>
              <td>
                <?php 
                  $resvM = []; $resvT = [];
                  foreach (($resv ?? []) as $r) {
                    $hi = substr($r['hora_inicio'] ?? '', 0, 8);
                    if ($hi !== '' && $hi < '13:00:00') { $resvM[] = $r; } else { $resvT[] = $r; }
                  }
                ?>
                <?php if (!empty($resvM)): ?>
                  <div class="small"><strong>Mañana:</strong></div>
                  <?php foreach ($resvM as $r): ?>
                    <div><?= htmlspecialchars(($r['hora_inicio'] ?? '')) ?> - <?= htmlspecialchars(($r['hora_fin'] ?? '')) ?><?= isset($r['profesor']) && $r['profesor'] !== '' ? (' · ' . htmlspecialchars($r['profesor'])) : '' ?></div>
                  <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($resvT)): ?>
                  <div class="small" style="margin-top:4px;"><strong>Tarde:</strong></div>
                  <?php foreach ($resvT as $r): ?>
                    <div><?= htmlspecialchars(($r['hora_inicio'] ?? '')) ?> - <?= htmlspecialchars(($r['hora_fin'] ?? '')) ?><?= isset($r['profesor']) && $r['profesor'] !== '' ? (' · ' . htmlspecialchars($r['profesor'])) : '' ?></div>
                  <?php endforeach; ?>
                <?php endif; ?>
                <?php if (empty($resvM) && empty($resvT)): ?>
                  <span class="small">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php 
                  $cancM = []; $cancT = [];
                  foreach (($canc ?? []) as $c) {
                    $hi = substr($c['hora_inicio'] ?? '', 0, 8);
                    if ($hi !== '' && $hi < '13:00:00') { $cancM[] = $c; } else { $cancT[] = $c; }
                  }
                ?>
                <?php if (!empty($cancM)): ?>
                  <div class="small"><strong>Mañana:</strong></div>
                  <?php foreach ($cancM as $c): ?>
                    <div><?= htmlspecialchars(($c['hora_inicio'] ?? '')) ?> - <?= htmlspecialchars(($c['hora_fin'] ?? '')) ?> · <?= htmlspecialchars(($c['motivo'] ?? '')) ?></div>
                  <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($cancT)): ?>
                  <div class="small" style="margin-top:4px;"><strong>Tarde:</strong></div>
                  <?php foreach ($cancT as $c): ?>
                    <div><?= htmlspecialchars(($c['hora_inicio'] ?? '')) ?> - <?= htmlspecialchars(($c['hora_fin'] ?? '')) ?> · <?= htmlspecialchars(($c['motivo'] ?? '')) ?></div>
                  <?php endforeach; ?>
                <?php endif; ?>
                <?php if (empty($cancM) && empty($cancT)): ?>
                  <span class="small">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </td>
    </tr>
  </table>

  <?php
    // Agrupar préstamos por sesión (fecha+hora_inicio+hora_fin+aula+usuario)
    $agrupados = [];
    foreach ($prestamos as $p) {
        $key = ($p['fecha_prestamo'] ?? '') . '|' . ($p['hora_inicio'] ?? '') . '|' . ($p['hora_fin'] ?? '') . '|' . ($p['nombre_aula'] ?? '') . '|' . ($p['nombre'] ?? $profesor_nombre);
        if (!isset($agrupados[$key])) {
            $agrupados[$key] = [
                'profesor' => $p['nombre'] ?? $profesor_nombre,
                'aula' => $p['nombre_aula'] ?? '',
                'fecha' => $p['fecha_prestamo'] ?? '',
                'hora_inicio' => $p['hora_inicio'] ?? '',
                'hora_fin' => $p['hora_fin'] ?? '',
                'estado' => $p['estado'] ?? '',
                'equipos' => []
            ];
        }
        $nombreEq = trim((string)($p['nombre_equipo'] ?? 'Equipo'));
        if ($nombreEq !== '') $agrupados[$key]['equipos'][] = $nombreEq;
        // Consolidar estado: si alguno está Prestado, queda Prestado; si todos Devuelto, Devuelto
        if (($p['estado'] ?? '') === 'Prestado') {
            $agrupados[$key]['estado'] = 'Prestado';
        }
    }
  ?>
  <?php if (!empty($agrupados)): ?>
    <h2 style="font-size:14px; margin: 12px 0 6px;">Préstamos de la semana</h2>
    <table>
      <thead>
        <tr>
          <th>Profesor</th>
          <th>Equipos</th>
          <th>Aula</th>
          <th>Fecha</th>
          <th>Hora inicio</th>
          <th>Hora fin</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($agrupados as $g): ?>
        <tr>
          <td><?= htmlspecialchars($g['profesor']) ?></td>
          <td><?= htmlspecialchars(implode(', ', array_unique($g['equipos']))) ?></td>
          <td><?= htmlspecialchars($g['aula']) ?></td>
          <td><?= htmlspecialchars($g['fecha']) ?></td>
          <td><?= htmlspecialchars($g['hora_inicio']) ?></td>
          <td><?= htmlspecialchars($g['hora_fin']) ?></td>
          <td><?= htmlspecialchars($g['estado']) ?></td>
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
