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
$usuario_nombre = $_SESSION['usuario'] ?? '';
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

// Preparar datos comunes y branding
date_default_timezone_set('America/Lima');
$fecha_descarga = date('Y-m-d H:i:s');
$colegio = "Colegio Juan Tomis Stack";
// Cargar logo como data URI (similar a exportar_pdf.php)
$logoDataUri = null;
$logoAbs = realpath(__DIR__ . '/../../Public/img/logo_colegio.png');
if ($logoAbs && is_file($logoAbs)) {
    $mime = 'image/png';
    $data = @file_get_contents($logoAbs);
    if ($data !== false) { $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode($data); }
}
$logoUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/Sistema_reserva_AIP/Public/img/logo_colegio.png';

// Preparar subtítulo con filtros
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
    /* Paleta institucional */
    .brand-bg { background:#1E6BD6; color:#fff; }
    .brand-text { color:#0F3E91; }
    .brand-light { background:#EAF2FF; }
    .brand-border { border-color:#C7DAFF !important; }

    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color:#222; }
    header { display:flex; align-items:center; gap:12px; margin-bottom: 12px; padding:10px; border:1px solid #C7DAFF; background:#F7FAFF; border-radius:6px; }
    header img { height: 50px; }
    h1 { font-size: 18px; margin: 0; color:#0F3E91; }
    .meta { font-size: 11px; color: #445; margin-top: 4px; }

    table { width:100%; border-collapse: collapse; }
    th, td { border: 1px solid #C7DAFF; padding: 6px 8px; }
    th { background:#EAF2FF; color:#0F3E91; font-weight:700; }
    tbody tr:nth-child(odd) { background:#FCFDFF; }

    .small { color:#4a5568; font-size: 11px; }
    .nowrap { white-space: nowrap; }
    .center { text-align:center; }
  </style>
  <title>Reportes y Filtros</title>
</head>
<body>
  <header>
    <?php if (!empty($logoDataUri)): ?>
      <img src="<?= htmlspecialchars($logoDataUri) ?>" alt="Logo" />
    <?php else: ?>
      <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" />
    <?php endif; ?>
    <div>
      <h1>Reportes y Filtros</h1>
      <div class="meta">
        <?= htmlspecialchars($colegio) ?> · Descargado por: <?= htmlspecialchars($usuario_nombre ?: '—') ?> · Rol: <?= htmlspecialchars($rol) ?><?= $sub ? ' · ' . htmlspecialchars($sub) : '' ?> · Generado: <?= htmlspecialchars($fecha_descarga) ?>
      </div>
    </div>
  </header>
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
