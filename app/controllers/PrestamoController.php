<?php
require_once '../models/PrestamoModel.php';
require_once __DIR__ . '/../lib/NotificationService.php';
use App\Lib\NotificationService;

class PrestamoController {
    private $model;

    public function __construct($conexion) {
        $this->model = new PrestamoModel($conexion);
    }

    public function guardarPrestamosMultiple($id_usuario, $equipos, $fecha_prestamo, $hora_inicio, $hora_fin = null, $id_aula) {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_SESSION['tipo'] ?? '') === 'Profesor') {
            if (empty($_SESSION['otp_verified'])) {
                return ['tipo'=>'error','mensaje'=>'Debes verificar tu identidad con el código SMS antes de confirmar el préstamo.'];
            }
        }
        $res = $this->model->guardarPrestamosMultiple($id_usuario, $equipos, $fecha_prestamo, $hora_inicio, $hora_fin, $id_aula);
        if (($res['tipo'] ?? '') === 'success') {
            // Construir detalles del préstamo (equipos por nombre)
            $detalles = '';
            try {
                $eqs = $this->model->obtenerEquiposPorIds($equipos);
                if ($eqs) {
                    $nombres = array_map(function($e){ return $e['nombre_equipo'] ?? 'Equipo'; }, $eqs);
                    $detalles = 'Equipos: ' . implode(', ', $nombres) . '. ';
                }
            } catch (\Throwable $e) { /* noop */ }

            // Notificar Admin y Encargado: correo + campanita
            try {
                $usuarios = $this->model->listarUsuariosPorRol(['Administrador','Encargado']);
                $docente = $_SESSION['usuario'] ?? 'Docente';
                $mensaje = 'Nuevo préstamo registrado por ' . $docente . '. ' . $detalles . 'Fecha: ' . $fecha_prestamo . ', ' . $hora_inicio . '-' . ($hora_fin?:'-') . '.';
                foreach ($usuarios as $u) {
                    // Campanita
                    $this->model->crearNotificacion((int)$u['id_usuario'], 'Nuevo préstamo de equipos', $mensaje, 'Admin.php?view=historial_global');
                    // Correo
                    if (!empty($u['correo'])) {
                        $ns = new NotificationService();
                        $ns->sendNotification(
                            ['email' => $u['correo']],
                            'Nuevo préstamo de equipos',
                            nl2br(htmlspecialchars($mensaje)),
                            [
                                'userName' => ($u['nombre'] ?? 'Usuario'),
                                'type' => 'info',
                                'sendSms' => false,
                                'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/Sistema_reserva_AIP/Admin.php?view=historial_global'
                            ]
                        );
                    }
                }
            } catch (\Throwable $e) { /* log suave */ }
        }
        return $res;
    }

    public function listarEquiposPorTipo($tipo) {
        return $this->model->listarEquiposPorTipo($tipo);
    }
    
    public function listarEquiposPorTipoConStock($tipo, $fecha) {
        return $this->model->listarEquiposPorTipoConStock($tipo, $fecha);
    }
    
    public function listarTodosEquipos() {
        return $this->model->listarTodosEquipos();
    }

    public function obtenerTodosPrestamos() {
        return $this->model->obtenerTodosPrestamos();
    }

    public function listarPrestamosPorUsuario($id_usuario) {
        return $this->model->listarPrestamosPorUsuario($id_usuario);
    }

    public function devolverEquipo($id_prestamo, ?string $comentario = null) {
        $ok = $this->model->devolverEquipo($id_prestamo, $comentario);
        if ($ok) {
            // Notificación interna mínima al profesor; sin correos ni consultas extra
            try {
                $idProfesor = $this->model->obtenerUsuarioPorPrestamo((int)$id_prestamo);
                if ($idProfesor) {
                    $titulo = 'Devolución registrada';
                    $mensaje = 'Tu préstamo #'.(int)$id_prestamo.' fue marcado como devuelto.' . ($comentario ? (' Observación: '.$comentario) : '');
                    $this->model->crearNotificacion($idProfesor, $titulo, $mensaje, 'Historial.php');
                }
            } catch (\Throwable $e) { /* log suave */ }
        }
        return $ok;
    }

    public function obtenerPrestamosFiltrados(?string $estado, ?string $desde, ?string $hasta, ?string $q): array {
        return $this->model->obtenerPrestamosFiltrados($estado, $desde, $hasta, $q);
    }

    // ========================= NUEVO: Packs de préstamo =========================
    public function guardarPrestamoPack(int $id_usuario, int $id_aula, string $fecha_prestamo, string $hora_inicio, ?string $hora_fin, array $items): array {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_SESSION['tipo'] ?? '') === 'Profesor') {
            if (empty($_SESSION['otp_verified'])) {
                return ['tipo'=>'error','mensaje'=>'Debes verificar tu identidad con el código SMS antes de confirmar el préstamo.'];
            }
        }
        $res = $this->model->crearPrestamoPack($id_usuario, $id_aula, $fecha_prestamo, $hora_inicio, $hora_fin, $items);
        if (($res['tipo'] ?? '') === 'success') {
            // Detalles de items del pack
            $detalles = '';
            try {
                $labels = [];
                foreach ($items as $it) {
                    $t = strtoupper($it['tipo'] ?? '');
                    $cant = (int)($it['cantidad'] ?? 0);
                    if ($t && $cant > 0) { $labels[] = $t . ' x' . $cant; }
                }
                if ($labels) { $detalles = 'Items: ' . implode(', ', $labels) . '. '; }
            } catch (\Throwable $e) { /* noop */ }

            // Notificar Admin y Encargado: correo + campanita
            try {
                $usuarios = $this->model->listarUsuariosPorRol(['Administrador','Encargado']);
                $docente = $_SESSION['usuario'] ?? 'Docente';
                $msg = 'Nuevo préstamo (pack) registrado por ' . $docente . '. ' . $detalles . 'Fecha: ' . $fecha_prestamo . ', ' . $hora_inicio . '-' . ($hora_fin?:'-') . '.';
                foreach ($usuarios as $u) {
                    // Campanita
                    $this->model->crearNotificacion((int)$u['id_usuario'], 'Nuevo préstamo (pack)', $msg, 'Admin.php?view=historial_global');
                    // Correo
                    if (!empty($u['correo'])) {
                        $ns = new NotificationService();
                        $ns->sendNotification(
                            ['email' => $u['correo']],
                            'Nuevo préstamo (pack)',
                            nl2br(htmlspecialchars($msg)),
                            [
                                'userName' => ($u['nombre'] ?? 'Usuario'),
                                'type' => 'info',
                                'sendSms' => false,
                                'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/Sistema_reserva_AIP/Admin.php?view=historial_global'
                            ]
                        );
                    }
                }
            } catch (\Throwable $e) { /* log suave */ }
        }
        return $res;
    }

    public function listarPacksPorUsuario(int $id_usuario): array {
        return $this->model->listarPacksPorUsuario($id_usuario);
    }

    public function obtenerItemsDePack(int $id_pack): array {
        return $this->model->obtenerItemsDePack($id_pack);
    }

    // Nuevos: soporte para Devolución de packs y filtros
    public function listarPacksFiltrados(?string $estado, ?string $desde, ?string $hasta, ?string $q): array {
        return $this->model->listarPacksFiltrados($estado, $desde, $hasta, $q);
    }

    public function devolverPack(int $id_pack, ?string $comentario = null): bool {
        $ok = $this->model->devolverPack($id_pack, $comentario);
        if ($ok) {
            // Notificación interna mínima al profesor; sin correos ni consultas pesadas
            try {
                $idProfesor = $this->model->obtenerUsuarioPorPack((int)$id_pack);
                if ($idProfesor) {
                    $titulo = 'Devolución de pack registrada';
                    $mensaje = 'Tu préstamo (pack) #'.(int)$id_pack.' fue marcado como devuelto.' . ($comentario ? (' Observación: '.$comentario) : '');
                    $this->model->crearNotificacion($idProfesor, $titulo, $mensaje, 'Historial.php');
                }
            } catch (\Throwable $e) { /* log suave */ }
        }
        return $ok;
    }

    // ========================= Notificaciones (in-app) =========================
    public function listarNotificacionesUsuario(int $id_usuario, bool $soloNoLeidas = false, int $limit = 10): array {
        return $this->model->listarNotificacionesUsuario($id_usuario, $soloNoLeidas, $limit);
    }

    public function marcarNotificacionLeida(int $id_notificacion, int $id_usuario): bool {
        return $this->model->marcarNotificacionLeida($id_notificacion, $id_usuario);
    }

    public function marcarTodasNotificacionesLeidas(int $id_usuario): bool {
        return $this->model->marcarTodasNotificacionesLeidas($id_usuario);
    }
}
?>
