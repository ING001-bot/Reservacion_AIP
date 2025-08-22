<?php
require '../models/PrestamoModel.php';

class PrestamoController {
    private $model;

    public function __construct($conexion) {
        $this->model = new PrestamoModel($conexion);
    }

    public function guardarPrestamosMultiple($id_usuario, $equipos, $fecha_prestamo, $hora_inicio, $hora_fin = null, $id_aula) {
        return $this->model->guardarPrestamosMultiple($id_usuario, $equipos, $fecha_prestamo, $hora_inicio, $hora_fin, $id_aula);
    }

    public function listarEquiposPorTipo($tipo) {
        return $this->model->listarEquiposPorTipo($tipo);
    }

    public function obtenerTodosPrestamos() {
        return $this->model->obtenerTodosPrestamos();
    }

    public function listarPrestamosPorUsuario($id_usuario) {
        return $this->model->listarPrestamosPorUsuario($id_usuario);
    }

    public function devolverEquipo($id_prestamo) {
        return $this->model->devolverEquipo($id_prestamo);
    }
}
?>
