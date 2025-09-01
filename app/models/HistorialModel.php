<?php
// app/models/HistorialModel.php
class HistorialModel {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    // Aulas tipo AIP (usado por la vista)
    public function obtenerAulasAIP() {
        $sql = "SELECT id_aula, nombre_aula FROM aulas WHERE tipo = 'AIP' ORDER BY id_aula ASC LIMIT 2";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Reservas por semana, FILTRADAS por profesor (id_usuario)
    public function obtenerReservasPorSemana($id_aula, $fecha_inicio, $fecha_fin, $id_usuario) {
        $sql = "
            SELECT 
                r.fecha,
                r.hora_inicio,
                r.hora_fin,
                u.nombre AS profesor
            FROM reservas r
            INNER JOIN usuarios u ON u.id_usuario = r.id_usuario
            WHERE r.id_aula = :id_aula
              AND r.fecha BETWEEN :fi AND :ff
              AND r.id_usuario = :id_usuario
            ORDER BY r.fecha ASC, r.hora_inicio ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_aula' => $id_aula,
            ':fi' => $fecha_inicio,
            ':ff' => $fecha_fin,
            ':id_usuario' => $id_usuario
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Prestamos por profesor (ya lo usas en la tabla inferior)
    public function obtenerPrestamosPorProfesor($id_usuario) {
        $sql = "
            SELECT 
                e.tipo_equipo,
                e.nombre_equipo,
                a.nombre_aula,
                p.fecha_prestamo,
                p.hora_inicio,
                p.hora_fin,
                p.fecha_devolucion
            FROM prestamos p
            LEFT JOIN equipos e ON e.id_equipo = p.id_equipo
            LEFT JOIN aulas a   ON a.id_aula   = p.id_aula
            WHERE p.id_usuario = :id_usuario
            ORDER BY p.fecha_prestamo DESC, p.hora_inicio ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
