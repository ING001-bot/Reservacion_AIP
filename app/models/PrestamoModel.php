<?php
require "../config/conexion.php";

class PrestamoModel {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    public function guardarPrestamosMultiple($id_usuario, $equipos, $hora_inicio, $hora_fin = null) {
        if (!$id_usuario || empty($equipos)) {
            return ['mensaje'=>'⚠ No se proporcionó usuario o equipos.','tipo'=>'error'];
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO prestamos (id_usuario, id_equipo, fecha_prestamo, estado, hora_inicio, hora_fin)
                VALUES (?, ?, CURDATE(), 'Prestado', ?, ?)
            ");

            foreach ($equipos as $id_equipo) {
                if ($id_equipo) $stmt->execute([$id_usuario, $id_equipo, $hora_inicio, $hora_fin]);
            }

            $this->db->commit();
            return ['mensaje'=>'✅ Préstamos registrados correctamente.','tipo'=>'success'];
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['mensaje'=>'❌ Error al registrar préstamos: '.$e->getMessage(),'tipo'=>'error'];
        }
    }

    public function listarEquiposPorTipo($tipo) {
        $stmt = $this->db->prepare("
            SELECT id_equipo, nombre_equipo
            FROM equipos
            WHERE tipo_equipo = ?
            AND id_equipo NOT IN (
                SELECT id_equipo FROM prestamos WHERE estado = 'Prestado' AND fecha_prestamo = CURDATE()
            )
        ");
        $stmt->execute([$tipo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodosPrestamos() {
        $stmt = $this->db->prepare("
            SELECT p.id_prestamo, e.nombre_equipo, u.nombre, p.fecha_prestamo, p.hora_inicio, p.hora_fin, p.estado
            FROM prestamos p
            JOIN equipos e ON p.id_equipo = e.id_equipo
            JOIN usuarios u ON p.id_usuario = u.id_usuario
            ORDER BY p.fecha_prestamo DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function devolverEquipo($id_prestamo) {
        $stmt = $this->db->prepare("
            UPDATE prestamos SET estado='Devuelto', fecha_devolucion=NOW() WHERE id_prestamo=?
        ");
        return $stmt->execute([$id_prestamo]);
    }
}
