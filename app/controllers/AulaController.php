<?php
require_once '../models/AulaModel.php';

class AulaController {
    private $model;
    public $mensaje = '';
    public $mensaje_tipo = '';

    public function __construct($conexion) {
        $this->model = new AulaModel($conexion);
    }

    public function registrarAula($post) {
        $nombre = trim($post['nombre_aula'] ?? '');
        $capacidad = intval($post['capacidad'] ?? 0);
        $tipo = $post['tipo'] ?? 'Regular';

        if (!$nombre || $capacidad < 1) {
            $this->mensaje = "⚠ Por favor completa todos los campos correctamente.";
            $this->mensaje_tipo = "error";
            return false;
        }

        $ok = $this->model->crearAula($nombre, $capacidad, $tipo);
        $this->mensaje = $ok ? "✅ Aula registrada correctamente." : "❌ Error al registrar el aula.";
        $this->mensaje_tipo = $ok ? "success" : "error";
        return $ok;
    }

    public function editarAula($post) {
        $id = intval($post['id_aula'] ?? 0);
        $nombre = trim($post['nombre_aula'] ?? '');
        $capacidad = intval($post['capacidad'] ?? 0);
        $tipo = $post['tipo'] ?? 'Regular';

        if (!$id || !$nombre || $capacidad < 1) {
            $this->mensaje = "⚠ Completa los campos correctamente.";
            $this->mensaje_tipo = "error";
            return false;
        }

        $ok = $this->model->actualizarAula($id, $nombre, $capacidad, $tipo);
        $this->mensaje = $ok ? "✅ Aula actualizada correctamente." : "❌ Error al actualizar.";
        $this->mensaje_tipo = $ok ? "success" : "error";
        return $ok;
    }

    public function eliminarAula($id) {
        $ok = $this->model->eliminarAula($id);
        $this->mensaje = $ok ? "✅ Aula eliminada correctamente." : "❌ Error al eliminar.";
        $this->mensaje_tipo = $ok ? "success" : "error";
        return $ok;
    }

    public function listarAulas($tipo = null) {
        return $this->model->obtenerAulas($tipo);
    }
}
?>
