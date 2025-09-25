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

    // Nuevo: obtener reservas para TODOS los profesores en la semana
    public function obtenerReservasSemanaPorAulaTodos($id_aula, $monday, ?string $profesorLike = null) {
        $dates = $this->getWeekDates($monday);
        $result = [];
        foreach ($dates as $fecha) {
            // Consulta directa usando el modelo (no hay método dedicado)
            $db = $this->reservaModel->getDb();
            $sql = "SELECT r.id_reserva, u.nombre AS profesor, r.hora_inicio, r.hora_fin
                    FROM reservas r
                    LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
                    WHERE r.id_aula = :aula AND r.fecha = :fecha";
            $params = [':aula' => $id_aula, ':fecha' => $fecha];
            if ($profesorLike) { $sql .= " AND u.nombre LIKE :prof"; $params[':prof'] = "%$profesorLike%"; }
            $sql .= " ORDER BY r.hora_inicio ASC";
            $stmt = $db->prepare($sql);
            foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $normalized = [];
            foreach ($rows as $r) {
                $normalized[] = [
                    'id_reserva' => $r['id_reserva'] ?? null,
                    'hora_inicio' => substr($r['hora_inicio'],0,8),
                    'hora_fin' => substr($r['hora_fin'],0,8),
                    'profesor' => $r['profesor'] ?? ''
                ];
            }
            $result[$fecha] = $normalized;
        }
        return $result;
    }

    // Nuevo: cancelaciones de TODOS los profesores en la semana
    public function obtenerCanceladasSemanaTodos($monday, $id_aula = null, ?string $profesorLike = null) {
        $dates = $this->getWeekDates($monday);
        $desde = $dates[0];
        $hasta = end($dates);
        $db = $this->reservaModel->getDb();
        $sql = "SELECT rc.id_cancelacion, rc.id_aula, rc.fecha, rc.hora_inicio, rc.hora_fin, rc.motivo, u.nombre AS profesor
                FROM reservas_canceladas rc
                LEFT JOIN usuarios u ON rc.id_usuario = u.id_usuario
                WHERE rc.fecha BETWEEN :desde AND :hasta";
        $params = [':desde' => $desde, ':hasta' => $hasta];
        if ($id_aula) { $sql .= " AND rc.id_aula = :aula"; $params[':aula'] = $id_aula; }
        if ($profesorLike) { $sql .= " AND u.nombre LIKE :prof"; $params[':prof'] = "%$profesorLike%"; }
        $sql .= " ORDER BY rc.fecha ASC, rc.hora_inicio ASC";
        $stmt = $db->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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

    // Obtener cancelaciones del usuario para la semana que inicia en $monday (YYYY-MM-DD)
    public function obtenerCanceladasSemana($monday, $id_usuario) {
        $dates = $this->getWeekDates($monday);
        $desde = $dates[0];
        $hasta = end($dates);
        return $this->reservaModel->obtenerCanceladasPorUsuarioYRango($id_usuario, $desde, $hasta);
    }
}
