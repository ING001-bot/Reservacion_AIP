<?php
require_once '../models/UsuarioModel.php';
require_once __DIR__ . '/../lib/Mailer.php';
require_once __DIR__ . '/../lib/MailboxChecker.php';
use App\Lib\Mailer;
use App\Lib\MailboxChecker;

class UsuarioController {
    private $usuarioModel;
    private $mailer;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
        $this->mailer = new Mailer();
    }

    /** Registrar usuario (incluye Admin) con verificación obligatoria por correo */
    public function registrarUsuario($nombre, $correo, $contraseña, $tipo_usuario) {
        $nombre = trim($nombre);
        // Solo letras (incluye acentos) y espacios
        if (!preg_match('/^[\p{L}\s]+$/u', $nombre)) {
            return ['error' => true, 'mensaje' => '⚠️ El nombre solo puede contener letras y espacios.'];
        }
        // Validar correo (formato, MX y reglas estrictas de dominio)
        $correo = trim($correo);
        $val = $this->validarCorreoEstricto($correo);
        if ($val['error']) { return $val; }
        if ($this->usuarioModel->existeCorreo($correo)) {
            return ['error' => true, 'mensaje' => '⚠️ El correo ya está registrado. Si necesitas acceso, edita ese usuario o usa "Olvidé mi contraseña".'];
        }
        // Si existe como inactivo, reactivarlo (reusar fila) en vez de intentar insertar y chocar con UNIQUE
        $reactivado = false;
        if ($this->usuarioModel->existeCorreoInactivo($correo)) {
            $hash = password_hash($contraseña, PASSWORD_BCRYPT);
            $reactivado = $this->usuarioModel->reactivarUsuarioAdmin($nombre, $correo, $hash, $tipo_usuario);
            if ($reactivado) {
                // Confirmar existencia del buzón por SMTP
                $subject = 'Se reactivó tu cuenta - Aulas de Innovación';
                $html = '<p>Hola ' . htmlspecialchars($nombre) . ',</p>' .
                        '<p>Tu cuenta de administrador ha sido reactivada.</p>';
                $sent = $this->mailer->send($correo, $subject, $html);
                if (!$sent) {
                    // Si no se pudo notificar (correo inexistente), volvemos a inactivar para no dejar cuentas inválidas
                    $this->usuarioModel->eliminarUsuario($this->usuarioModel->obtenerPorCorreo($correo)['id_usuario'] ?? 0);
                    return ['error' => true, 'mensaje' => '❌ Ese correo no existe o no acepta mensajes. Solo se permiten correos existentes (verificados por envío).'];
                }
                return ['error' => false, 'mensaje' => '✅ Usuario reactivado y notificado por correo.'];
            }
        }
        if (strlen($contraseña) < 6) {
            return ['error' => true, 'mensaje' => '⚠️ La contraseña debe tener al menos 6 caracteres'];
        }
        $hash = password_hash($contraseña, PASSWORD_BCRYPT);
        // Registro con verificación obligatoria (verificado = 0)
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', time() + 24*60*60);
        $ok = $this->usuarioModel->registrarConVerificacion($nombre, $correo, $hash, $tipo_usuario, $token, $expira);
        if ($ok) {
            $link = $this->buildVerificationLink($correo, $token);
            $sent = $this->enviarCorreoVerificacion($correo, $nombre, $link);
            if (!$sent) {
                // rollback si no se pudo enviar (correo inexistente/no aceptado por el servidor SMTP)
                $this->usuarioModel->eliminarPorCorreo($correo);
                return ['error' => true, 'mensaje' => '❌ Ese correo no existe o no acepta mensajes. Solo se permiten correos existentes (verificados por envío).'];
            }
        }
        return ['error' => !$ok, 'mensaje' => $ok ? '✅ Usuario creado. Debe verificar su correo para activar la cuenta.' : '❌ Error al registrar'];
    }

    /** Registrar profesor público */
    public function registrarProfesorPublico($nombre, $correo, $contraseña) {
        $nombre = trim($nombre);
        if (!preg_match('/^[\p{L}\s]+$/u', $nombre)) {
            return ['error' => true, 'mensaje' => '⚠️ El nombre solo puede contener letras y espacios.'];
        }
        $correo = trim($correo);
        $val = $this->validarCorreoEstricto($correo);
        if ($val['error']) { return $val; }
        if ($this->usuarioModel->existeCorreo($correo)) {
            return ['error' => true, 'mensaje' => '⚠️ El correo ya está en uso'];
        }
        // Si existe como inactivo, reactivarlo (reusar fila) para registro público
        $reactivado = false;
        if ($this->usuarioModel->existeCorreoInactivo($correo)) {
            $hash = password_hash($contraseña, PASSWORD_BCRYPT);
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', time() + 24*60*60);
            $reactivado = $this->usuarioModel->reactivarUsuarioPublico($nombre, $correo, $hash, $token, $expira);
            if ($reactivado) {
                $link = $this->buildVerificationLink($correo, $token);
                $sent = $this->enviarCorreoVerificacion($correo, $nombre, $link);
                if (!$sent) {
                    // revertir reactivación si no hay buzón real
                    $this->usuarioModel->eliminarUsuario($this->usuarioModel->obtenerPorCorreo($correo)['id_usuario'] ?? 0);
                    return ['error' => true, 'mensaje' => '❌ Ese correo no existe o no acepta mensajes. Solo se permiten correos existentes (verificados por envío).'];
                }
                return ['error' => false, 'mensaje' => '✅ Cuenta reactivada. Revisa tu correo para verificarla.'];
            }
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
                return ['error' => true, 'mensaje' => '❌ Ese correo no existe o no acepta mensajes. Solo se permiten correos existentes (verificados por envío).'];
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
        $script = $_SERVER['SCRIPT_NAME'] ?? '/';
        // Detectar base del proyecto, anclando antes de '/app' o '/Public'
        $baseProject = '';
        if (($pos = stripos($script, '/app/')) !== false) {
            $baseProject = substr($script, 0, $pos);
        } elseif (($pos = stripos($script, '/Public/')) !== false) {
            $baseProject = substr($script, 0, $pos);
        } else {
            // fallback: usar dirname 2 veces
            $baseProject = rtrim(dirname(dirname($script)), '/');
        }
        $path = $baseProject . '/app/controllers/VerificarCorreoController.php';
        return sprintf('%s://%s%s?correo=%s&token=%s', $scheme, $host, $path, urlencode($correo), urlencode($token));
    }

    private function enviarCorreoVerificacion(string $correo, string $nombre, string $link): bool {
        $subject = 'Verifica tu cuenta - Aulas de Innovación';
        $safeLink = htmlspecialchars($link);
        $html = ''
            . '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1f2a44;">'
            .   '<p>Hola ' . htmlspecialchars($nombre) . ',</p>'
            .   '<p>Gracias por registrarte. Por favor verifica tu cuenta:</p>'
            .   '<p style="margin:20px 0;">'
            .     '<a href="' . $safeLink . '" '
            .        'style="display:inline-block;background:#1e6bd6;color:#fff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:600;"'
            .     '>Verificar mi cuenta</a>'
            .   '</p>'
            .   '<p style="color:#555;">Si el botón no se muestra o no funciona, copia y pega este enlace en tu navegador:</p>'
            .   '<p style="word-break:break-all;"><a href="' . $safeLink . '">' . $safeLink . '</a></p>'
            .   '<p style="color:#555;">Este enlace expira en 24 horas.</p>'
            .   '<p style="color:#555;">Si no solicitaste esta cuenta, ignora este mensaje.</p>'
            . '</div>';
        return $this->mailer->send($correo, $subject, $html);
    }

    // Validación estricta de correo: formato, MX y dominios comunes escritos correctamente
    private function validarCorreoEstricto(string $correo): array {
        $correo = trim(strtolower($correo));
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return ['error' => true, 'mensaje' => '⚠️ Completa correctamente el correo. Ejemplo: usuario@gmail.com'];
        }
        $atPos = strrpos($correo, '@');
        if ($atPos === false) {
            return ['error' => true, 'mensaje' => '⚠️ Correo inválido.'];
        }
        $dominio = substr($correo, $atPos + 1);

        // 1) Correcciones para dominios comunes mal escritos (p. ej., gmai.com en vez de gmail.com)
        $comunes = ['gmail.com','hotmail.com','outlook.com','yahoo.com','live.com','icloud.com','proton.me','protonmail.com'];
        foreach ($comunes as $ok) {
            // distancia de edición pequeña o misma cadena sin puntos
            $dist = levenshtein($dominio, $ok);
            $sinPuntosIgual = (str_replace('.', '', $dominio) === str_replace('.', '', $ok));
            if ($dist > 0 && $dist <= 1 || $sinPuntosIgual && $dominio !== $ok) {
                return ['error' => true, 'mensaje' => '⚠️ El dominio parece incorrecto. ¿Quisiste decir @' . $ok . '?'];
            }
        }

        // 2) Regla específica Gmail: debe ser exactamente gmail.com
        if (strpos($dominio, 'gmail') !== false && $dominio !== 'gmail.com') {
            return ['error' => true, 'mensaje' => '⚠️ Para correos Gmail usa el dominio correcto: @gmail.com'];
        }

        // 3) Validación MX (con fallback a dns_get_record)
        $tieneMX = null;
        if ($dominio) {
            if (function_exists('checkdnsrr')) {
                $tieneMX = checkdnsrr($dominio, 'MX');
            }
            if ($tieneMX === null && function_exists('dns_get_record')) {
                $mxRecords = @dns_get_record($dominio, DNS_MX);
                $tieneMX = is_array($mxRecords) && count($mxRecords) > 0;
            }
        }
        if ($tieneMX === false) {
            return ['error' => true, 'mensaje' => '⚠️ El dominio del correo no tiene registros MX válidos. Solo se aceptan correos existentes.'];
        }
        // 3.5) (sin API externa) continuar con chequeo RCPT-TO local y verificación por enlace
        // 4) Chequeo SMTP RCPT TO (si el servidor responde 550/551 -> inexistente)
        try {
            $checker = new MailboxChecker();
            $rcpt = $checker->check($correo, 5);
            if ($rcpt === false) {
                return ['error' => true, 'mensaje' => '⚠️ Ese buzón no existe según su servidor de correo. Verifica el correo ingresado.'];
            }
            // Si $rcpt es null (indeterminado), permitir continuar a verificación por enlace.
            // Grandes proveedores (Gmail/Outlook/etc.) suelen responder indeterminado.
        } catch (\Throwable $e) {
            // Ignorar errores del checker; seguiremos con verificación por enlace
        }
        return ['error' => false, 'mensaje' => ''];
    }
}
?>
