<?php
require_once '../models/EquipoModel.php';

class EquipoController {
    private $equipoModel;

    public function __construct() {
        $this->equipoModel = new EquipoModel();
    }

    /** Registrar equipo */
    public function registrarEquipo($nombre_equipo, $tipo_equipo) {
        if (!$nombre_equipo || !$tipo_equipo) {
            return ['error' => true, 'mensaje' => '⚠ Todos los campos son obligatorios.'];
        }
        $ok = $this->equipoModel->registrarEquipo($nombre_equipo, $tipo_equipo);
        return [
            'error' => !$ok,
            'mensaje' => $ok ? "✅ Equipo '$nombre_equipo' registrado correctamente." : "❌ Error al registrar el equipo."
        ];
    }

    /** Listar equipos */
    public function listarEquipos() {
        return $this->equipoModel->obtenerEquipos();
    }

    /** Eliminar equipo */
    public function eliminarEquipo($id_equipo) {
        $ok = $this->equipoModel->eliminarEquipo($id_equipo);
        return [
            'error' => !$ok,
            'mensaje' => $ok ? "✅ Equipo eliminado correctamente." : "❌ Error al eliminar."
        ];
    }

    /** Editar equipo */
    public function editarEquipo($id_equipo, $nombre_equipo, $tipo_equipo) {
        if (!$nombre_equipo || !$tipo_equipo) {
            return ['error' => true, 'mensaje' => '⚠ Todos los campos son obligatorios.'];
        }
        $ok = $this->equipoModel->actualizarEquipo($id_equipo, $nombre_equipo, $tipo_equipo);
        return [
            'error' => !$ok,
            'mensaje' => $ok ? "✅ Equipo actualizado correctamente." : "❌ Error al actualizar."
        ];
    }

    /** Manejo de POST */
    public function handleRequest() {
        $mensaje = '';
        $mensaje_tipo = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['registrar_equipo'])) {
                $res = $this->registrarEquipo($_POST['nombre_equipo'], $_POST['tipo_equipo']);
                $mensaje = $res['mensaje'];
                $mensaje_tipo = $res['error'] ? 'error' : 'success';
            }

            if (isset($_POST['eliminar_equipo'])) {
                $res = $this->eliminarEquipo($_POST['id_equipo']);
                $mensaje = $res['mensaje'];
                $mensaje_tipo = $res['error'] ? 'error' : 'success';
            }

            if (isset($_POST['editar_equipo'])) {
                $res = $this->editarEquipo($_POST['id_equipo'], $_POST['nombre_equipo'], $_POST['tipo_equipo']);
                $mensaje = $res['mensaje'];
                $mensaje_tipo = $res['error'] ? 'error' : 'success';
            }
        }

        // Siempre obtenemos los equipos para la vista
        $equipos = $this->listarEquipos();
        return ['equipos' => $equipos, 'mensaje' => $mensaje, 'mensaje_tipo' => $mensaje_tipo];
    }
}
?>
