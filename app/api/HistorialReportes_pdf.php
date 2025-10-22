<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../controllers/HistorialGlobalController.php';

// DomPDF
require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['tipo'])) {
    http_response_code(403);
    echo 'Acceso denegado';
    exit;
}

$rol = $_SESSION['tipo'];
$controller = new HistorialGlobalController($conexion);

// Filtros como en HistorialGlobal_fetch
$opts = [];
if ($rol === 'Administrador') {
    $opts['desde']    = isset($_GET['desde']) && $_GET['desde'] !== '' ? $_GET['desde'] : null;
    $opts['hasta']    = isset($_GET['hasta']) && $_GET['hasta'] !== '' ? $_GET['hasta'] : null;
    $opts['profesor'] = isset($_GET['profesor']) && $_GET['profesor'] !== '' ? $_GET['profesor'] : null;
    $opts['tipo']     = isset($_GET['tipo']) && $_GET['tipo'] !== '' ? $_GET['tipo'] : null; // reserva | prestamo
    $opts['estado']   = isset($_GET['estado']) && $_GET['estado'] !== '' ? $_GET['estado'] : null;
}

try {
    $rows = $controller->listarHistorial($opts);
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error al consultar datos: ' . $e->getMessage();
    exit;
}

// Preparar HTML del PDF
$fecha_descarga = date('Y-m-d H:i:s');
$subtitulo = [];
if (!empty($opts['desde']) || !empty($opts['hasta'])) {
    $subtitulo[] = 'Rango: ' . ($opts['desde'] ?? '—') . ' a ' . ($opts['hasta'] ?? '—');
}
if (!empty($opts['profesor'])) $subtitulo[] = 'Profesor: ' . $opts['profesor'];
if (!empty($opts['tipo']))     $subtitulo[] = 'Tipo: ' . ucfirst($opts['tipo']);
if (!empty($opts['estado']))   $subtitulo[] = 'Estado: ' . $opts['estado'];
$sub = implode(' · ', $subtitulo);

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color:#222; }
    h1 { font-size: 18px; margin: 0 0 6px 0; }
    .meta { font-size: 11px; color: #555; margin-bottom: 10px; }
    table { width:100%; border-collapse: collapse; }
    th, td { border: 1px solid #999; padding: 6px 8px; }
    th { background:#f0f0f0; font-weight:700; }
    td.center, th.center { text-align:center; }
    .small { color:#777; font-size: 11px; }
    .nowrap { white-space: nowrap; }
  </style>
  <title>Reportes y Filtros</title>
</head>
<body>
  <h1>Reportes y Filtros</h1>
  <div class="meta">
    Rol: <?= htmlspecialchars($rol) ?><?= $sub ? ' · ' . htmlspecialchars($sub) : '' ?> · Generado: <?= htmlspecialchars($fecha_descarga) ?>
  </div>
  <?php if (!empty($rows)): ?>
    <table>
      <thead>
        <tr>
          <th class="nowrap">Fecha</th>
          <th class="nowrap center">Inicio</th>
          <th class="nowrap center">Fin</th>
          <th>Profesor</th>
          <th>Aula/Equipo</th>
          <th class="center">Tipo</th>
          <th class="center">Estado</th>
          <th>Observación</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $it):
        $aulaEquipoHtml = $it['equipo'] ?? '';
        // Para PDF usamos texto plano: chips -> texto (reemplazar múltiples espacios)
        $aulaEquipo = trim(preg_replace('/\s+/', ' ', strip_tags($aulaEquipoHtml)));
        if ($aulaEquipo === '') { $aulaEquipo = (string)($it['aula'] ?? ''); }
      ?>
        <tr>
          <td class="nowrap"><?= htmlspecialchars($it['fecha'] ?? '') ?></td>
          <td class="center nowrap"><?= htmlspecialchars(substr($it['hora_inicio'] ?? '', 0, 5)) ?></td>
          <td class="center nowrap"><?= htmlspecialchars(substr($it['hora_fin'] ?? '', 0, 5)) ?></td>
          <td><?= htmlspecialchars($it['profesor'] ?? '') ?></td>
          <td><?= htmlspecialchars($aulaEquipo) ?></td>
          <td class="center"><?= htmlspecialchars($it['tipo'] ?? '') ?></td>
          <td class="center"><?= htmlspecialchars($it['estado'] ?? '') ?></td>
          <td><?= htmlspecialchars($it['observacion'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="small">No hay resultados para los filtros seleccionados.</p>
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

try {
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $filename = 'reportes_filtros_' . date('Ymd_His') . '.pdf';
    $dompdf->stream($filename, ['Attachment' => 1]);
    exit;
} catch (\Exception $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Ocurrió un error al generar el PDF. ' . $e->getMessage();
    exit;
}
