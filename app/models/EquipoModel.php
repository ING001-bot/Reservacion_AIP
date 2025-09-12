<?php
require '../config/conexion.php';

class EquipoModel {
    private $db;

    public function __construct() {
        global $conexion;
        $this->db = $conexion;
    }

    /** Registrar equipo */
    public function registrarEquipo($nombre_equipo, $tipo_equipo, $stock) {
        $stmt = $this->db->prepare("INSERT INTO equipos (nombre_equipo, tipo_equipo, stock, activo) VALUES (?, ?, ?, 1)");
        return $stmt->execute([$nombre_equipo, $tipo_equipo, $stock]);
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

    /** Dar de baja (baja lógica) */
    public function darDeBajaEquipo($id_equipo) {
        $stmt = $this->db->prepare("UPDATE equipos SET activo = 0 WHERE id_equipo = ?");
        return $stmt->execute([$id_equipo]);
    }

    /** Restaurar equipo */
    public function restaurarEquipo($id_equipo) {
        $stmt = $this->db->prepare("UPDATE equipos SET activo = 1 WHERE id_equipo = ?");
        return $stmt->execute([$id_equipo]);
    }

    /** Eliminar definitivo */
    public function eliminarEquipoDefinitivo($id_equipo) {
        $stmt = $this->db->prepare("DELETE FROM equipos WHERE id_equipo = ?");
        return $stmt->execute([$id_equipo]);
    }

    /** Reducir stock al prestar */
    public function reducirStock($id_equipo) {
        $stmt = $this->db->prepare("UPDATE equipos SET stock = stock - 1 WHERE id_equipo = ? AND stock > 0");
        return $stmt->execute([$id_equipo]);
    }

    /** Aumentar stock al devolver */
    public function aumentarStock($id_equipo) {
        $stmt = $this->db->prepare("UPDATE equipos SET stock = stock + 1 WHERE id_equipo = ?");
        return $stmt->execute([$id_equipo]);
    }
}
?>
