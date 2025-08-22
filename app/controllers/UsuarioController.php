<?php
require_once '../models/UsuarioModel.php';

class UsuarioController {
    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
    }

    /** Registrar usuario admin */
    public function registrarUsuario($nombre, $correo, $contraseña, $tipo_usuario) {
        if ($this->usuarioModel->existeCorreo($correo)) {
            return ['error' => true, 'mensaje' => '⚠️ El correo ya está registrado'];
        }
        if (strlen($contraseña) < 6) {
            return ['error' => true, 'mensaje' => '⚠️ La contraseña debe tener al menos 6 caracteres'];
        }
        $hash = password_hash($contraseña, PASSWORD_BCRYPT);
        $ok = $this->usuarioModel->registrar($nombre, $correo, $hash, $tipo_usuario);
        return ['error' => !$ok, 'mensaje' => $ok ? '✅ Usuario registrado correctamente' : '❌ Error al registrar'];
    }

    /** Registrar profesor público */
    public function registrarProfesorPublico($nombre, $correo, $contraseña) {
        if ($this->usuarioModel->existeCorreo($correo)) {
            return ['error' => true, 'mensaje' => '⚠️ El correo ya está en uso'];
        }
        if (strlen($contraseña) < 6) {
            return ['error' => true, 'mensaje' => '⚠️ La contraseña debe tener al menos 6 caracteres'];
        }
        $hash = password_hash($contraseña, PASSWORD_BCRYPT);
        $ok = $this->usuarioModel->registrar($nombre, $correo, $hash, 'Profesor');
        return ['error' => !$ok, 'mensaje' => $ok ? '✅ Cuenta creada con éxito' : '❌ Error al crear cuenta'];
    }

    /** Listar usuarios */
    public function listarUsuarios() {
        return $this->usuarioModel->obtenerUsuarios();
    }

    /** Eliminar usuario */
    public function eliminarUsuario($id_usuario) {
        $ok = $this->usuarioModel->eliminarUsuario($id_usuario);
        return [
            'error' => !$ok,
            'mensaje' => $ok ? "✅ Usuario eliminado correctamente." : "❌ Error al eliminar."
        ];
    }

    /** Editar usuario */
    public function editarUsuario($id_usuario, $nombre, $correo, $tipo_usuario) {
        if (!$nombre || !$correo || !$tipo_usuario) {
            return ['error' => true, 'mensaje' => '⚠ Todos los campos son obligatorios.'];
        }
        $ok = $this->usuarioModel->actualizarUsuario($id_usuario, $nombre, $correo, $tipo_usuario);
        return [
            'error' => !$ok,
            'mensaje' => $ok ? "✅ Usuario actualizado correctamente." : "❌ Error al actualizar."
        ];
    }

    /** Manejo de POST */
    public function handleRequest() {
        $mensaje = '';
        $mensaje_tipo = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['registrar_usuario_admin'])) {
                $res = $this->registrarUsuario($_POST['nombre'], $_POST['correo'], $_POST['contraseña'], $_POST['tipo']);
                $mensaje = $res['mensaje'];
                $mensaje_tipo = $res['error'] ? 'error' : 'success';
            }

            if (isset($_POST['registrar_profesor_publico'])) {
                $res = $this->registrarProfesorPublico($_POST['nombre'], $_POST['correo'], $_POST['contraseña']);
                $mensaje = $res['mensaje'];
                $mensaje_tipo = $res['error'] ? 'error' : 'success';
            }

            if (isset($_POST['eliminar_usuario'])) {
                $res = $this->eliminarUsuario($_POST['id_usuario']);
                $mensaje = $res['mensaje'];
                $mensaje_tipo = $res['error'] ? 'error' : 'success';
            }

            if (isset($_POST['editar_usuario'])) {
                $res = $this->editarUsuario($_POST['id_usuario'], $_POST['nombre'], $_POST['correo'], $_POST['tipo']);
                $mensaje = $res['mensaje'];
                $mensaje_tipo = $res['error'] ? 'error' : 'success';
            }
        }

        $usuarios = $this->listarUsuarios();
        return ['usuarios' => $usuarios, 'mensaje' => $mensaje, 'mensaje_tipo' => $mensaje_tipo];
    }
}
?>
