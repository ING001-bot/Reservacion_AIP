<?php
require 'app/config/conexion.php';

class EquipoModel {
    private $db;

    public function __construct() {
        global $conexion;
        $this->db = $conexion;
    }

    public function registrarEquipo($nombre_equipo, $tipo_equipo) {
        $stmt = $this->db->prepare("
            INSERT INTO equipos (nombre_equipo, tipo_equipo, estado)
            VALUES (?, ?, 'Disponible')
        ");
        return $stmt->execute([$nombre_equipo, $tipo_equipo]);
    }
}
