<?php
class ReservaModel {
    private $db;

    public function __construct($conexion) {
        $this->db = $conexion;
    }

    public function crearReserva($id_aula, $id_usuario, $fecha, $hora_inicio, $hora_fin) {
        $stmt = $this->db->prepare("
            INSERT INTO reservas (id_usuario, id_aula, fecha, hora_inicio, hora_fin)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$id_usuario, $id_aula, $fecha, $hora_inicio, $hora_fin]);
    }
}
