<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require "../config/conexion.php";
require '../controllers/ReservaController.php';

$id_aula = $_GET['id_aula'] ?? null;
$fecha   = $_GET['fecha'] ?? null;

$controller = new ReservaController($conexion);
$reservas = $controller->obtenerReservasPorFecha($id_aula, $fecha);

if ($id_aula && $fecha) {
    $t_inicio = strtotime("06:00");
    $t_fin    = strtotime("19:00");
    $intervalo = 45 * 60;

    while ($t_inicio < $t_fin) {
        $inicio_hm = date("H:i", $t_inicio);
        $fin_hm    = date("H:i", $t_inicio + $intervalo);
        $inicio = $inicio_hm . ":00";
        $fin    = $fin_hm . ":00";

        $ocupada = false;
        foreach ($reservas as $res) {
            if ($inicio < $res['hora_fin'] && $fin > $res['hora_inicio']) {
                $ocupada = true;
                break;
            }
        }

        $clase = $ocupada ? "btn btn-danger btn-sm" : "btn btn-success btn-sm";
        echo "<button type='button' class='{$clase} mb-1'>{$inicio_hm} - {$fin_hm}</button>";

        $t_inicio += $intervalo;
    }
} else {
    echo "<small class='text-muted'>Selecciona aula y fecha para ver disponibilidad</small>";
}
