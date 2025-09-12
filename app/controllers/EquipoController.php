<?php
require_once '../models/EquipoModel.php';

class EquipoController {
    private $equipoModel;

    public function __construct() {
        $this->equipoModel = new EquipoModel();
    }

    /** Registrar equipo */
    public function registrarEquipo($nombre_equipo, $tipo_equipo, $stock) {
        if (!$nombre_equipo || !$tipo_equipo || $stock < 0) {
            return ['error' => true, 'mensaje' => '⚠ Todos los campos son obligatorios y el stock no puede ser negativo.'];
        }
        $ok = $this->equipoModel->registrarEquipo($nombre_equipo, $tipo_equipo, $stock);
        return [
            'error' => !$ok,
            'mensaje' => $ok ? "✅ Equipo '$nombre_equipo' registrado correctamente." : "❌ Error al registrar el equipo."
        ];
    }

    /** Listar equipos */
    public function listarEquipos($soloActivos = false) {
        return $soloActivos ? $this->equipoModel->obtenerEquiposActivos() : $this->equipoModel->obtenerEquipos();
    }

    /** Dar de baja equipo */
    public function darDeBajaEquipo($id_equipo) {
        $ok = $this->equipoModel->darDeBajaEquipo($id_equipo);
        return [
            'error' => !$ok,
            'mensaje' => $ok ? "✅ Equipo dado de baja correctamente." : "❌ Error al dar de baja."
        ];
    }

    /** Restaurar equipo */
    public function restaurarEquipo($id_equipo) {
        $ok = $this->equipoModel->restaurarEquipo($id_equipo);
        return [
            'error' => !$ok,
            'mensaje' => $ok ? "✅ Equipo restaurado correctamente." : "❌ Error al restaurar."
        ];
    }

    /** Eliminar definitivo */
    public function eliminarEquipoDefinitivo($id_equipo) {
        $ok = $this->equipoModel->eliminarEquipoDefinitivo($id_equipo);
        return [
            'error' => !$ok,
            'mensaje' => $ok ? "✅ Equipo eliminado definitivamente." : "❌ Error al eliminar."
        ];
    }

    /** Manejo de POST */
    public function handleRequest() {
        $mensaje = '';
        $mensaje_tipo = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['registrar_equipo'])) {
                $res = $this->registrarEquipo($_POST['nombre_equipo'], $_POST['tipo_equipo'], $_POST['stock']);
                $mensaje = $res['mensaje'];
                $mensaje_tipo = $res['error'] ? 'error' : 'success';
            }
            if (isset($_POST['dar_baja_equipo'])) {
                $res = $this->darDeBajaEquipo($_POST['id_equipo']);
                $mensaje = $res['mensaje'];
                $mensaje_tipo = $res['error'] ? 'error' : 'success';
            }
            if (isset($_POST['restaurar_equipo'])) {
                $res = $this->restaurarEquipo($_POST['id_equipo']);
                $mensaje = $res['mensaje'];
                $mensaje_tipo = $res['error'] ? 'error' : 'success';
            }
            if (isset($_POST['eliminar_equipo_def'])) {
                $res = $this->eliminarEquipoDefinitivo($_POST['id_equipo']);
                $mensaje = $res['mensaje'];
                $mensaje_tipo = $res['error'] ? 'error' : 'success';
            }
        }

        $equipos = $this->listarEquipos();
        return ['equipos' => $equipos, 'mensaje' => $mensaje, 'mensaje_tipo' => $mensaje_tipo];
    }
}
?>
