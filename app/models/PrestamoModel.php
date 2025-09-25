<?php
require "../config/conexion.php";
class PrestamoModel {
    private $db;
    public function __construct($conexion) { $this->db = $conexion; }

    // Permite a controladores ejecutar consultas personalizadas cuando el modelo no tiene un método específico
    public function getDb(): PDO {
        return $this->db;
    }

    public function guardarPrestamosMultiple($id_usuario, $equipos, $fecha_prestamo, $hora_inicio, $hora_fin = null, $id_aula) {
        if (!$id_usuario || empty($equipos) || !$id_aula) {
            return ['mensaje'=>'⚠ No se proporcionó usuario, equipos o aula.','tipo'=>'error'];
        }
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO prestamos (id_usuario, id_equipo, id_aula, fecha_prestamo, estado, hora_inicio, hora_fin)
                VALUES (?, ?, ?, ?, 'Prestado', ?, ?)
            ");
            foreach ($equipos as $val) {
                if ($val) $stmt->execute([$id_usuario, $val, $id_aula, $fecha_prestamo, $hora_inicio, $hora_fin]);
            }
            $this->db->commit();
            return ['mensaje'=>'✅ Préstamos registrados correctamente.','tipo'=>'success'];
        } catch (PDOException $e) {
            return ['mensaje'=>'❌ Error al registrar préstamos: '.$e->getMessage(),'tipo'=>'error'];
        }
    }

    public function listarEquiposPorTipo($tipo) {
        $stmt = $this->db->prepare("\n            SELECT id_equipo, nombre_equipo\n            FROM equipos\n            WHERE tipo_equipo = ?\n            AND id_equipo NOT IN (\n                SELECT id_equipo FROM prestamos WHERE estado = 'Prestado' AND fecha_prestamo = CURDATE()\n            )\n        ");
        $stmt->execute([$tipo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodosPrestamos() {
        $stmt = $this->db->prepare("\n            SELECT p.id_prestamo, e.nombre_equipo, e.tipo_equipo, u.nombre, \n                   a.nombre_aula, a.tipo,\n                   p.fecha_prestamo, p.hora_inicio, p.hora_fin, \n                   p.fecha_devolucion, p.estado\n            FROM prestamos p\n            JOIN equipos e ON p.id_equipo = e.id_equipo\n            JOIN usuarios u ON p.id_usuario = u.id_usuario\n            JOIN aulas a ON p.id_aula = a.id_aula\n            ORDER BY p.fecha_prestamo DESC\n        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPrestamosPorUsuario($id_usuario) {
        if (!$id_usuario) return [];
        $stmt = $this->db->prepare("\n            SELECT p.*, e.tipo_equipo, e.nombre_equipo, a.nombre_aula\n            FROM prestamos p\n            JOIN equipos e ON p.id_equipo = e.id_equipo\n            JOIN aulas a ON p.id_aula = a.id_aula\n            WHERE p.id_usuario = ?\n            ORDER BY p.fecha_prestamo DESC\n        ");
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPrestamosFiltrados(?string $estado, ?string $desde, ?string $hasta, ?string $q): array {
        $sql = "\n            SELECT p.id_prestamo, e.nombre_equipo, e.tipo_equipo, u.nombre, \n                   a.nombre_aula, a.tipo,\n                   p.fecha_prestamo, p.hora_inicio, p.hora_fin, \n                   p.fecha_devolucion, p.estado\n            FROM prestamos p\n            JOIN equipos e ON p.id_equipo = e.id_equipo\n            JOIN usuarios u ON p.id_usuario = u.id_usuario\n            JOIN aulas a ON p.id_aula = a.id_aula\n            WHERE 1=1\n        ";
        $params = [];
        if ($estado) { $sql .= " AND p.estado = :estado"; $params[':estado'] = $estado; }
        if ($desde) { $sql .= " AND p.fecha_prestamo >= :desde"; $params[':desde'] = $desde; }
        if ($hasta) { $sql .= " AND p.fecha_prestamo <= :hasta"; $params[':hasta'] = $hasta; }
        if ($q) {
            $sql .= " AND (u.nombre LIKE :q OR e.nombre_equipo LIKE :q OR a.nombre_aula LIKE :q)";
            $params[':q'] = "%$q%";
        }
        $sql .= " ORDER BY p.fecha_prestamo DESC, p.hora_inicio DESC";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function devolverEquipo($id_prestamo, ?string $comentario = null) {
        $stmt = $this->db->prepare("\n            UPDATE prestamos \n            SET estado='Devuelto', fecha_devolucion=CURDATE(), comentario_devolucion = :comentario \n            WHERE id_prestamo=:id\n        ");
        $stmt->bindValue(':comentario', $comentario, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id_prestamo, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Helpers para notificaciones por correo
    public function obtenerEquiposPorIds(array $ids): array {
        if (empty($ids)) return [];
        // construir placeholders dinámicos
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT id_equipo, nombre_equipo, tipo_equipo FROM equipos WHERE id_equipo IN ($placeholders)");
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerAulaPorId($id_aula) {
        $stmt = $this->db->prepare("SELECT id_aula, nombre_aula, tipo FROM aulas WHERE id_aula = ?");
        $stmt->execute([$id_aula]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
