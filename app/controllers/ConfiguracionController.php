<?php
require_once __DIR__ . '/../models/ConfiguracionModel.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class ConfiguracionController {
    private $configModel;
    private $usuarioModel;

    public function __construct() {
        $this->configModel = new ConfiguracionModel();
        $this->usuarioModel = new UsuarioModel();
    }

    /**
     * Obtener perfil completo del usuario
     */
    public function obtenerPerfil(int $id_usuario): ?array {
        return $this->configModel->obtenerPerfilCompleto($id_usuario);
    }

    /**
     * Actualizar foto de perfil
     */
    public function actualizarFoto(int $id_usuario, array $archivo): array {
        // Validar que sea una imagen
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $extensionesPermitidas)) {
            return ['error' => true, 'mensaje' => '⚠️ Solo se permiten imágenes (JPG, PNG, GIF, WEBP)'];
        }

        // Validar tamaño (máx 2MB)
        if ($archivo['size'] > 2 * 1024 * 1024) {
            return ['error' => true, 'mensaje' => '⚠️ La imagen no debe superar 2MB'];
        }

        // Crear directorio si no existe
        $directorioDestino = __DIR__ . '/../../Public/uploads/perfiles/';
        if (!is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0755, true);
        }

        // Generar nombre único
        $nombreArchivo = 'perfil_' . $id_usuario . '_' . time() . '.' . $extension;
        $rutaCompleta = $directorioDestino . $nombreArchivo;
        $rutaRelativa = 'uploads/perfiles/' . $nombreArchivo;

        // Eliminar foto anterior si existe
        $configAnterior = $this->configModel->obtenerConfiguracion($id_usuario);
        if ($configAnterior && !empty($configAnterior['foto_perfil'])) {
            $rutaAnterior = __DIR__ . '/../../Public/' . $configAnterior['foto_perfil'];
            if (file_exists($rutaAnterior)) {
                @unlink($rutaAnterior);
            }
        }

        // Mover archivo
        if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            // Guardar en BD
            $ok = $this->configModel->actualizarFotoPerfil($id_usuario, $rutaRelativa);
            return [
                'error' => !$ok,
                'mensaje' => $ok ? '✅ Foto de perfil actualizada' : '❌ Error al guardar en base de datos',
                'ruta' => $ok ? $rutaRelativa : null
            ];
        }

        return ['error' => true, 'mensaje' => '❌ Error al subir la imagen'];
    }

    /**
     * Eliminar foto de perfil
     */
    public function eliminarFoto(int $id_usuario): array {
        $ok = $this->configModel->eliminarFotoPerfil($id_usuario);
        return [
            'error' => !$ok,
            'mensaje' => $ok ? '✅ Foto eliminada' : '❌ Error al eliminar'
        ];
    }

    /**
     * Actualizar biografía/información adicional
     */
    public function actualizarBio(int $id_usuario, string $bio): array {
        $ok = $this->configModel->actualizarBio($id_usuario, $bio);
        return [
            'error' => !$ok,
            'mensaje' => $ok ? '✅ Información actualizada' : '❌ Error al actualizar'
        ];
    }

    /**
     * Actualizar datos personales (nombre, correo, teléfono)
     */
    public function actualizarDatosPersonales(int $id_usuario, string $nombre, string $correo, ?string $telefono): array {
        // Validar nombre
        $nombre = trim($nombre);
        if (!preg_match('/^[\p{L}\s]+$/u', $nombre)) {
            return ['error' => true, 'mensaje' => '⚠️ El nombre solo puede contener letras y espacios'];
        }

        // Validar correo
        $correo = strtolower(trim($correo));
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return ['error' => true, 'mensaje' => '⚠️ Correo inválido'];
        }

        // Verificar que el correo no esté en uso por otro usuario
        if ($this->usuarioModel->existeCorreoDeOtro($correo, $id_usuario)) {
            return ['error' => true, 'mensaje' => '⚠️ El correo ya está en uso'];
        }

        // Verificar teléfono si se proporciona
        if (!empty($telefono)) {
            if ($this->usuarioModel->existeTelefonoDeOtro($telefono, $id_usuario)) {
                return ['error' => true, 'mensaje' => '⚠️ El teléfono ya está registrado'];
            }
        }

        // Obtener datos actuales del usuario
        $usuarioActual = $this->usuarioModel->obtenerPorId($id_usuario);
        if (!$usuarioActual) {
            return ['error' => true, 'mensaje' => '⚠️ Usuario no encontrado'];
        }

        // Actualizar
        $ok = $this->usuarioModel->actualizarUsuario(
            $id_usuario,
            $nombre,
            $correo,
            $usuarioActual['tipo_usuario'],
            $telefono
        );

        return [
            'error' => !$ok,
            'mensaje' => $ok ? '✅ Datos actualizados correctamente' : '❌ Error al actualizar'
        ];
    }

    /**
     * Cambiar rol de usuario (solo para administradores)
     */
    public function cambiarRol(int $id_usuario, string $nuevo_rol): array {
        $rolesPermitidos = ['Administrador', 'Profesor', 'Encargado'];
        
        if (!in_array($nuevo_rol, $rolesPermitidos)) {
            return ['error' => true, 'mensaje' => '⚠️ Rol no válido'];
        }

        $usuario = $this->usuarioModel->obtenerPorId($id_usuario);
        if (!$usuario) {
            return ['error' => true, 'mensaje' => '⚠️ Usuario no encontrado'];
        }

        $ok = $this->usuarioModel->actualizarUsuario(
            $id_usuario,
            $usuario['nombre'],
            $usuario['correo'],
            $nuevo_rol,
            $usuario['telefono'] ?? null
        );

        return [
            'error' => !$ok,
            'mensaje' => $ok ? "✅ Rol cambiado a $nuevo_rol" : '❌ Error al cambiar rol'
        ];
    }
}
