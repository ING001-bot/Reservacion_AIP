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
