<?php
require_once __DIR__ . '/../config/conexion.php';

class TipoEquipoModel {
    private $db;

    public function __construct() {
        global $conexion;
        $this->db = $conexion;
        $this->ensureTable();
        $this->normalizarTiposExistentes();
    }
    
    /** Normalizar tipos existentes a MAYÚSCULAS */
    private function normalizarTiposExistentes() {
        try {
            $this->db->exec("UPDATE tipos_equipo SET nombre = UPPER(TRIM(nombre)) WHERE nombre != UPPER(nombre)");
        } catch (\Throwable $e) { /* silencioso */ }
    }

    private function ensureTable() {
        $sql = "CREATE TABLE IF NOT EXISTS tipos_equipo (
            id_tipo INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL UNIQUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        try { $this->db->exec($sql); } catch (\Throwable $e) { /* noop */ }
    }

    public function listar() {
        $stmt = $this->db->prepare("SELECT id_tipo, nombre FROM tipos_equipo ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function existeNombre($nombre) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tipos_equipo WHERE LOWER(nombre)=LOWER(?)");
        $stmt->execute([$nombre]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function crear($nombre) {
        if (!$nombre) return false;
        $nombre = strtoupper(trim($nombre));
        $stmt = $this->db->prepare("INSERT INTO tipos_equipo (nombre) VALUES (?)");
        return $stmt->execute([$nombre]);
    }

    public function eliminar($id_tipo) {
        // Nota: prevenimos eliminar si hay equipos con ese tipo
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM equipos WHERE tipo_equipo = (SELECT nombre FROM tipos_equipo WHERE id_tipo = ?)");
        $stmt->execute([$id_tipo]);
        if ((int)$stmt->fetchColumn() > 0) { return false; }
        $stmt = $this->db->prepare("DELETE FROM tipos_equipo WHERE id_tipo = ?");
        return $stmt->execute([$id_tipo]);
    }
}
