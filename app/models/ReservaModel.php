<?php
require "../config/conexion.php";
class ReservaModel {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    // Crear nueva reserva
    public function crearReserva($id_aula, $id_usuario, $fecha, $hora_inicio, $hora_fin) {
        $stmt = $this->db->prepare("
            INSERT INTO reservas (id_usuario, id_aula, fecha, hora_inicio, hora_fin)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$id_usuario, $id_aula, $fecha, $hora_inicio, $hora_fin]);
    }

    // Obtener todas las aulas disponibles
    // Filtrar por tipo si se indica
    public function obtenerAulas($tipo = null) {
        if ($tipo) {
            $stmt = $this->db->prepare("SELECT * FROM aulas WHERE tipo = ?");
            $stmt->execute([$tipo]);
        } else {
            $stmt = $this->db->query("SELECT * FROM aulas");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Listar reservas de un profesor específico
    public function obtenerReservasPorProfesor($id_usuario) {
        $stmt = $this->db->prepare("
            SELECT r.id_reserva, u.nombre AS profesor, a.nombre_aula, a.capacidad,
                   r.fecha, r.hora_inicio, r.hora_fin
            FROM reservas r
            INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
            INNER JOIN aulas a ON r.id_aula = a.id_aula
            WHERE r.id_usuario = ?
            ORDER BY r.fecha DESC, r.hora_inicio ASC
        ");
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Verificar disponibilidad del aula
    public function verificarDisponibilidad($id_aula, $fecha, $hora_inicio, $hora_fin) {
        $hora_min = "07:00:00";
        $hora_max = "18:35:00";

        if ($hora_inicio < $hora_min || $hora_fin > $hora_max) {
            return false;
        }

        $query = "SELECT * FROM reservas 
                  WHERE id_aula = :id_aula 
                  AND fecha = :fecha
                  AND (hora_inicio < :hora_fin AND hora_fin > :hora_inicio)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_aula", $id_aula);
        $stmt->bindParam(":fecha", $fecha);
        $stmt->bindParam(":hora_inicio", $hora_inicio);
        $stmt->bindParam(":hora_fin", $hora_fin);
        $stmt->execute();

        return $stmt->rowCount() === 0;
    }
}
