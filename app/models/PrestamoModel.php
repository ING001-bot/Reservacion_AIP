<?php
require "../config/conexion.php";
class PrestamoModel {
    private $db;
    public function __construct($conexion) { $this->db = $conexion; }
    // Permite a controladores ejecutar consultas personalizadas cuando el modelo no tiene un método específico
    public function getDb(): PDO {
        return $this->db;
    }

    public function guardarPrestamosMultiple($id_usuario, $equipos, $fecha_prestamo, $hora_inicio, $id_aula, $hora_fin = null) {
        if (!$id_usuario || empty($equipos) || !$id_aula) {
            return ['mensaje'=>'⚠ No se proporcionó usuario, equipos o aula.','tipo'=>'error'];
        }
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("\n                INSERT INTO prestamos (id_usuario, id_equipo, id_aula, fecha_prestamo, estado, hora_inicio, hora_fin)
                VALUES (?, ?, ?, ?, 'Prestado', ?, ?)
            ");
            foreach ($equipos as $val) {
                if ($val) $stmt->execute([$id_usuario, $val, $id_aula, $fecha_prestamo, $hora_inicio, $hora_fin ?? null]);
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
        $stmt = $this->db->prepare("\n            SELECT p.id_prestamo, e.nombre_equipo, e.tipo_equipo, u.nombre, \n                   a.nombre_aula, a.tipo,\n                   p.fecha_prestamo, p.hora_inicio, p.hora_fin, \n                   p.fecha_devolucion, p.estado\n            FROM prestamos p\n            LEFT JOIN equipos e ON p.id_equipo = e.id_equipo\n            JOIN usuarios u ON p.id_usuario = u.id_usuario\n            JOIN aulas a ON p.id_aula = a.id_aula\n            ORDER BY p.fecha_prestamo DESC\n        ");
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
        $stmt = $this->db->prepare("\n            UPDATE prestamos \n            SET estado='Devuelto', fecha_devolucion=CURDATE(), comentario_devolucion = :comentario \n            WHERE id_prestamo=:id\n        ");
        $stmt->bindValue(':comentario', $comentario, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id_prestamo, PDO::PARAM_INT);
        return $stmt->execute();
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

    // ========================= NUEVO: Packs de préstamo =========================
    /**
     * Crea un pack de préstamo (encabezado + items) validando stock por tipo en la fecha indicada.
     * $items: array de ['tipo' => string, 'cantidad' => int, 'es_complemento' => bool]
     */
    public function crearPrestamoPack(int $id_usuario, int $id_aula, string $fecha_prestamo, string $hora_inicio, ?string $hora_fin, array $items): array {
        // Normalizar items: filtrar cantidades > 0
        $norm = [];
        foreach ($items as $it) {
            $tipo = trim((string)($it['tipo'] ?? ''));
            $cant = (int)($it['cantidad'] ?? 0);
            if ($tipo !== '' && $cant > 0) {
                $norm[] = [
                    'tipo' => $tipo,
                    'cantidad' => $cant,
                    'es_complemento' => !empty($it['es_complemento']) ? 1 : 0,
                ];
            }
        }
        if (empty($norm)) {
            return ['tipo' => 'error', 'mensaje' => '⚠ Debes seleccionar al menos un equipo o complemento.'];
        }

        // Validar stock por tipo para la fecha
        $faltantes = $this->validarStockPorFecha($fecha_prestamo, $norm);
        if (!empty($faltantes)) {
            // Construir mensaje de faltantes
            $msgs = [];
            foreach ($faltantes as $tipo => $data) {
                $msgs[] = $tipo . ' (disp: ' . $data['disponible'] . ', solicitado: ' . $data['solicitado'] . ')';
            }
            return ['tipo' => 'error', 'mensaje' => '❌ Stock insuficiente para: ' . implode(', ', $msgs)];
        }

        // Insertar pack e items
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO prestamos_pack (id_usuario, id_aula, fecha_prestamo, hora_inicio, hora_fin, estado) VALUES (?, ?, ?, ?, ?, 'Prestado')");
            $stmt->execute([$id_usuario, $id_aula, $fecha_prestamo, $hora_inicio, $hora_fin]);
            $id_pack = (int)$this->db->lastInsertId();

            $stmtItem = $this->db->prepare("INSERT INTO prestamos_pack_items (id_pack, tipo_equipo, es_complemento, cantidad) VALUES (?, ?, ?, ?)");
            foreach ($norm as $it) {
                $stmtItem->execute([$id_pack, $it['tipo'], $it['es_complemento'], $it['cantidad']]);
            }

            $this->db->commit();
            return ['tipo' => 'success', 'mensaje' => '✅ Préstamo registrado correctamente.', 'id_pack' => $id_pack];
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return ['tipo' => 'error', 'mensaje' => '❌ Error al registrar el préstamo: ' . $e->getMessage()];
        }
    }

    /** Devuelve packs del usuario (sin items) */
    public function listarPacksPorUsuario(int $id_usuario): array {
        $stmt = $this->db->prepare("SELECT p.id_pack, p.fecha_prestamo, p.hora_inicio, p.hora_fin, p.estado, p.fecha_devolucion, a.nombre_aula FROM prestamos_pack p JOIN aulas a ON p.id_aula = a.id_aula WHERE p.id_usuario = ? ORDER BY p.fecha_prestamo DESC, p.hora_inicio DESC");
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Devuelve items de un pack */
    public function obtenerItemsDePack(int $id_pack): array {
        $stmt = $this->db->prepare("SELECT tipo_equipo, es_complemento, cantidad FROM prestamos_pack_items WHERE id_pack = ? ORDER BY es_complemento ASC, tipo_equipo ASC");
        $stmt->execute([$id_pack]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Listar packs con filtros similares a prestamos unitarios */
    public function listarPacksFiltrados(?string $estado, ?string $desde, ?string $hasta, ?string $q): array {
        $sql = "SELECT p.id_pack, u.nombre AS nombre_usuario, a.nombre_aula, a.tipo AS tipo_aula, p.fecha_prestamo, p.hora_inicio, p.hora_fin, p.fecha_devolucion, p.estado
                FROM prestamos_pack p
                JOIN usuarios u ON p.id_usuario = u.id_usuario
                JOIN aulas a ON p.id_aula = a.id_aula
                WHERE 1=1";
        $params = [];
        if ($estado) { $sql .= " AND p.estado = :estado"; $params[':estado'] = $estado; }
        if ($desde) { $sql .= " AND p.fecha_prestamo >= :desde"; $params[':desde'] = $desde; }
        if ($hasta) { $sql .= " AND p.fecha_prestamo <= :hasta"; $params[':hasta'] = $hasta; }
        if ($q) {
            $sql .= " AND (u.nombre LIKE :q OR a.nombre_aula LIKE :q)";
            $params[':q'] = "%$q%";
        }
        $sql .= " ORDER BY p.fecha_prestamo DESC, p.hora_inicio DESC";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $packs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Adjuntar un resumen de items por pack
        $res = [];
        $stmtItems = $this->db->prepare("SELECT tipo_equipo, es_complemento, cantidad FROM prestamos_pack_items WHERE id_pack = ? ORDER BY es_complemento ASC, tipo_equipo ASC");
        foreach ($packs as $p) {
            $stmtItems->execute([$p['id_pack']]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            $p['items'] = $items;
            $res[] = $p;
        }
        return $res;
    }

    /** Marcar pack como devuelto con comentario opcional */
    public function devolverPack(int $id_pack, ?string $comentario = null): bool {
        $stmt = $this->db->prepare("UPDATE prestamos_pack SET estado='Devuelto', fecha_devolucion=CURDATE(), comentario_devolucion = :c WHERE id_pack = :id");
        $stmt->bindValue(':c', $comentario, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id_pack, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /** Suma lo prestado por tipo en una fecha (packs activos) */
    private function sumarPrestadoPorTipoEnFecha(string $fecha_prestamo): array {
        $sql = "SELECT i.tipo_equipo, COALESCE(SUM(i.cantidad),0) AS prestado FROM prestamos_pack p JOIN prestamos_pack_items i ON p.id_pack = i.id_pack WHERE p.estado = 'Prestado' AND p.fecha_prestamo = :f GROUP BY i.tipo_equipo";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':f' => $fecha_prestamo]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) { $map[$r['tipo_equipo']] = (int)$r['prestado']; }
        return $map;
    }

    /**
     * Obtiene stock total por tipo dinámicamente desde equipos activos.
     * Así, cualquier alta/baja desde Admin se refleja inmediatamente sin depender de stock_equipos.
     */
    private function obtenerStockPorTipo(): array {
        $sql = "SELECT tipo_equipo, COUNT(*) AS stock_total FROM equipos WHERE activo = 1 GROUP BY tipo_equipo";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) {
            $map[$r['tipo_equipo']] = (int)($r['stock_total'] ?? 0);
        }
        return $map;
    }

    /** Valida stock para cada tipo solicitado en la fecha */
    private function validarStockPorFecha(string $fecha, array $items): array {
        $sol = [];
        foreach ($items as $it) { $sol[$it['tipo']] = ($sol[$it['tipo']] ?? 0) + (int)$it['cantidad']; }
        $stock = $this->obtenerStockPorTipo();
        $pre = $this->sumarPrestadoPorTipoEnFecha($fecha);
        $faltantes = [];
        foreach ($sol as $tipo => $cant) {
            $total = (int)($stock[$tipo] ?? 0);
            $ya = (int)($pre[$tipo] ?? 0);
            $disp = max(0, $total - $ya);
            if ($cant > $disp) {
                $faltantes[$tipo] = ['disponible' => $disp, 'solicitado' => $cant];
            }
        }
        return $faltantes; // vacío si todo ok
    }

    /** Calcula disponibilidad por tipo para una fecha: total_activo - ya_prestado */
    public function calcularDisponibilidadPorFecha(string $fecha): array {
        $stock = $this->obtenerStockPorTipo();
        $prestado = $this->sumarPrestadoPorTipoEnFecha($fecha);
        $disp = [];
        // incluir todos los tipos presentes en stock
        foreach ($stock as $tipo => $total) {
            $ya = (int)($prestado[$tipo] ?? 0);
            $disp[$tipo] = max(0, (int)$total - $ya);
        }
        // incluir tipos que no tengan stock (0) pero sí prestado (para mostrar 0)
        foreach ($prestado as $tipo => $ya) {
            if (!isset($disp[$tipo])) $disp[$tipo] = 0;
        }
        return $disp;
    }
}
