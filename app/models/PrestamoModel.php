<?php
if (!class_exists('PrestamoModel')) {
class PrestamoModel {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }
    
    /** Permite a controladores ejecutar consultas personalizadas cuando el modelo no tiene un método específico */
    public function getDb(): PDO {
        return $this->db;
    }
    

    public function guardarPrestamosMultiple($id_usuario, $equipos, $fecha_prestamo, $hora_inicio, $id_aula, $hora_fin = null) {
        if (!$id_usuario || empty($equipos) || !$id_aula) {
            return ['mensaje'=>'⚠ No se proporcionó usuario, equipos o aula.','tipo'=>'error'];
        }
        
        // Validar fecha mínima: debe ser al menos 1 día después
        date_default_timezone_set('America/Lima');
        $hoy = new DateTime('today', new DateTimeZone('America/Lima'));
        $minima = (clone $hoy)->modify('+1 day');
        $fechaPrestamo = DateTime::createFromFormat('Y-m-d', $fecha_prestamo);
        
        if (!$fechaPrestamo || $fechaPrestamo < $minima) {
            return ['mensaje'=>'⚠️ Solo puedes solicitar préstamos a partir del día siguiente. Los préstamos deben hacerse con anticipación, no el mismo día.','tipo'=>'error'];
        }
        
        // Validar que el aula existe
        $checkAula = $this->db->prepare("SELECT id_aula FROM aulas WHERE id_aula = ? AND activo = 1");
        $checkAula->execute([$id_aula]);
        if (!$checkAula->fetch()) {
            return ['mensaje'=>' El aula seleccionada no existe o está inactiva. Por favor, selecciona otra aula.','tipo'=>'error'];
        }
        
        $this->db->beginTransaction();
        try {
            // Preparar consultas
            $stmtInsert = $this->db->prepare("\n                INSERT INTO prestamos (id_usuario, id_equipo, id_aula, fecha_prestamo, estado, hora_inicio, hora_fin)\n                VALUES (?, ?, ?, ?, 'Prestado', ?, ?)\n            ");
            $stmtSelStock = $this->db->prepare("SELECT stock FROM equipos WHERE id_equipo = ? AND activo = 1 FOR UPDATE");
            $stmtDecStock = $this->db->prepare("UPDATE equipos SET stock = stock - 1 WHERE id_equipo = ? AND stock > 0");

            foreach ($equipos as $val) {
                if (!$val) { continue; }
                // Validar stock disponible del equipo seleccionado
                $stmtSelStock->execute([$val]);
                $row = $stmtSelStock->fetch(\PDO::FETCH_ASSOC);
                $stk = isset($row['stock']) ? (int)$row['stock'] : 0;
                if ($stk <= 0) {
                    $this->db->rollBack();
                    return ['mensaje' => ' El equipo seleccionado (ID '.(int)$val.') no tiene stock disponible.', 'tipo' => 'error'];
                }
                // Registrar préstamo individual
                $stmtInsert->execute([$id_usuario, $val, $id_aula, $fecha_prestamo, $hora_inicio, $hora_fin ?? null]);
                // Descontar stock
                $stmtDecStock->execute([$val]);
            }
            $this->db->commit();
            return ['mensaje'=>' Préstamos registrados correctamente.','tipo'=>'success'];
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return ['mensaje'=>' Error al registrar préstamos: '.$e->getMessage(),'tipo'=>'error'];
        }
    }

    public function listarTodosEquipos() {
        $stmt = $this->db->prepare("SELECT id_equipo, nombre_equipo, tipo_equipo, activo, stock FROM equipos");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarEquiposPorTipo($tipo) {
        $tipo = strtoupper(trim($tipo)); // Normalizar a mayúsculas
        $stmt = $this->db->prepare("\n            SELECT id_equipo, nombre_equipo, tipo_equipo\n            FROM equipos\n            WHERE \n              REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(UPPER(TRIM(tipo_equipo)),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U') =\n              REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(UPPER(TRIM(?)),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U')\n              AND activo = 1\n              AND id_equipo NOT IN (\n                  SELECT id_equipo FROM prestamos WHERE estado = 'Prestado' AND fecha_prestamo = CURDATE()\n              )\n        ");
        $stmt->execute([$tipo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function listarEquiposPorTipoConStock($tipo, $fecha) {
        // Nota: el stock en la tabla `equipos` se maneja como stock disponible actual.
        // Al prestar se decrementa y al devolver se incrementa. No debemos volver a restar
        // los préstamos del día aquí para evitar doble descuento.
        $tipo = strtoupper(trim($tipo));
        $stmt = $this->db->prepare("\n            SELECT \n                e.id_equipo,\n                e.nombre_equipo,\n                e.tipo_equipo,\n                e.stock AS disponible\n            FROM equipos e\n            WHERE \n              REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(UPPER(TRIM(e.tipo_equipo)),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U') =\n              REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(UPPER(TRIM(?)),'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U')\n              AND e.activo = 1\n              AND e.stock > 0\n            ORDER BY e.nombre_equipo ASC\n        ");
        $stmt->execute([$tipo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodosPrestamos() {
        $stmt = $this->db->prepare("\n            SELECT p.id_prestamo, p.id_usuario, e.nombre_equipo, e.tipo_equipo, u.nombre, \n                   a.nombre_aula, a.tipo,\n                   p.fecha_prestamo, p.hora_inicio, p.hora_fin, \n                   p.fecha_devolucion, p.estado\n            FROM prestamos p\n            LEFT JOIN equipos e ON p.id_equipo = e.id_equipo\n            JOIN usuarios u ON p.id_usuario = u.id_usuario\n            JOIN aulas a ON p.id_aula = a.id_aula\n            ORDER BY p.fecha_prestamo DESC\n        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPrestamosPorUsuario($id_usuario) {
        if (!$id_usuario) return [];
        $stmt = $this->db->prepare("\n            SELECT p.*, e.tipo_equipo, e.nombre_equipo, a.nombre_aula\n            FROM prestamos p\n            LEFT JOIN equipos e ON p.id_equipo = e.id_equipo\n            JOIN aulas a ON p.id_aula = a.id_aula\n            WHERE p.id_usuario = ?\n            ORDER BY p.fecha_prestamo DESC\n        ");
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPrestamosFiltrados(?string $estado, ?string $desde, ?string $hasta, ?string $q): array {
        $sql = "\n            SELECT p.id_prestamo, e.nombre_equipo, e.tipo_equipo, u.nombre, \n                   a.nombre_aula, a.tipo,\n                   p.fecha_prestamo, p.hora_inicio, p.hora_fin, \n                   p.fecha_devolucion, p.estado\n            FROM prestamos p\n            LEFT JOIN equipos e ON p.id_equipo = e.id_equipo\n            JOIN usuarios u ON p.id_usuario = u.id_usuario\n            JOIN aulas a ON p.id_aula = a.id_aula\n            WHERE 1=1\n        ";
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
        // Obtener el id_equipo del préstamo
        $stmtGet = $this->db->prepare("SELECT id_equipo FROM prestamos WHERE id_prestamo = :id");
        $stmtGet->bindValue(':id', $id_prestamo, PDO::PARAM_INT);
        $stmtGet->execute();
        $row = $stmtGet->fetch(PDO::FETCH_ASSOC);
        $id_equipo = $row ? (int)$row['id_equipo'] : null;

        $this->db->beginTransaction();
        try {
            // Marcar devolución
            $stmt = $this->db->prepare("\n                UPDATE prestamos \n                SET estado='Devuelto', fecha_devolucion=CURDATE(), comentario_devolucion = :comentario \n                WHERE id_prestamo=:id\n            ");
            $stmt->bindValue(':comentario', $comentario, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id_prestamo, PDO::PARAM_INT);
            $stmt->execute();

            // Incrementar stock si aplica
            if ($id_equipo) {
                $stmtInc = $this->db->prepare("UPDATE equipos SET stock = stock + 1 WHERE id_equipo = ?");
                $stmtInc->execute([$id_equipo]);
            }

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // ================= Notifications helpers =================
    public function crearNotificacion(int $id_usuario, string $titulo, string $mensaje, ?string $url = null): bool {
        $stmt = $this->db->prepare("INSERT INTO notificaciones (id_usuario, titulo, mensaje, url) VALUES (?,?,?,?)");
        return $stmt->execute([$id_usuario, $titulo, $mensaje, $url]);
    }

    public function listarUsuariosPorRol(array $roles): array {
        if (empty($roles)) return [];
        $place = implode(',', array_fill(0, count($roles), '?'));
        $stmt = $this->db->prepare("SELECT id_usuario, nombre, correo, tipo_usuario FROM usuarios WHERE tipo_usuario IN ($place) AND activo = 1");
        $stmt->execute($roles);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPrestamoPorId(int $id_prestamo): ?array {
        $stmt = $this->db->prepare("
            SELECT p.*, e.nombre_equipo, e.tipo_equipo, u.nombre, u.id_usuario
            FROM prestamos p
            LEFT JOIN equipos e ON p.id_equipo = e.id_equipo
            LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
            WHERE p.id_prestamo = ?
        ");
        $stmt->execute([$id_prestamo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function obtenerUsuarioPorPrestamo(int $id_prestamo): ?int {
        $stmt = $this->db->prepare("SELECT id_usuario FROM prestamos WHERE id_prestamo = ?");
        $stmt->execute([$id_prestamo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id_usuario'] : null;
    }

    public function obtenerUsuarioPorPack(int $id_pack): ?int {
        $stmt = $this->db->prepare("SELECT id_usuario FROM prestamos_pack WHERE id_pack = ?");
        $stmt->execute([$id_pack]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id_usuario'] : null;
    }

    public function obtenerUsuarioPorId(int $id_usuario): ?array {
        $stmt = $this->db->prepare("SELECT id_usuario, nombre, correo FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Listar notificaciones de un usuario
    public function listarNotificacionesUsuario(int $id_usuario, bool $soloNoLeidas = false, int $limit = 50): array {
        if (!$id_usuario) return [];
        $sql = "SELECT id_notificacion, titulo, mensaje, url, leida, creada_en
                FROM notificaciones
                WHERE id_usuario = :u" . ($soloNoLeidas ? " AND leida = 0" : "") . "
                ORDER BY creada_en DESC
                LIMIT :lim";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':u', $id_usuario, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marcarNotificacionLeida(int $id_notificacion, int $id_usuario): bool {
        $stmt = $this->db->prepare("UPDATE notificaciones SET leida = 1 WHERE id_notificacion = :id AND id_usuario = :u");
        $stmt->bindValue(':id', $id_notificacion, PDO::PARAM_INT);
        $stmt->bindValue(':u', $id_usuario, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function marcarTodasNotificacionesLeidas(int $id_usuario): bool {
        $stmt = $this->db->prepare("UPDATE notificaciones SET leida = 1 WHERE id_usuario = :u AND leida = 0");
        $stmt->bindValue(':u', $id_usuario, PDO::PARAM_INT);
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
}
