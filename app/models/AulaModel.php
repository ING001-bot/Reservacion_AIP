<?php
class AulaModel {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    public function obtenerTodas() {
        $stmt = $this->conexion->query("SELECT id_aula, nombre_aula, capacidad FROM aulas ORDER BY nombre_aula ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
