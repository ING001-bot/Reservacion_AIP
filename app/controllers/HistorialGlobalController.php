<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../models/ReservaModel.php';
require_once __DIR__ . '/../models/PrestamoModel.php';

class HistorialGlobalController {
    private $reservaModel;
    private $prestamoModel;

    public function __construct($conexion) {
        $this->reservaModel = new ReservaModel($conexion);
        $this->prestamoModel = new PrestamoModel($conexion);
    }

    // Retorna un listado unificado de movimientos (reservas activas, canceladas y préstamos)
    // Filtros opcionales: rango de fechas, profesor, tipo, estado
    public function listarHistorial(array $opts = []): array {
        $desde = $opts['desde'] ?? null;     // YYYY-MM-DD
        $hasta = $opts['hasta'] ?? null;     // YYYY-MM-DD
        $prof  = $opts['profesor'] ?? null;  // nombre parcial
        $tipo  = $opts['tipo'] ?? null;      // 'reserva' | 'prestamo'
        $estado= $opts['estado'] ?? null;    // 'Activa' | 'Cancelada' | 'Incidente' | 'Finalizada' | 'Prestado' | 'Devuelto'

        $result = [];

        // 1) Reservas activas
        if (!$tipo || strtolower($tipo) === 'reserva') {
            $reservas = $this->consultarReservasActivas($desde, $hasta, $prof);
            foreach ($reservas as $r) {
                $result[] = [
                    'id'           => $r['id_reserva'],
                    'fecha'        => $r['fecha'],
                    'hora_inicio'  => substr($r['hora_inicio'], 0, 8),
                    'hora_fin'     => substr($r['hora_fin'], 0, 8),
                    'profesor'     => $r['profesor'] ?? $r['nombre'] ?? '',
                    'tipo'         => 'Reserva',
                    'estado'       => 'Activa',
                    'aula'         => $r['nombre_aula'] ?? '',
                    'observacion'  => '',
                ];
            }

            // 2) Reservas canceladas
            $canceladas = $this->consultarReservasCanceladas($desde, $hasta, $prof);
            foreach ($canceladas as $c) {
                $result[] = [
                    'id'           => $c['id_cancelacion'],
                    'fecha'        => $c['fecha'],
                    'hora_inicio'  => substr($c['hora_inicio'] ?? '', 0, 8),
                    'hora_fin'     => substr($c['hora_fin'] ?? '', 0, 8),
                    'profesor'     => $c['profesor'] ?? $c['nombre'] ?? '',
                    'tipo'         => 'Reserva',
                    'estado'       => 'Cancelada',
                    'aula'         => $c['nombre_aula'] ?? '',
                    'observacion'  => $c['motivo'] ?? '',
                ];
            }
        }

        // 3) Préstamos
        if (!$tipo || strtolower($tipo) === 'prestamo' || strtolower($tipo) === 'préstamo') {
            $prestamos = $this->consultarPrestamos($desde, $hasta, $prof);
            foreach ($prestamos as $p) {
                $result[] = [
                    'id'           => $p['id_prestamo'],
                    'fecha'        => $p['fecha_prestamo'],
                    'hora_inicio'  => substr($p['hora_inicio'] ?? '', 0, 8),
                    'hora_fin'     => substr($p['hora_fin'] ?? '', 0, 8),
                    'profesor'     => $p['nombre'] ?? '',
                    'tipo'         => 'Préstamo',
                    'estado'       => $p['estado'] ?? '',
                    'aula'         => $p['nombre_aula'] ?? '',
                    'observacion'  => $p['comentario_devolucion'] ?? '',
                    'equipo'       => $p['nombre_equipo'] ?? '',
                ];
            }
        }

        // Filtro por estado, si se envía
        if ($estado) {
            $est = strtolower($estado);
            $result = array_values(array_filter($result, function($it) use ($est) {
                return strtolower($it['estado'] ?? '') === $est;
            }));
        }

        // Orden cronológico descendente (fecha + hora)
        usort($result, function($a, $b) {
            $ka = ($a['fecha'] ?? '') . ' ' . ($a['hora_inicio'] ?? '');
            $kb = ($b['fecha'] ?? '') . ' ' . ($b['hora_inicio'] ?? '');
            return strcmp($kb, $ka);
        });

        return $result;
    }

    private function consultarReservasActivas(?string $desde, ?string $hasta, ?string $prof): array {
        // Consulta base de reservas activas
        $sql = "SELECT r.id_reserva, r.fecha, r.hora_inicio, r.hora_fin, a.nombre_aula, u.nombre
                FROM reservas r
                LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
                LEFT JOIN aulas a ON r.id_aula = a.id_aula
                WHERE 1=1";
        $params = [];
        if ($desde) { $sql .= " AND r.fecha >= :desde"; $params[':desde'] = $desde; }
        if ($hasta) { $sql .= " AND r.fecha <= :hasta"; $params[':hasta'] = $hasta; }
        if ($prof)  { $sql .= " AND u.nombre LIKE :prof"; $params[':prof'] = "%$prof%"; }
        $sql .= " ORDER BY r.fecha DESC, r.hora_inicio DESC";

        $stmt = $this->reservaModel->getDb()->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function consultarReservasCanceladas(?string $desde, ?string $hasta, ?string $prof): array {
        $sql = "SELECT rc.id_cancelacion, rc.fecha, rc.hora_inicio, rc.hora_fin, rc.motivo, a.nombre_aula, u.nombre
                FROM reservas_canceladas rc
                LEFT JOIN usuarios u ON rc.id_usuario = u.id_usuario
                LEFT JOIN aulas a ON rc.id_aula = a.id_aula
                WHERE 1=1";
        $params = [];
        if ($desde) { $sql .= " AND rc.fecha >= :desde"; $params[':desde'] = $desde; }
        if ($hasta) { $sql .= " AND rc.fecha <= :hasta"; $params[':hasta'] = $hasta; }
        if ($prof)  { $sql .= " AND u.nombre LIKE :prof"; $params[':prof'] = "%$prof%"; }
        $sql .= " ORDER BY rc.fecha DESC, rc.hora_inicio DESC";

        $stmt = $this->reservaModel->getDb()->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function consultarPrestamos(?string $desde, ?string $hasta, ?string $prof): array {
        $sql = "SELECT p.id_prestamo, p.fecha_prestamo, p.hora_inicio, p.hora_fin, p.estado,
                       p.comentario_devolucion,
                       u.nombre, a.nombre_aula, e.nombre_equipo
                FROM prestamos p
                JOIN usuarios u ON p.id_usuario = u.id_usuario
                JOIN aulas a ON p.id_aula = a.id_aula
                JOIN equipos e ON p.id_equipo = e.id_equipo
                WHERE 1=1";
        $params = [];
        if ($desde) { $sql .= " AND p.fecha_prestamo >= :desde"; $params[':desde'] = $desde; }
        if ($hasta) { $sql .= " AND p.fecha_prestamo <= :hasta"; $params[':hasta'] = $hasta; }
        if ($prof)  { $sql .= " AND u.nombre LIKE :prof"; $params[':prof'] = "%$prof%"; }
        $sql .= " ORDER BY p.fecha_prestamo DESC, p.hora_inicio DESC";

        $stmt = $this->prestamoModel->getDb()->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
