<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Prevenir caché del navegador (solo si no es vista embebida)
if (!defined('EMBEDDED_VIEW')) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
}

require "../config/conexion.php";
require '../controllers/ReservaController.php';

$id_aula = $_GET['id_aula'] ?? null;
$fecha   = $_GET['fecha'] ?? null;
$turno   = $_GET['turno'] ?? 'manana'; // 'manana' | 'tarde'

$controller = new ReservaController($conexion);
$reservas = $controller->obtenerReservasPorFecha($id_aula, $fecha);

if ($id_aula && $fecha) {
    $intervalo = 45 * 60;
    if ($turno === 'tarde') {
        // Turno tarde específico
        // Antes del recreo (todos habilitados si están disponibles, incluyendo 16:00)
        $tarde_pre = ['13:00','13:45','14:30','15:15','16:00'];
        foreach ($tarde_pre as $inicio_hm) {
            $inicio = $inicio_hm . ":00";
            $fin_hm = date('H:i', strtotime($inicio_hm) + 45*60);
            $fin    = $fin_hm . ":00";
            $ocupada = false; $half_end = false; $starts_here = false;
            foreach ($reservas as $res) {
                $res_ini = substr($res['hora_inicio'],0,5);
                $res_fin = substr($res['hora_fin'],0,5);
                if ($res_ini === $inicio_hm) { $starts_here = true; }
                if ($res_fin === $inicio_hm) { $half_end = true; }
                if ($inicio < $res['hora_fin'] && $fin > $res['hora_inicio']) { $ocupada = true; }
            }
            $fin_especiales = ['16:00','18:35'];
            if ($starts_here || $ocupada || ($half_end && in_array($inicio_hm, $fin_especiales, true))) {
                echo "<button type='button' class='btn btn-danger btn-sm mb-1' data-time='{$inicio_hm}' disabled>{$inicio_hm}</button>";
            } elseif ($half_end) {
                echo "<button type='button' class='btn btn-success btn-sm btn-half-danger mb-1' data-time='{$inicio_hm}'>{$inicio_hm}</button>";
            } else {
                echo "<button type='button' class='btn btn-success btn-sm mb-1' data-time='{$inicio_hm}'>{$inicio_hm}</button>";
            }
        }
        // Botón de recreo de la tarde (no seleccionable)
        echo "<button type='button' class='btn btn-warning btn-sm mb-1' data-time='16:00-16:20' disabled title='Recreo'>16:00 - 16:20</button>";
        // Después del recreo
        $tarde_post = ['16:20','17:05','17:50','18:35'];
        foreach ($tarde_post as $inicio_hm) {
            $inicio = $inicio_hm . ":00";
            $fin_hm = date('H:i', strtotime($inicio_hm) + 45*60);
            $fin    = $fin_hm . ":00";
            $ocupada = false; $half_end = false; $starts_here = false;
            foreach ($reservas as $res) {
                $res_ini = substr($res['hora_inicio'],0,5);
                $res_fin = substr($res['hora_fin'],0,5);
                if ($res_ini === $inicio_hm) { $starts_here = true; }
                if ($res_fin === $inicio_hm) { $half_end = true; }
                if ($inicio < $res['hora_fin'] && $fin > $res['hora_inicio']) { $ocupada = true; }
            }
            $fin_especiales = ['16:00','18:35'];
            if ($starts_here || $ocupada || ($half_end && in_array($inicio_hm, $fin_especiales, true))) {
                echo "<button type='button' class='btn btn-danger btn-sm mb-1' data-time='{$inicio_hm}' disabled>{$inicio_hm}</button>";
            } elseif ($half_end) {
                echo "<button type='button' class='btn btn-success btn-sm btn-half-danger mb-1' data-time='{$inicio_hm}'>{$inicio_hm}</button>";
            } else {
                echo "<button type='button' class='btn btn-success btn-sm mb-1' data-time='{$inicio_hm}'>{$inicio_hm}</button>";
            }
        }
    } else {
        $morning_starts = ['06:00','06:45'];
        $s = strtotime('07:10');
        $pre_recreo_fin = strtotime('10:10');
        while ($s < $pre_recreo_fin) { $morning_starts[] = date('H:i', $s); $s += 45*60; }
        $morning_starts[] = '10:10'; // 10:10 debe ser seleccionable
        $s = strtotime('10:30');
        $last = strtotime('12:45');
        while ($s <= $last) { $morning_starts[] = date('H:i', $s); $s += 45*60; }

        foreach ($morning_starts as $inicio_hm) {
            $inicio = $inicio_hm . ":00";
            $fin_hm = date('H:i', strtotime($inicio_hm) + 45*60);
            $fin    = $fin_hm . ":00";
            $ocupada = false; $half_end = false; $starts_here = false;
            foreach ($reservas as $res) {
                $res_ini = substr($res['hora_inicio'],0,5);
                $res_fin = substr($res['hora_fin'],0,5);
                if ($res_ini === $inicio_hm) { $starts_here = true; }
                if ($res_fin === $inicio_hm) { $half_end = true; }
                if ($inicio < $res['hora_fin'] && $fin > $res['hora_inicio']) { $ocupada = true; }
            }
            $fin_especiales = ['10:10','12:45'];
            if ($starts_here || $ocupada || ($half_end && in_array($inicio_hm, $fin_especiales, true))) {
                echo "<button type='button' class='btn btn-danger btn-sm mb-1' data-time='{$inicio_hm}' disabled>{$inicio_hm}</button>";
            } elseif ($half_end) {
                echo "<button type='button' class='btn btn-success btn-sm btn-half-danger mb-1' data-time='{$inicio_hm}'>{$inicio_hm}</button>";
            } else {
                echo "<button type='button' class='btn btn-success btn-sm mb-1' data-time='{$inicio_hm}'>{$inicio_hm}</button>";
            }
            if ($inicio_hm === '10:10') {
                // Botón de recreo de la mañana (no seleccionable), colocado inmediatamente después de 10:10
                echo "<button type='button' class='btn btn-warning btn-sm mb-1' data-time='10:10-10:30' disabled title='Recreo'>10:10 - 10:30</button>";
            }
        }
    }
    // Sin botón adicional de fin en turno tarde
} else {
    echo "<small class='text-muted'>Selecciona aula y fecha para ver disponibilidad</small>";
}
