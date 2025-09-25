<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/ReservaModel.php';
require_once __DIR__ . '/../models/PrestamoModel.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['tipo'])) {
  http_response_code(403);
  echo json_encode(['error' => 'Acceso denegado']);
  exit;
}

$rol = $_SESSION['tipo'];
if ($rol !== 'Administrador') {
  // Solo Admin ve estadísticas globales
  http_response_code(403);
  echo json_encode(['error' => 'Solo Administrador']);
  exit;
}

$desde    = isset($_GET['desde']) && $_GET['desde'] !== '' ? $_GET['desde'] : null; // YYYY-MM-DD
$hasta    = isset($_GET['hasta']) && $_GET['hasta'] !== '' ? $_GET['hasta'] : null; // YYYY-MM-DD
$profLike = isset($_GET['profesor']) && $_GET['profesor'] !== '' ? $_GET['profesor'] : null;
$aulaLike = isset($_GET['aula']) && $_GET['aula'] !== '' ? $_GET['aula'] : null;
$turno    = isset($_GET['turno']) && $_GET['turno'] !== '' ? $_GET['turno'] : null; // manana | tarde
$tipo     = isset($_GET['tipo']) && $_GET['tipo'] !== '' ? $_GET['tipo'] : null; // reserva | prestamo
$estado   = isset($_GET['estado']) && $_GET['estado'] !== '' ? $_GET['estado'] : null;

