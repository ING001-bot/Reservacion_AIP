<?php
class ReservaModel {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    public function crearReserva($id_usuario, $id_aula, $fecha, $hora_inicio, $hora_fin) {
        $stmt = $this->conexion->prepare(
            "INSERT INTO reservas (id_usuario, id_aula, fecha, hora_inicio, hora_fin)
             VALUES (:id_usuario, :id_aula, :fecha, :hora_inicio, :hora_fin)"
        );

        $stmt->execute([
            ':id_usuario' => $id_usuario,
            ':id_aula' => $id_aula,
            ':fecha' => $fecha,
            ':hora_inicio' => $hora_inicio,
            ':hora_fin' => $hora_fin
        ]);
    }
}
