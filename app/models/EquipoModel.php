<?php
require '../config/conexion.php';

class EquipoModel {
    private $db;

    public function __construct() {
        global $conexion;
        $this->db = $conexion;
    }

    public function registrarEquipo($nombre_equipo, $tipo_equipo) {
        $stmt = $this->db->prepare("INSERT INTO equipos (nombre_equipo, tipo_equipo) VALUES (?, ?)");
        return $stmt->execute([$nombre_equipo, $tipo_equipo]);
    }

    public function obtenerEquipos() {
        $stmt = $this->db->prepare("SELECT * FROM equipos");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminarEquipo($id_equipo) {
        $stmt = $this->db->prepare("DELETE FROM equipos WHERE id_equipo = ?");
        return $stmt->execute([$id_equipo]);
    }

    public function actualizarEquipo($id_equipo, $nombre_equipo, $tipo_equipo) {
        $stmt = $this->db->prepare("UPDATE equipos SET nombre_equipo = ?, tipo_equipo = ? WHERE id_equipo = ?");
        return $stmt->execute([$nombre_equipo, $tipo_equipo, $id_equipo]);
    }
}
?>
