<?php
require_once __DIR__ . '/../models/TipoEquipoModel.php';

class TipoEquipoController {
    private $model;
    public $mensaje = '';
    public $mensaje_tipo = '';

    public function __construct() {
        $this->model = new TipoEquipoModel();
    }

    public function listar() {
        return $this->model->listar();
    }

    public function crear($nombre) {
        $nombre = trim($nombre ?: '');
        if ($nombre === '') {
            $this->mensaje = '⚠ Debes ingresar un nombre de tipo.';
            $this->mensaje_tipo = 'error';
            return;
        }
        if ($this->model->existeNombre($nombre)) {
            $this->mensaje = '⚠ Ya existe un tipo con ese nombre.';
            $this->mensaje_tipo = 'error';
            return;
        }
        $ok = $this->model->crear($nombre);
        $this->mensaje = $ok ? '✅ Tipo creado correctamente.' : '❌ No se pudo crear el tipo.';
        $this->mensaje_tipo = $ok ? 'success' : 'error';
    }

    public function editar($id_tipo, $nombre) {
        $nombre = trim($nombre ?: '');
        if ($nombre === '') {
            $this->mensaje = '⚠ Debes ingresar un nombre de tipo.';
            $this->mensaje_tipo = 'error';
            return;
        }
        // Verificar si ya existe otro tipo con ese nombre (excluyendo el actual)
        $tipoExistente = $this->model->obtenerPorNombre($nombre);
        if ($tipoExistente && $tipoExistente['id_tipo'] != $id_tipo) {
            $this->mensaje = '⚠ Ya existe otro tipo con ese nombre.';
            $this->mensaje_tipo = 'error';
            return;
        }
        $ok = $this->model->actualizar((int)$id_tipo, $nombre);
        $this->mensaje = $ok ? '✅ Tipo actualizado correctamente.' : '❌ No se pudo actualizar el tipo.';
        $this->mensaje_tipo = $ok ? 'success' : 'error';
    }

    public function eliminar($id_tipo) {
        $ok = $this->model->eliminar((int)$id_tipo);
        $this->mensaje = $ok ? '✅ Tipo eliminado.' : '❌ No se pudo eliminar. Verifica que no esté asociado a equipos.';
        $this->mensaje_tipo = $ok ? 'success' : 'error';
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['crear_tipo'])) {
                $this->crear($_POST['nombre_tipo'] ?? '');
            } elseif (isset($_POST['editar_tipo'])) {
                $this->editar($_POST['id_tipo'] ?? 0, $_POST['nombre_tipo'] ?? '');
            } elseif (isset($_POST['eliminar_tipo'])) {
                $this->eliminar($_POST['id_tipo'] ?? 0);
            }
        }
        return [
            'tipos' => $this->listar(),
            'mensaje' => $this->mensaje,
            'mensaje_tipo' => $this->mensaje_tipo,
        ];
    }
}
