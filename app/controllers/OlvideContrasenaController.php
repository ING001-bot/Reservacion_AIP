<?php
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../lib/Mailer.php';
use App\Lib\Mailer;

class OlvideContrasenaController {
    private UsuarioModel $model;
    private Mailer $mailer;

    public function __construct() {
        $this->model = new UsuarioModel();
        $this->mailer = new Mailer();
    }

    public function handle(): array {
        $msg = '';
        $type = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar_reset'])) {
            $correo = trim($_POST['correo'] ?? '');
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                return ['msg' => '⚠️ Ingresa un correo válido.', 'type' => 'danger'];
            }
            // Si el correo no existe activo ni inactivo, no revelar; responder genérico
            $user = $this->model->obtenerPorCorreo($correo);
            if (!$user) {
                return ['msg' => '✅ Si el correo existe, enviaremos un enlace para restablecer.', 'type' => 'success'];
            }
            // Reset password normal
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', time() + 60 * 30); // 30 minutos
            $ok = $this->model->guardarResetToken($correo, $token, $expira);
            if (!$ok) {
                return ['msg' => '❌ Ocurrió un error al generar el enlace. Intenta más tarde.', 'type' => 'danger'];
            }
            $link = $this->buildResetLink($correo, $token);
            $enviado = $this->enviarCorreoReset($correo, $user['nombre'] ?? 'Usuario', $link);
            $msg = $enviado ? '✅ Si el correo existe, enviaremos un enlace para restablecer.' : '❌ No se pudo enviar el correo. Intenta más tarde.';
            $type = $enviado ? 'success' : 'danger';
        }
        return ['msg' => $msg, 'type' => $type];
    }

    private function buildResetLink(string $correo, string $token): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '/';
        // Base antes de /app o /Public
        $baseProject = '';
        if (($pos = stripos($script, '/app/')) !== false) {
            $baseProject = substr($script, 0, $pos);
        } elseif (($pos = stripos($script, '/Public/')) !== false) {
            $baseProject = substr($script, 0, $pos);
        } else {
            $baseProject = rtrim(dirname(dirname($script)), '/');
        }
        $path = $baseProject . '/app/view/Restablecer_Contraseña.php';
        return sprintf('%s://%s%s?token=%s', $scheme, $host, $path, urlencode($token));
    }

    private function enviarCorreoReset(string $correo, string $nombre, string $link): bool {
        $subject = 'Restablecer contraseña - Aulas de Innovación';
        $safeLink = htmlspecialchars($link);
        $html = ''
            . '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#1f2a44;">'
            .   '<p>Hola ' . htmlspecialchars($nombre) . ',</p>'
            .   '<p>Recibimos una solicitud para restablecer tu contraseña. Haz clic en el botón:</p>'
            .   '<p style="margin:20px 0;">'
            .     '<a href="' . $safeLink . '" '
            .        'style="display:inline-block;background:#1e6bd6;color:#fff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:600;"'
            .     '>Restablecer contraseña</a>'
            .   '</p>'
            .   '<p style="color:#555;">Si el botón no se muestra o no funciona, copia y pega este enlace en tu navegador:</p>'
            .   '<p style="word-break:break-all;"><a href="' . $safeLink . '">' . $safeLink . '</a></p>'
            .   '<p style="color:#555;">Este enlace expira en 30 minutos.</p>'
            .   '<p style="color:#555;">Si no solicitaste este cambio, ignora este mensaje.</p>'
            . '</div>';
        return $this->mailer->send($correo, $subject, $html);
    }

}
