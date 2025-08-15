<?php
require '../config/conexion.php';

class EquipoModel {
    private $db;

    public function __construct() {
        global $conexion;
        $this->db = $conexion;
    }

    public function registrarEquipo($nombre_equipo, $tipo_equipo) {
        $stmt = $this->db->prepare(
            "INSERT INTO equipos (nombre_equipo, tipo_equipo) VALUES (?, ?)"
        );
        return $stmt->execute([$nombre_equipo, $tipo_equipo]);
    }

    public function obtenerEquipos() {
        $stmt = $this->db->prepare("SELECT * FROM equipos");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
