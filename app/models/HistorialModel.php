<?php
// app/models/HistorialModel.php
if (session_status() === PHP_SESSION_NONE) session_start();

class HistorialModel {
    private $db;

    // Acepta PDO $conexion como dependencia. Si no se pasa, intenta $GLOBALS['conexion'].
    public function __construct(PDO $conexion = null) {
        if ($conexion instanceof PDO) {
            $this->db = $conexion;
            return;
        }
        if (isset($GLOBALS['conexion']) && $GLOBALS['conexion'] instanceof PDO) {
            $this->db = $GLOBALS['conexion'];
            return;
        }
        throw new RuntimeException("No se encontró la conexión a la BD. Pasa PDO al constructor o revisa config/conexion.php");
    }

    // Obtener todas las aulas (posibilidad de filtrar por tipo: 'AIP' o 'REGULAR')
    public function obtenerAulas($tipo = null) {
        if ($tipo) {
            $stmt = $this->db->prepare("SELECT * FROM aulas WHERE tipo = ? ORDER BY nombre_aula");
            $stmt->execute([$tipo]);
        } else {
            $stmt = $this->db->query("SELECT * FROM aulas ORDER BY nombre_aula");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener reservas para una aula entre dos fechas (inclusive), opcionalmente filtradas por usuario
    public function obtenerReservasSemana($id_aula, $fecha_inicio, $fecha_fin, $id_usuario = null) {
        $sql = "SELECT r.id_reserva, r.id_aula, r.id_usuario, r.fecha, r.hora_inicio, r.hora_fin,
                       a.nombre_aula, u.nombre AS profesor
                FROM reservas r
                LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
                LEFT JOIN aulas a ON r.id_aula = a.id_aula
                WHERE r.id_aula = ?
                  AND r.fecha BETWEEN ? AND ?";
        $params = [$id_aula, $fecha_inicio, $fecha_fin];

        if (!empty($id_usuario)) {
            $sql .= " AND r.id_usuario = ?";
            $params[] = $id_usuario;
        }

        $sql .= " ORDER BY r.fecha, r.hora_inicio";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener préstamos por profesor (historial)
    public function obtenerPrestamosPorProfesor($id_usuario) {
        $sql = "SELECT p.id_prestamo, p.fecha_prestamo, p.hora_inicio, p.hora_fin, p.fecha_devolucion,
                       e.tipo_equipo, e.nombre_equipo, a.nombre_aula
                FROM prestamos p
                JOIN equipos e ON p.id_equipo = e.id_equipo
                JOIN aulas a ON p.id_aula = a.id_aula
                WHERE p.id_usuario = ?
                ORDER BY p.fecha_prestamo DESC, p.hora_inicio ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