try {
  $reservaModel = new ReservaModel($conexion);
  $prestamoModel = new PrestamoModel($conexion);
  $db = $reservaModel->getDb(); // PDO

  // Filtros SQL helpers
  $condFechasReservas = '1=1';
  $paramsR = [];
  if ($desde) { $condFechasReservas .= ' AND r.fecha >= :r_desde'; $paramsR[':r_desde'] = $desde; }
  if ($hasta) { $condFechasReservas .= ' AND r.fecha <= :r_hasta'; $paramsR[':r_hasta'] = $hasta; }
  if ($profLike) { $condFechasReservas .= ' AND u.nombre LIKE :r_prof'; $paramsR[':r_prof'] = "%$profLike%"; }
  if ($aulaLike) { $condFechasReservas .= ' AND a.nombre_aula LIKE :r_aula'; $paramsR[':r_aula'] = "%$aulaLike%"; }

  $condFechasCancel = '1=1';
  $paramsC = [];
  if ($desde) { $condFechasCancel .= ' AND rc.fecha >= :c_desde'; $paramsC[':c_desde'] = $desde; }
  if ($hasta) { $condFechasCancel .= ' AND rc.fecha <= :c_hasta'; $paramsC[':c_hasta'] = $hasta; }
  if ($profLike) { $condFechasCancel .= ' AND u.nombre LIKE :c_prof'; $paramsC[':c_prof'] = "%$profLike%"; }
  if ($aulaLike) { $condFechasCancel .= ' AND a.nombre_aula LIKE :c_aula'; $paramsC[':c_aula'] = "%$aulaLike%"; }

  $condFechasPrest = '1=1';
  $paramsP = [];
  if ($desde) { $condFechasPrest .= ' AND p.fecha_prestamo >= :p_desde'; $paramsP[':p_desde'] = $desde; }
  if ($hasta) { $condFechasPrest .= ' AND p.fecha_prestamo <= :p_hasta'; $paramsP[':p_hasta'] = $hasta; }
  if ($profLike) { $condFechasPrest .= ' AND u.nombre LIKE :p_prof'; $paramsP[':p_prof'] = "%$profLike%"; }
  if ($aulaLike) { $condFechasPrest .= ' AND a.nombre_aula LIKE :p_aula'; $paramsP[':p_aula'] = "%$aulaLike%"; }

  // 1) Conteos básicos
  $sqlReservasCount = "SELECT COUNT(*) AS c FROM reservas r LEFT JOIN usuarios u ON r.id_usuario=u.id_usuario WHERE $condFechasReservas";
  $st = $db->prepare($sqlReservasCount); foreach ($paramsR as $k=>$v) $st->bindValue($k,$v); $st->execute();
  $reservas_count = (int)($st->fetchColumn());

  $sqlCancelCount = "SELECT COUNT(*) AS c FROM reservas_canceladas rc LEFT JOIN usuarios u ON rc.id_usuario=u.id_usuario WHERE $condFechasCancel";
  $st = $db->prepare($sqlCancelCount); foreach ($paramsC as $k=>$v) $st->bindValue($k,$v); $st->execute();
  $cancelaciones_count = (int)($st->fetchColumn());

  $dbP = $prestamoModel->getDb();
  $sqlPrestamosCount = "SELECT COUNT(*) AS c FROM prestamos p JOIN usuarios u ON p.id_usuario=u.id_usuario WHERE $condFechasPrest";
  $st = $dbP->prepare($sqlPrestamosCount); foreach ($paramsP as $k=>$v) $st->bindValue($k,$v); $st->execute();
  $prestamos_count = (int)($st->fetchColumn());

  // 2) Top profesores (reservas)
  $sqlTopProf = "SELECT u.nombre AS profesor, COUNT(*) AS cantidad
                 FROM reservas r LEFT JOIN usuarios u ON r.id_usuario=u.id_usuario
                 WHERE $condFechasReservas
                 GROUP BY u.nombre ORDER BY cantidad DESC LIMIT 10";
  $st = $db->prepare($sqlTopProf); foreach ($paramsR as $k=>$v) $st->bindValue($k,$v); $st->execute();
  $top_profesores_reservas = $st->fetchAll(PDO::FETCH_ASSOC);

  // 3) Top profesores (préstamos)
  $sqlTopProfPrest = "SELECT u.nombre AS profesor, COUNT(*) AS cantidad
                      FROM prestamos p JOIN usuarios u ON p.id_usuario=u.id_usuario
                      WHERE $condFechasPrest
                      GROUP BY u.nombre ORDER BY cantidad DESC LIMIT 10";
  $st = $dbP->prepare($sqlTopProfPrest); foreach ($paramsP as $k=>$v) $st->bindValue($k,$v); $st->execute();
  $top_profesores_prestamos = $st->fetchAll(PDO::FETCH_ASSOC);

  // 4) Top aulas por reservas
  $sqlTopAulas = "SELECT a.nombre_aula AS aula, COUNT(*) AS cantidad
                  FROM reservas r LEFT JOIN aulas a ON r.id_aula=a.id_aula
                  LEFT JOIN usuarios u ON r.id_usuario=u.id_usuario
                  WHERE $condFechasReservas
                  GROUP BY a.nombre_aula ORDER BY cantidad DESC LIMIT 10";
  $st = $db->prepare($sqlTopAulas); foreach ($paramsR as $k=>$v) $st->bindValue($k,$v); $st->execute();
  $top_aulas_reservas = $st->fetchAll(PDO::FETCH_ASSOC);

  // 5) Reservas por día (serie para gráfico)
  $sqlPorDia = "SELECT r.fecha, COUNT(*) AS cantidad
                FROM reservas r LEFT JOIN usuarios u ON r.id_usuario=u.id_usuario
                WHERE $condFechasReservas
                GROUP BY r.fecha ORDER BY r.fecha";
  $st = $db->prepare($sqlPorDia); foreach ($paramsR as $k=>$v) $st->bindValue($k,$v); $st->execute();
  $reservas_por_dia_rows = $st->fetchAll(PDO::FETCH_ASSOC);
  $reservas_por_dia = [];
  foreach ($reservas_por_dia_rows as $row) { $reservas_por_dia[$row['fecha']] = (int)$row['cantidad']; }

  // 6) Horas reservadas totales (suma de duración en horas)
  // Turno: si se especifica, limitar horas
  $turnCond = '';
  if ($turno === 'manana') { $turnCond = " AND TIME(r.hora_inicio) >= '06:00:00' AND TIME(r.hora_fin) <= '12:45:00'"; }
  elseif ($turno === 'tarde') { $turnCond = " AND TIME(r.hora_inicio) >= '13:00:00' AND TIME(r.hora_fin) <= '19:00:00'"; }

  $sqlDuracion = "SELECT SUM(TIMESTAMPDIFF(MINUTE, r.hora_inicio, r.hora_fin)) AS minutos
                  FROM reservas r LEFT JOIN usuarios u ON r.id_usuario=u.id_usuario
                  LEFT JOIN aulas a ON r.id_aula=a.id_aula
                  WHERE $condFechasReservas $turnCond";
  $st = $db->prepare($sqlDuracion); foreach ($paramsR as $k=>$v) $st->bindValue($k,$v); $st->execute();
  $minutos = (int)($st->fetchColumn());
  $horas_reservadas = round($minutos / 60, 2);

  // 7) Top profesores por horas reservadas
  $sqlTopHoras = "SELECT u.nombre AS profesor,
                         ROUND(SUM(TIMESTAMPDIFF(MINUTE, r.hora_inicio, r.hora_fin))/60, 2) AS horas
                  FROM reservas r
                  LEFT JOIN usuarios u ON r.id_usuario=u.id_usuario
                  LEFT JOIN aulas a ON r.id_aula=a.id_aula
                  WHERE $condFechasReservas $turnCond
                  GROUP BY u.nombre
                  ORDER BY horas DESC
                  LIMIT 10";
  $st = $db->prepare($sqlTopHoras); foreach ($paramsR as $k=>$v) $st->bindValue($k,$v); $st->execute();
  $top_profesores_horas = $st->fetchAll(PDO::FETCH_ASSOC);

  // 8) Horas reservadas por día (serie)
  $sqlHorasDia = "SELECT r.fecha, ROUND(SUM(TIMESTAMPDIFF(MINUTE, r.hora_inicio, r.hora_fin))/60, 2) AS horas
                  FROM reservas r
                  LEFT JOIN usuarios u ON r.id_usuario=u.id_usuario
                  LEFT JOIN aulas a ON r.id_aula=a.id_aula
                  WHERE $condFechasReservas $turnCond
                  GROUP BY r.fecha
                  ORDER BY r.fecha";
  $st = $db->prepare($sqlHorasDia); foreach ($paramsR as $k=>$v) $st->bindValue($k,$v); $st->execute();
  $horas_por_dia_rows = $st->fetchAll(PDO::FETCH_ASSOC);
  $horas_por_dia = [];
  foreach ($horas_por_dia_rows as $row) { $horas_por_dia[$row['fecha']] = (float)$row['horas']; }

  // 9) Utilización por aula (horas)
  $sqlUtilAulas = "SELECT a.nombre_aula AS aula,
                          ROUND(SUM(TIMESTAMPDIFF(MINUTE, r.hora_inicio, r.hora_fin))/60, 2) AS horas
                   FROM reservas r
                   LEFT JOIN aulas a ON r.id_aula=a.id_aula
                   LEFT JOIN usuarios u ON r.id_usuario=u.id_usuario
                   WHERE $condFechasReservas $turnCond
                   GROUP BY a.nombre_aula
                   ORDER BY horas DESC
                   LIMIT 10";
  $st = $db->prepare($sqlUtilAulas); foreach ($paramsR as $k=>$v) $st->bindValue($k,$v); $st->execute();
  $utilizacion_aulas = $st->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'ok' => true,
    'resumen' => [
      'reservas' => $reservas_count,
      'cancelaciones' => $cancelaciones_count,
      'prestamos' => $prestamos_count,
      'horas_reservadas' => $horas_reservadas
    ],
    'top_profesores_reservas' => $top_profesores_reservas,
    'top_profesores_prestamos' => $top_profesores_prestamos,
    'top_aulas_reservas' => $top_aulas_reservas,
    'reservas_por_dia' => $reservas_por_dia,
    'top_profesores_horas' => $top_profesores_horas,
    'horas_por_dia' => $horas_por_dia,
    'utilizacion_aulas' => $utilizacion_aulas
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
