<?php
// app/models/HistorialModel.php
require '../config/conexion.php'; // ajusta ruta si tu config está en otra ubicación

class HistorialModel {
    private $db;

    public function __construct($conexion) {
        // aquí asumimos que $conexion es un PDO pasado desde el controller
        $this->db = $conexion;
    }

    // Obtener las 2 aulas tipo AIP (ordenadas)
    public function obtenerAulasAIP() {
        $stmt = $this->db->prepare("SELECT * FROM aulas WHERE tipo = 'AIP' ORDER BY nombre_aula ASC LIMIT 2");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener reservas de un aula entre dos fechas inclusive
    // Devuelve filas: fecha, hora_inicio, hora_fin, profesor(nombre)
    public function obtenerReservasPorSemana($id_aula, $fecha_inicio, $fecha_fin) {
        $sql = "
            SELECT r.fecha, r.hora_inicio, r.hora_fin, u.nombre AS profesor
            FROM reservas r
            LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
            WHERE r.id_aula = :id_aula
              AND r.fecha BETWEEN :fi AND :ff
            ORDER BY r.fecha, r.hora_inicio
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id_aula', $id_aula);
        $stmt->bindParam(':fi', $fecha_inicio);
        $stmt->bindParam(':ff', $fecha_fin);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener préstamos del profesor (adaptado a tu esquema: prestamos, equipos, aulas)
    // Devuelve columnas: tipo_equipo, nombre_equipo, nombre_aula, fecha_prestamo, hora_inicio, hora_fin, fecha_devolucion
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
            LEFT JOIN equipos e ON p.id_equipo = e.id_equipo
            LEFT JOIN aulas a  ON p.id_aula   = a.id_aula
            WHERE p.id_usuario = :id_usuario
            ORDER BY p.fecha_prestamo DESC, p.hora_inicio ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
