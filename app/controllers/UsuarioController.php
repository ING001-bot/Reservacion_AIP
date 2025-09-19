<?php
require_once '../models/UsuarioModel.php';
require_once __DIR__ . '/../lib/Mailer.php';
use App\Lib\Mailer;

class UsuarioController {
    private $usuarioModel;
    private $mailer;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
        $this->mailer = new Mailer();
    }

    /** Registrar usuario admin (auto-verificado, sin correo de verificación) */
    public function registrarUsuario($nombre, $correo, $contraseña, $tipo_usuario) {
        $nombre = trim($nombre);
        // Solo letras (incluye acentos) y espacios
        if (!preg_match('/^[\p{L}\s]+$/u', $nombre)) {
            return ['error' => true, 'mensaje' => '⚠️ El nombre solo puede contener letras y espacios.'];
        }
        // Validar correo (formato y dominio)
        $correo = trim($correo);
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return ['error' => true, 'mensaje' => '⚠️ Ingresa un correo válido.'];
        }
        $dominio = substr(strrchr($correo, '@'), 1);
        if ($dominio && function_exists('checkdnsrr') && !checkdnsrr($dominio, 'MX')) {
            return ['error' => true, 'mensaje' => '⚠️ El dominio del correo no tiene registros MX válidos.'];
        }
        if ($this->usuarioModel->existeCorreo($correo)) {
            return ['error' => true, 'mensaje' => '⚠️ El correo ya está registrado'];
        }
        if (strlen($contraseña) < 6) {
            return ['error' => true, 'mensaje' => '⚠️ La contraseña debe tener al menos 6 caracteres'];
        }
        $hash = password_hash($contraseña, PASSWORD_BCRYPT);
        // Admin: registrar como verificado directamente pero solo si el correo acepta notificación
        $ok = $this->usuarioModel->registrarVerificado($nombre, $correo, $hash, $tipo_usuario);
        if ($ok) {
            // Enviar correo de notificación para validar existencia del buzón
            $subject = 'Se te creó una cuenta - Aulas de Innovación';
            $html = '<p>Hola ' . htmlspecialchars($nombre) . ',</p>' .
                    '<p>Un administrador ha creado tu cuenta en el sistema Aulas de Innovación con este correo.</p>' .
                    '<p>Ya puedes iniciar sesión con tu correo y la contraseña definida.</p>';
            $sent = $this->mailer->send($correo, $subject, $html);
            if (!$sent) {
                // rollback si no se pudo enviar (correo inexistente/no aceptado por el servidor SMTP)
                $this->usuarioModel->eliminarPorCorreo($correo);
                return ['error' => true, 'mensaje' => '❌ No se pudo enviar correo a esa dirección. Usa un correo válido.'];
            }
        }
        return ['error' => !$ok, 'mensaje' => $ok ? '✅ Usuario registrado, verificado y notificado por correo.' : '❌ Error al registrar'];
    }

    /** Registrar profesor público */
    public function registrarProfesorPublico($nombre, $correo, $contraseña) {
        $nombre = trim($nombre);
        if (!preg_match('/^[\p{L}\s]+$/u', $nombre)) {
            return ['error' => true, 'mensaje' => '⚠️ El nombre solo puede contener letras y espacios.'];
        }
        $correo = trim($correo);
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return ['error' => true, 'mensaje' => '⚠️ Ingresa un correo válido.'];
        }
        $dominio = substr(strrchr($correo, '@'), 1);
        if ($dominio && function_exists('checkdnsrr') && !checkdnsrr($dominio, 'MX')) {
            return ['error' => true, 'mensaje' => '⚠️ El dominio del correo no tiene registros MX válidos.'];
        }
        if ($this->usuarioModel->existeCorreo($correo)) {
            return ['error' => true, 'mensaje' => '⚠️ El correo ya está en uso'];
        }
        if (strlen($contraseña) < 6) {
            return ['error' => true, 'mensaje' => '⚠️ La contraseña debe tener al menos 6 caracteres'];
        }
        $hash = password_hash($contraseña, PASSWORD_BCRYPT);
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', time() + 24*60*60);
        $ok = $this->usuarioModel->registrarConVerificacion($nombre, $correo, $hash, 'Profesor', $token, $expira);
        if ($ok) {
            $link = $this->buildVerificationLink($correo, $token);
            $sent = $this->enviarCorreoVerificacion($correo, $nombre, $link);
            if (!$sent) {
                // Si no se pudo enviar, revertimos el registro para evitar cuentas imposibles de verificar
                $this->usuarioModel->eliminarPorCorreo($correo);
                return ['error' => true, 'mensaje' => '❌ No se pudo enviar el correo de verificación. Intenta con un correo válido o más tarde.'];
            }
        }
        return ['error' => !$ok, 'mensaje' => $ok ? '✅ Cuenta creada. Revisa tu correo para verificarla.' : '❌ Error al crear cuenta'];
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

    private function buildVerificationLink(string $correo, string $token): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
        // Link directo al controlador de verificación
        $path = $base . '/../controllers/VerificarCorreoController.php';
        return sprintf('%s://%s%s?correo=%s&token=%s', $scheme, $host, $path, urlencode($correo), urlencode($token));
    }

    private function enviarCorreoVerificacion(string $correo, string $nombre, string $link): bool {
        $subject = 'Verifica tu cuenta - Aulas de Innovación';
        $html = '<p>Hola ' . htmlspecialchars($nombre) . ',</p>' .
                '<p>Gracias por registrarte. Por favor, verifica tu cuenta haciendo clic en el siguiente enlace:</p>' .
                '<p><a href="' . htmlspecialchars($link) . '">Verificar mi cuenta</a></p>' .
                '<p>Este enlace expira en 24 horas.</p>' .
                '<p>Si no solicitaste esta cuenta, ignora este mensaje.</p>';
        return $this->mailer->send($correo, $subject, $html);
    }
}
?>
