<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../controllers/HistorialGlobalController.php';
require_once __DIR__ . '/../controllers/HistorialController.php';

// DomPDF
require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['tipo'])) {
    http_response_code(403);
    die('Acceso denegado');
}

$rol = $_SESSION['tipo'];
$id_usuario = (int)($_SESSION['id_usuario'] ?? 0);
$profesor_nombre = $_SESSION['usuario'] ?? '';
$start_week = $_POST['start_week'] ?? date('Y-m-d');
$turno = $_POST['turno'] ?? 'manana';
$q = trim((string)($_POST['q'] ?? ''));

$global = new HistorialGlobalController($conexion);
$helper = new HistorialController($conexion);
$monday = $helper->getMondayOfWeek($start_week);
$week = $helper->getWeekDates($monday);
$desde = $week[0];
$hasta = end($week);

// Solo Admin/Encargado pueden descargar PDF desde Historial / Equipos
if (!in_array($rol, ['Administrador','Encargado'])) {
    http_response_code(403);
    die('No autorizado');
}

$opts = ['desde' => $desde, 'hasta' => $hasta, 'tipo' => 'prestamo'];
if ($q !== '') { $opts['profesor'] = $q; }
$prestamos = $global->listarHistorial($opts);

$colegio = 'Colegio Juan Tomis Stack';
$logoFile = realpath(__DIR__ . '/../../Public/img/logo_colegio.png') ?: '../../Public/img/logo_colegio.png';
$fecha_descarga = date('Y-m-d H:i:s');

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
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
  <title>Historial de Préstamos de Equipos</title>
</head>
<body>
  <header>
    <?php if (is_file($logoFile)): ?>
      <img src="<?= 'file:///' . str_replace('\\', '/', htmlspecialchars($logoFile)) ?>" alt="Logo" />
    <?php endif; ?>
    <div>
      <h1><?= htmlspecialchars($colegio) ?></h1>
      <div class="meta">
        Rol: <?= htmlspecialchars($rol) ?> · Semana que inicia: <?= htmlspecialchars($monday) ?> · Turno: <?= htmlspecialchars($turno) ?><br/>
        Filtro: <?= $q !== '' ? htmlspecialchars($q) : 'Sin filtro' ?> · Generado: <?= htmlspecialchars($fecha_descarga) ?>
      </div>
    </div>
  </header>

  <?php if (!empty($prestamos)): ?>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Profesor</th>
          <th>Equipo solicitado</th>
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
          <td><?= htmlspecialchars($p['id'] ?? $p['id_prestamo'] ?? '') ?></td>
          <td><?= htmlspecialchars($p['profesor'] ?? $p['nombre'] ?? '') ?></td>
          <td><?= htmlspecialchars($p['equipo'] ?? $p['nombre_equipo'] ?? '') ?></td>
          <td><?= htmlspecialchars($p['aula'] ?? $p['nombre_aula'] ?? '') ?></td>
          <td><?= htmlspecialchars($p['fecha'] ?? $p['fecha_prestamo'] ?? '') ?></td>
          <td><?= htmlspecialchars($p['hora_inicio'] ?? '') ?></td>
          <td><?= htmlspecialchars($p['hora_fin'] ?? '') ?></td>
          <td><?= htmlspecialchars($p['estado'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="small">No hay préstamos de equipos para la semana seleccionada.</p>
  <?php endif; ?>
</body>
</html>
<?php
$html = ob_get_clean();
$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$filename = 'historial_equipos_' . $monday . '.pdf';
$dompdf->stream($filename, ['Attachment' => 1]);
exit;
