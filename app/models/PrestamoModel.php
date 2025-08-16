<?php
require '../config/conexion.php';

class PrestamoModel {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    public function obtenerUsuarioPorNombre($nombre) {
        $stmt = $this->db->prepare("SELECT id_usuario FROM usuarios WHERE nombre = ?");
        $stmt->execute([$nombre]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insertarPrestamo($id_usuario, $id_equipo, $fecha) {
        $stmt = $this->db->prepare(
            "INSERT INTO prestamos (id_usuario, id_equipo, fecha_prestamo, estado) VALUES (?, ?, ?, 'Prestado')"
        );
        return $stmt->execute([$id_usuario, $id_equipo, $fecha]);
    }

    public function actualizarEstadoEquipo($id_equipo, $estado = 'Prestado') {
        $stmt = $this->db->prepare("UPDATE equipos SET estado = ? WHERE id_equipo = ?");
        return $stmt->execute([$estado, $id_equipo]);
    }

    public function obtenerEquiposDisponibles() {
        $stmt = $this->db->prepare("SELECT id_equipo, nombre_equipo, tipo_equipo FROM equipos WHERE estado = 'Disponible'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
