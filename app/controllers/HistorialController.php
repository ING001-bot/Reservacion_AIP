<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require '../models/ReservaModel.php';
require '../models/PrestamoModel.php';

class HistorialController {
    private $reservaModel;
    private $prestamoModel;

    public function __construct($conexion) {
        $this->reservaModel = new ReservaModel($conexion);
        $this->prestamoModel = new PrestamoModel($conexion);
    }

    // Devuelve el lunes de la semana de referencia (YYYY-MM-DD)
    public function getMondayOfWeek($date) {
        $ts = strtotime($date);
        $dayOfWeek = date('N', $ts); // 1 (mon) - 7 (sun)
        $offset = $dayOfWeek - 1;
        $mondayTs = strtotime("-{$offset} days", $ts);
        return date('Y-m-d', $mondayTs);
    }

    // Lunes -> [Lun, Mar, Mie, Jue, Vie, Sab]
    public function getWeekDates($monday) {
        $dates = [];
        $ts = strtotime($monday);
        for ($i = 0; $i < 6; $i++) {
            $dates[] = date('Y-m-d', strtotime("+{$i} days", $ts));
        }
        return $dates;
    }

    // Obtener aulas por tipo (ej. 'AIP')
    public function obtenerAulasPorTipo($tipo = 'AIP') {
        return $this->reservaModel->obtenerAulas($tipo);
    }

    // Obtener reservas de la semana para un aula, filtrando por profesor
    public function obtenerReservasSemanaPorAula($id_aula, $monday, $id_usuario) {
        $dates = $this->getWeekDates($monday);
        $result = [];
        foreach ($dates as $fecha) {
            $res = $this->reservaModel->obtenerReservasPorAulaYFecha($id_aula, $fecha, $id_usuario);
            $normalized = [];
            foreach ($res as $r) {
                $normalized[] = [
                    'id_reserva' => $r['id_reserva'] ?? null,
                    'hora_inicio' => substr($r['hora_inicio'],0,8),
                    'hora_fin' => substr($r['hora_fin'],0,8),
                    'profesor' => $r['nombre'] ?? ($r['profesor'] ?? '')
                ];
            }
            $result[$fecha] = $normalized;
        }
        return $result;
    }

    public function obtenerPrestamos() {
        return $this->prestamoModel->obtenerTodosPrestamos();
    }
}
