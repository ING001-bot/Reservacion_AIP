<?php
if (session_status() === PHP_SESSION_NONE) session_start();

class RouteGuard {
    public static function enforceInternalNav(): void {
        // Debe haber sesión activa
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: /Reservacion_AIP/Public/login.php');
            exit;
        }
        // Permitir llamadas AJAX/fetch
        $isAjax = (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            (isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'cors')
        );
        if ($isAjax) return;

        // Permitir siempre solicitudes POST (formularios) aunque no traigan Referer
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (strtoupper($method) === 'POST') {
            return;
        }

        // Verificar que la navegación sea interna (mismo origen)
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $sameOrigin = ($referer && parse_url($referer, PHP_URL_HOST) === $host);

        // Si no viene desde el mismo origen (tecleó la ruta o pegó URL), redirigir al panel según rol
        if (!$sameOrigin) {
            $tipo = $_SESSION['tipo'] ?? '';
            if ($tipo === 'Administrador') {
                header('Location: /Reservacion_AIP/app/view/Admin.php');
            } elseif ($tipo === 'Encargado') {
                header('Location: /Reservacion_AIP/app/view/Encargado.php');
            } else {
                header('Location: /Reservacion_AIP/app/view/Profesor.php');
            }
            exit;
        }
    }
}
