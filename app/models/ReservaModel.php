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
            $stmt = $this->db->prepare("SELECT * FROM aulas WHERE activo = 1 AND tipo = ? ORDER BY nombre_aula ASC");
            $stmt->execute([$tipo]);
        } else {
            $stmt = $this->db->query("SELECT * FROM aulas WHERE activo = 1 ORDER BY nombre_aula ASC");
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

    // Obtener datos de un aula por ID (para notificaciones)
    public function obtenerAulaPorId($id_aula) {
        $stmt = $this->db->prepare("SELECT id_aula, nombre_aula, tipo FROM aulas WHERE id_aula = ?");
        $stmt->execute([$id_aula]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Cancelar con motivo: registra en reservas_canceladas y elimina la reserva
    public function cancelarReserva($id_reserva, $id_usuario, $motivo) {
        try {
            $this->db->beginTransaction();
            // Insertar cancelación
            $stmt = $this->db->prepare("INSERT INTO reservas_canceladas (id_reserva, id_usuario, motivo) VALUES (?, ?, ?)");
            $stmt->execute([$id_reserva, $id_usuario, $motivo]);
            // Eliminar reserva original
            $stmt2 = $this->db->prepare("DELETE FROM reservas WHERE id_reserva = ? AND id_usuario = ?");
            $stmt2->execute([$id_reserva, $id_usuario]);
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Listar cancelaciones del usuario
    public function obtenerCanceladasPorUsuario($id_usuario) {
        $stmt = $this->db->prepare("
            SELECT rc.id_cancelacion, rc.motivo, rc.fecha_cancelacion,
                   r.fecha, r.hora_inicio, r.hora_fin, a.nombre_aula, a.tipo
            FROM reservas_canceladas rc
            LEFT JOIN reservas r ON rc.id_reserva = r.id_reserva
            LEFT JOIN aulas a ON r.id_aula = a.id_aula
            WHERE rc.id_usuario = ?
            ORDER BY rc.fecha_cancelacion DESC
        ");
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
