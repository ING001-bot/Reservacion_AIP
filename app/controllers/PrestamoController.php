<?php
require '../models/PrestamoModel.php';

class PrestamoController {
    private $model;

    public function __construct($conexion) {
        $this->model = new PrestamoModel($conexion);
    }

    // Guardar múltiples préstamos
    public function guardarPrestamosMultiple($id_usuario, $equipos, $hora_inicio, $hora_fin = null) {
        return $this->model->guardarPrestamosMultiple($id_usuario, $equipos, $hora_inicio, $hora_fin);
    }

    // Listar equipos disponibles por tipo
    public function listarEquiposPorTipo($tipo) {
        return $this->model->listarEquiposPorTipo($tipo);
    }

    // Obtener todos los préstamos (para encargado)
    public function obtenerTodosPrestamos() {
        return $this->model->obtenerTodosPrestamos();
    }

    // Cambiar estado a devuelto
    public function devolverEquipo($id_prestamo) {
        return $this->model->devolverEquipo($id_prestamo);
    }
}
