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

        // Normalización del nombre según tipo
        $nombre = $this->normalizarNombreAula($nombre, $tipo);

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

        // Normalización del nombre según tipo
        $nombre = $this->normalizarNombreAula($nombre, $tipo);

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

    /**
     * Normaliza el nombre del aula:
     * - Regular: "1a", "1 A", "1°a" -> "1° A"
     * - AIP: "aip1", "Aip 1" -> "AIP1"
     */
    private function normalizarNombreAula(string $nombre, string $tipo): string {
        $n = trim($nombre);
        // Compactar espacios múltiples
        $n = preg_replace('/\s+/', ' ', $n);
        // AIP: AIP + número
        if (strcasecmp($tipo, 'AIP') === 0) {
            if (preg_match('/^aip\s*(\d+)$/i', $n, $m)) {
                return 'AIP' . $m[1];
            }
            // Si sólo es número, prepender AIP
            if (preg_match('/^(\d+)$/', $n, $m)) {
                return 'AIP' . $m[1];
            }
            return strtoupper($n);
        }
        // Regular: numero + letra -> N° L
        // Acepta formatos: "1a", "1 a", "1°a", "1 º a"
        if (preg_match('/^(\d+)\s*[º°]?\s*([a-zA-Z])$/u', $n, $m)) {
            $num = $m[1];
            $letra = strtoupper($m[2]);
            return $num . '° ' . $letra;
        }
        // Si solo número, añadir símbolo grado sin letra (ej. "1" -> "1°")
        if (preg_match('/^(\d+)$/', $n, $m)) {
            return $m[1] . '°';
        }
        // Por defecto: capitalizar palabras y mayúsculas de letras sueltas
        return mb_convert_case($n, MB_CASE_TITLE, 'UTF-8');
    }
}
?>
