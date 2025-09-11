<?php
require "../config/conexion.php";

class ReservaModel {
    private $db;
    public function __construct($conexion) { $this->db = $conexion; }

    public function crearReserva($id_aula, $id_usuario, $fecha, $hora_inicio, $hora_fin) {
        $stmt = $this->db->prepare("
            INSERT INTO reservas (id_aula, id_usuario, fecha, hora_inicio, hora_fin)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$id_aula, $id_usuario, $fecha, $hora_inicio, $hora_fin]);
    }

    public function obtenerAulas($tipo = null) {
        if ($tipo) {
            $stmt = $this->db->prepare("SELECT * FROM aulas WHERE tipo = ?");
            $stmt->execute([$tipo]);
        } else {
            $stmt = $this->db->query("SELECT * FROM aulas");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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

    public function verificarDisponibilidad($id_aula, $fecha, $hora_inicio, $hora_fin) {
        $hora_min = "06:00:00";
        $hora_max = "19:00:00";

        if (strlen($hora_inicio) === 5) $hora_inicio .= ":00";
        if (strlen($hora_fin) === 5) $hora_fin .= ":00";

        if ($hora_inicio < $hora_min || $hora_fin > $hora_max || $hora_inicio >= $hora_fin) {
            return false;
        }

        $query = "SELECT COUNT(*) FROM reservas 
                  WHERE id_aula = :id_aula 
                    AND fecha = :fecha
                    AND (hora_inicio < :hora_fin AND hora_fin > :hora_inicio)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':id_aula' => $id_aula,
            ':fecha' => $fecha,
            ':hora_inicio' => $hora_inicio,
            ':hora_fin' => $hora_fin
        ]);

        return $stmt->fetchColumn() == 0;
    }

    // ✅ Modificada: ahora filtra por id_usuario si se pasa (para historial/pdf)
    public function obtenerReservasPorAulaYFecha($id_aula, $fecha, $id_usuario = null) {
        $sql = "SELECT r.id_reserva, u.nombre, r.hora_inicio, r.hora_fin 
                FROM reservas r
                LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
                WHERE r.id_aula = :id_aula AND r.fecha = :fecha";

        if ($id_usuario) {
            $sql .= " AND r.id_usuario = :id_usuario";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id_aula', $id_aula, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha);

        if ($id_usuario) {
            $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarReserva($id_reserva, $id_usuario) {
        $stmt = $this->db->prepare("
            DELETE FROM reservas
            WHERE id_reserva = ? AND id_usuario = ?
        ");
        return $stmt->execute([$id_reserva, $id_usuario]);
    }
}
