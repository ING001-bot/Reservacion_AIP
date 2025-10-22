<?php
require_once '../models/EquipoModel.php';
require_once '../models/TipoEquipoModel.php';

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
        // Validar existencia del tipo
        $tipoModel = new TipoEquipoModel();
        if (!$tipoModel->existeNombre($tipo_equipo)) {
            return ['error' => true, 'mensaje' => '⚠ Debes crear primero el tipo de equipo "'.htmlspecialchars($tipo_equipo).'" en ⚙ Tipos de Equipo.'];
        }
        // Normalizaciones
        $nombreNorm = ucwords(strtolower(trim($nombre_equipo)));
        $tipoNorm = strtoupper(trim($tipo_equipo));

        // 1) Evitar duplicado por nombre (independiente del tipo)
        if ($this->equipoModel->existeNombre($nombreNorm)) {
            return ['error' => true, 'mensaje' => '❌ Ya existe un equipo con el nombre "'.htmlspecialchars($nombreNorm).'" en el inventario.'];
        }

        // 2) Validar consistencia nombre vs tipo seleccionado
        $map = [
            'LAPTOP' => ['laptop','notebook'],
            'PROYECTOR' => ['proyector','proyec','proy'],
            'MOUSE' => ['mouse','raton','ratón'],
            'EXTENSIÓN' => ['extension','extensión','cable','alargador'],
            'PARLANTE' => ['parlante','parlan','bafle','altavoz','speaker']
        ];
        $lowerName = mb_strtolower($nombre_equipo, 'UTF-8');
        foreach ($map as $tipo => $palabras) {
            foreach ($palabras as $p) {
                if (mb_strpos($lowerName, $p, 0, 'UTF-8') !== false) {
                    if ($tipoNorm !== $tipo) {
                        return ['error' => true, 'mensaje' => '❌ El nombre parece corresponder a "'.$tipo.'", pero seleccionaste "'.htmlspecialchars($tipoNorm).'". Corrige el tipo.'];
                    }
                    break 2; // ya matcheó un tipo
                }
            }
        }
        $ok = $this->equipoModel->registrarEquipo($nombreNorm, $tipoNorm, (int)$stock);
        return [
            'error' => !$ok,
            'mensaje' => $ok ? "✅ Equipo '$nombre_equipo' registrado correctamente." : "❌ Error al registrar el equipo."
        ];
    }

    /** Actualizar equipo */
    public function actualizarEquipo($id_equipo, $nombre_equipo, $tipo_equipo, $stock) {
        // Validar que los campos no estén vacíos
        if (empty($id_equipo) || empty($nombre_equipo) || empty($tipo_equipo) || !isset($stock)) {
            return ['error' => true, 'mensaje' => '⚠ Todos los campos son obligatorios.'];
        }
        
        // Validar que el stock sea un número válido
        if (!is_numeric($stock) || $stock < 0) {
            return ['error' => true, 'mensaje' => '⚠ El stock debe ser un número mayor o igual a 0.'];
        }
        
        // Validar existencia del tipo
        $tipoModel = new TipoEquipoModel();
        if (!$tipoModel->existeNombre($tipo_equipo)) {
            return ['error' => true, 'mensaje' => '⚠ El tipo de equipo "'.htmlspecialchars($tipo_equipo).'" no existe.'];
        }
        
        $ok = $this->equipoModel->actualizarEquipo($id_equipo, $nombre_equipo, $tipo_equipo, $stock);
        return [
            'error' => !$ok,
            'mensaje' => $ok ? "✅ Equipo actualizado correctamente." : "❌ Error al actualizar el equipo."
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
            if (isset($_POST['editar_equipo'])) {
                $res = $this->actualizarEquipo(
                    $_POST['id_equipo'],
                    $_POST['nombre_equipo'],
                    $_POST['tipo_equipo'],
                    $_POST['stock']
                );
                $mensaje = $res['mensaje'];
                $mensaje_tipo = $res['error'] ? 'error' : 'success';
            }
        }

        $equipos = $this->listarEquipos();
        return ['equipos' => $equipos, 'mensaje' => $mensaje, 'mensaje_tipo' => $mensaje_tipo];
    }
}
?>
