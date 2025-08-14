<?php
class PrestamoModel {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    // Obtener el usuario por nombre
    public function obtenerUsuarioPorNombre($nombre) {
        $stmt = $this->db->prepare("SELECT id_usuario FROM usuarios WHERE nombre = ?");
        $stmt->execute([$nombre]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Insertar un préstamo
    public function insertarPrestamo($id_usuario, $id_equipo, $fecha) {
        $stmt = $this->db->prepare(
            "INSERT INTO prestamos (id_usuario, id_equipo, fecha_prestamo, estado) VALUES (?, ?, ?, 'Prestado')"
        );
        return $stmt->execute([$id_usuario, $id_equipo, $fecha]);
    }

    // Actualizar el estado del equipo (por ejemplo, cambiar a 'Prestado')
    public function actualizarEstadoEquipo($id_equipo, $estado = 'Prestado') {
        $stmt = $this->db->prepare("UPDATE equipos SET estado = ? WHERE id_equipo = ?");
        return $stmt->execute([$estado, $id_equipo]);
    }

    // Obtener equipos disponibles
    public function obtenerEquiposDisponibles() {
        $stmt = $this->db->prepare("SELECT id_equipo, nombre_equipo, tipo_equipo FROM equipos WHERE estado = 'Disponible'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Devolver un equipo
    public function devolverEquipo($id_prestamo) {
        $hoy = date('Y-m-d');

        // Actualiza el préstamo
        $update = $this->db->prepare("
            UPDATE prestamos 
            SET fecha_devolucion = ?, estado = 'Devuelto' 
            WHERE id_prestamo = ?
        ");
        $exito = $update->execute([$hoy, $id_prestamo]);

        if ($exito) {
            // Obtener id del equipo
            $stmt = $this->db->prepare("SELECT id_equipo FROM prestamos WHERE id_prestamo = ?");
            $stmt->execute([$id_prestamo]);
            $equipo = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($equipo) {
                // Cambiar estado del equipo a 'Disponible'
                $updateEquipo = $this->db->prepare("
                    UPDATE equipos SET estado = 'Disponible' WHERE id_equipo = ?
                ");
                return $updateEquipo->execute([$equipo['id_equipo']]);
            }
        }

        return false;
    }

    // Método para obtener los préstamos activos
    public function obtenerPrestamosActivos() {
        $stmt = $this->db->prepare("
            SELECT p.id_prestamo, e.nombre_equipo, u.nombre, p.fecha_prestamo 
            FROM prestamos p
            JOIN equipos e ON p.id_equipo = e.id_equipo
            JOIN usuarios u ON p.id_usuario = u.id_usuario
            WHERE p.estado = 'Prestado'
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
