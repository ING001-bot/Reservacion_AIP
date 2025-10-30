<?php
if (session_status() === PHP_SESSION_NONE) session_start();
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
        // 13:00 hasta 18:15 (para que el fin sea 19:00)
        $t_inicio = strtotime('13:00');
        $t_ult_inicio = strtotime('18:15');
        while ($t_inicio <= $t_ult_inicio) {
            $inicio_hm = date('H:i', $t_inicio);
            $fin_hm    = date('H:i', $t_inicio + $intervalo);
            $inicio = $inicio_hm . ":00";
            $fin    = $fin_hm . ":00";

            $ocupada = false;
            foreach ($reservas as $res) {
                if ($inicio >= $res['hora_inicio'] && $inicio <= $res['hora_fin']) { $ocupada = true; break; }
            }
            $clase = $ocupada ? 'btn btn-danger btn-sm' : 'btn btn-success btn-sm';
            echo "<button type='button' class='{$clase} mb-1' data-time='{$inicio_hm}'>{$inicio_hm}</button>";
            $t_inicio += $intervalo;
        }
    } else {
        // mañana: 06:00 y 06:45, luego desde 07:00 cada 45 minutos hasta 12:45
        $morning_starts = ['06:00','06:45'];
        $s = strtotime('07:00');
        $last = strtotime('12:45');
        while ($s <= $last) { $morning_starts[] = date('H:i',$s); $s += 45*60; }
        // Asegurar 12:45 explícitamente
        if (!in_array('12:45', $morning_starts, true)) { $morning_starts[] = '12:45'; }

        foreach ($morning_starts as $inicio_hm) {
            $inicio = $inicio_hm . ":00";
            $fin_hm = date('H:i', strtotime($inicio_hm) + 45*60);
            $fin    = $fin_hm . ":00";
            $ocupada = false;
            foreach ($reservas as $res) {
                if ($inicio >= $res['hora_inicio'] && $inicio <= $res['hora_fin']) { $ocupada = true; break; }
            }
            $clase = $ocupada ? 'btn btn-danger btn-sm' : 'btn btn-success btn-sm';
            echo "<button type='button' class='{$clase} mb-1' data-time='{$inicio_hm}'>{$inicio_hm}</button>";
        }
    }
    // Agregar casilla de las 19:00 como punto final cuando es turno tarde
    if ($turno === 'tarde') {
        $ocupada19 = false;
        foreach ($reservas as $res) {
            if ('19:00:00' >= $res['hora_inicio'] && '19:00:00' <= $res['hora_fin']) { $ocupada19 = true; break; }
        }
        $cls19 = $ocupada19 ? 'btn btn-danger btn-sm' : 'btn btn-success btn-sm';
        echo "<button type='button' class='{$cls19} mb-1' data-time='19:00'>19:00</button>";
    }
} else {
    echo "<small class='text-muted'>Selecciona aula y fecha para ver disponibilidad</small>";
}
