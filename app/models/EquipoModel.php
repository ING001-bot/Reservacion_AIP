<?php
require_once __DIR__ . '/../config/conexion.php';

class EquipoModel {
    private $db;

    public function __construct() {
        global $conexion;
        $this->db = $conexion;
        $this->normalizarTiposExistentes();
    }
    
    /** Normalizar tipos existentes a MAYÚSCULAS (ejecuta siempre para mantener consistencia) */
    private function normalizarTiposExistentes() {
        try {
            // Normalizar todos los tipos: MAYÚSCULAS y sin espacios extras
            $this->db->exec("UPDATE equipos SET tipo_equipo = UPPER(TRIM(tipo_equipo)) WHERE tipo_equipo != UPPER(TRIM(tipo_equipo))");
        } catch (\Throwable $e) { 
            error_log("Error al normalizar tipos de equipo: " . $e->getMessage());
        }
    }

    /** Registrar equipo */
    public function registrarEquipo($nombre_equipo, $tipo_equipo, $stock) {
        $nombre_equipo = ucwords(strtolower(trim($nombre_equipo)));
        $tipo_equipo = strtoupper(trim($tipo_equipo));
        $stmt = $this->db->prepare("INSERT INTO equipos (nombre_equipo, tipo_equipo, stock, stock_maximo, activo) VALUES (?, ?, ?, ?, 1)");
        return $stmt->execute([$nombre_equipo, $tipo_equipo, $stock, $stock]);
    }

    /** Verifica si existe un equipo con el mismo nombre (insensible a mayúsculas) */
    public function existeNombre(string $nombre): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM equipos WHERE LOWER(nombre_equipo) = LOWER(?)");
        $stmt->execute([trim($nombre)]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** Verifica si existe un equipo con el mismo nombre y tipo */
    public function existeNombreYTipo(string $nombre, string $tipo): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM equipos WHERE LOWER(nombre_equipo) = LOWER(?) AND UPPER(tipo_equipo) = UPPER(?)");
        $stmt->execute([trim($nombre), trim($tipo)]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** Listar todos los equipos (solo admin) */
    public function obtenerEquipos() {
        $stmt = $this->db->prepare("SELECT * FROM equipos");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Listar solo equipos activos y con stock > 0 (para profesores) */
    public function obtenerEquiposActivos() {
        $stmt = $this->db->prepare("SELECT * FROM equipos WHERE activo = 1 AND stock > 0");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Eliminar equipo permanentemente */
    public function darDeBajaEquipo($id_equipo) {
        $stmt = $this->db->prepare("DELETE FROM equipos WHERE id_equipo = ?");
        return $stmt->execute([$id_equipo]);
    }

    /** Restaurar equipo (ya no aplica con eliminación física) */
    public function restaurarEquipo($id_equipo) {
        // Método obsoleto - no se puede restaurar lo que se eliminó físicamente
        return false;
    }

    /** Eliminar definitivo (ahora es lo mismo que dar de baja) */
    public function eliminarEquipoDefinitivo($id_equipo) {
        $stmt = $this->db->prepare("DELETE FROM equipos WHERE id_equipo = ?");
        return $stmt->execute([$id_equipo]);
    }

    /** Reducir stock al prestar */
    public function reducirStock($id_equipo) {
        $stmt = $this->db->prepare("UPDATE equipos SET stock = stock - 1 WHERE id_equipo = ? AND stock > 0");
        return $stmt->execute([$id_equipo]);
    }

    /** Aumentar stock al devolver (sin superar el máximo) */
    public function aumentarStock($id_equipo) {
        $stmt = $this->db->prepare("UPDATE equipos SET stock = LEAST(stock + 1, stock_maximo) WHERE id_equipo = ?");
        return $stmt->execute([$id_equipo]);
    }

    /** Actualizar información del equipo */
    public function actualizarEquipo($id_equipo, $nombre_equipo, $tipo_equipo, $stock) {
        $nombre_equipo = ucwords(strtolower(trim($nombre_equipo)));
        $tipo_equipo = strtoupper(trim($tipo_equipo));
        
        $stmt = $this->db->prepare("UPDATE equipos SET nombre_equipo = ?, tipo_equipo = ?, stock = ?, stock_maximo = ? WHERE id_equipo = ?");
        return $stmt->execute([$nombre_equipo, $tipo_equipo, $stock, $stock, $id_equipo]);
    }
}
?>
